<?php

namespace App\Services;

use TCPDF;

class TrainingServiceFeePdfService
{
    /** @param array<string, mixed> $fee
     * @param  array<string, mixed>  $client
     */
    public function render(array $fee, array $client): string
    {
        $pdf = new NumberedPdf('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetTitle($fee['reference_no'] ?? 'Training Services Fee');
        $pdf->SetMargins(42, 24, 42);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(0, 0, 0, 0);
        $pdf->AddPage();
        $pdf->SetLineWidth(0.5);

        $y = 30.0;
        $y = $this->block($pdf, $y, $fee, $client);
        $y += 18;
        $pdf->SetLineStyle(['dash' => '4,2']);
        $pdf->Line(42, $y, 556, $y);
        $pdf->SetLineStyle(['dash' => 0]);
        $y += 32;
        $this->block($pdf, $y, $fee, $client);

        return $pdf->Output('', 'S');
    }

    /** @param array<string, mixed> $fee
     * @param  array<string, mixed>  $client
     */
    private function block(TCPDF $pdf, float $y, array $fee, array $client): float
    {
        $this->text($pdf, 474, $y, 84, 24, "TSD Form No. 015\nRev. 0/ 01-09-23", '', 8);
        $pdf->Image(resource_path('images/ptri-logo.jpg'), 46, $y + 2, 44, 44);
        $this->text($pdf, 42, $y + 2, 514, 12, 'Republic of the Philippines', '', 8, 'C');
        $this->text($pdf, 42, $y + 13, 514, 12, 'Department of Science and Technology', '', 8, 'C');
        $this->text($pdf, 42, $y + 24, 514, 12, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 9, 'C');
        $this->text($pdf, 42, $y + 36, 514, 12, 'Bicutan, Taguig City', '', 8, 'C');
        $this->text($pdf, 42, $y + 54, 514, 16, 'TRAINING SERVICES FEE', 'BU', 10, 'C');
        $this->text($pdf, 42, $y + 72, 502, 14, 'Date/Time: '.($fee['date_time'] ?? ''), '', 8, 'R');
        $y += 92;

        $nameH = $this->fieldHeight($pdf, 372, 'Name:', $client['fullname']);
        $addressH = $this->fieldHeight($pdf, 372, 'Address:', $client['address']);
        $this->field($pdf, 42, $y, 372, $nameH, 'Name:', $client['fullname']);
        $this->field($pdf, 42, $y + $nameH, 372, $addressH, 'Address:', $client['address']);
        $this->boxedField($pdf, 414, $y, 130, $nameH + $addressH, 'REFERENCE NO.', $fee['reference_no'] ?? '');
        $y += $nameH + $addressH;

        $contactH = $this->fieldHeight($pdf, 251, 'Contact No.:', $client['mobile_no']);
        $occupationH = $this->fieldHeight($pdf, 251, 'Occupation:', $client['occupation'] ?? '');
        $rowHeight = max($contactH, $occupationH);
        $this->field($pdf, 42, $y, 251, $rowHeight, 'Contact No.:', $client['mobile_no']);
        $this->field($pdf, 293, $y, 251, $rowHeight, 'Occupation:', $client['occupation'] ?? '');
        $y += $rowHeight;

        $businessLineH = $this->fieldHeight($pdf, 502, 'Business Line:', $client['business_line'] ?? '');
        $this->field($pdf, 42, $y, 502, $businessLineH, 'Business Line:', $client['business_line'] ?? '');
        $y += $businessLineH;

        $widths = [176, 106, 106, 114];
        $this->row($pdf, $y, $widths, ['Particulars', 'Duration', 'No. of Participants', 'Net Amount Due'], 18, 'B');
        $y += 18;
        $values = [$fee['particulars'], $fee['duration'], (string) $fee['no_participants'], $this->money($fee['net_amount_due'])];
        $pdf->SetFont('helvetica', '', 8);
        $dataRowHeight = 18.0;
        foreach ($values as $i => $value) {
            $dataRowHeight = max($dataRowHeight, $pdf->getStringHeight($widths[$i], (string) $value) + 6);
        }
        $this->row($pdf, $y, $widths, $values, $dataRowHeight, '', true);
        $y += $dataRowHeight + 8;

        $colWidth = 502 / 3;
        foreach ([['Assessed by:', ''], ['Approved by:', ''], ['Conforme:', '']] as $i => [$label]) {
            $this->text($pdf, 42 + $i * $colWidth, $y, $colWidth - 6, 12, $label, '', 8);
        }
        $pdf->Line(42, $y + 28, 42 + $colWidth - 20, $y + 28);
        $pdf->Line(42 + $colWidth, $y + 28, 42 + 2 * $colWidth - 20, $y + 28);
        $pdf->Line(42 + 2 * $colWidth, $y + 28, 42 + 3 * $colWidth - 20, $y + 28);
        $this->text($pdf, 42, $y + 30, $colWidth - 6, 12, 'Training Officer', '', 7, 'C');
        $this->text($pdf, 42 + $colWidth, $y + 30, $colWidth - 6, 12, 'Chief, TSD', '', 7, 'C');
        $this->text($pdf, 42 + 2 * $colWidth, $y + 30, $colWidth - 6, 12, "Customer's Name and Signature", '', 7, 'C');
        $this->text($pdf, 42 + 2 * $colWidth, $y + 42, $colWidth - 6, 12, 'Date:', '', 7);
        $y += 56;

        $signatureWidths = [248, 128, 126];
        $this->field($pdf, 42, $y, $signatureWidths[0], 18, 'Accounting staff signature:', '');
        $this->field($pdf, 42 + $signatureWidths[0], $y, $signatureWidths[1], 18, 'Bill No.:', $fee['bill_no'] ?? '');
        $this->field($pdf, 42 + $signatureWidths[0] + $signatureWidths[1], $y, $signatureWidths[2], 18, 'Date:', '');
        $y += 18;
        $this->field($pdf, 42, $y, $signatureWidths[0], 18, 'Collection staff signature:', '');
        $this->field($pdf, 42 + $signatureWidths[0], $y, $signatureWidths[1], 18, 'O.R. No.:', $fee['or_no'] ?? '');
        $this->field($pdf, 42 + $signatureWidths[0] + $signatureWidths[1], $y, $signatureWidths[2], 18, 'Date:', '');
        $y += 18;

        $this->text($pdf, 42, $y + 4, 514, 12, '*Original official receipt to be mailed by the customer', 'I', 7);
        $y += 18;

        return $y;
    }

    private function money(mixed $amount): string
    {
        return 'PHP '.number_format((float) $amount, 2);
    }

    private function text(TCPDF $pdf, float $x, float $y, float $width, float $height, string $text, string $style = '', float $size = 9, string $align = 'L'): void
    {
        $pdf->SetFont('helvetica', $style, $size);
        $pdf->MultiCell($width, $height, $text, 0, $align, false, 0, $x, $y);
    }

    private function fieldHeight(TCPDF $pdf, float $width, string $label, ?string $value): float
    {
        $pdf->SetFont('helvetica', '', 8);
        $labelWidth = $pdf->GetStringWidth($label) + 10;
        $pdf->SetFont('helvetica', 'B', 8);

        return max(16, $pdf->getStringHeight($width - $labelWidth - 6, $value ?? '') + 6);
    }

    private function field(TCPDF $pdf, float $x, float $y, float $width, float $height, string $label, ?string $value): void
    {
        $pdf->Rect($x, $y, $width, $height);
        $pdf->SetFont('helvetica', '', 8);
        $labelWidth = $pdf->GetStringWidth($label) + 10;
        $this->text($pdf, $x + 4, $y, $labelWidth, $height, $label, 'B', 8);
        $this->text($pdf, $x + 4 + $labelWidth, $y, $width - $labelWidth - 8, $height, $value ?? '', '', 8);
    }

    private function boxedField(TCPDF $pdf, float $x, float $y, float $width, float $height, string $label, string $value): float
    {
        $pdf->Rect($x, $y, $width, $height);
        $this->text($pdf, $x + 3, $y + 4, $width - 6, 10, $label, 'B', 7, 'C');
        $this->text($pdf, $x + 3, $y + 16, $width - 6, 10, $value, '', 8, 'C');

        return $height;
    }

    /** @param list<int> $widths
     * @param  list<string>  $values
     */
    private function row(TCPDF $pdf, float $y, array $widths, array $values, float $height, string $style = '', bool $leftFirst = false): void
    {
        $x = 42.0;
        foreach ($widths as $i => $width) {
            $pdf->Rect($x, $y, $width, $height);
            $this->text($pdf, $x, $y, $width, $height, (string) $values[$i], $style, 8, $leftFirst && $i === 0 ? 'L' : 'C');
            $x += $width;
        }
    }
}
