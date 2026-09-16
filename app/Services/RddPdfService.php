<?php

namespace App\Services;

use TCPDF;

class RddPdfService
{
    /** @param array<string, mixed> $request
     * @param  array<string, mixed>  $rdd
     */
    public function render(array $request, array $rdd): string
    {
        $pdf = new NumberedPdf('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetTitle($rdd['reference_no']);
        $pdf->SetMargins(42, 24, 42);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(3, 2, 3, 2);
        $pdf->AddPage();
        $pdf->SetLineWidth(0.5);
        $this->text($pdf, 474, 22, 84, 24, "RDD Form No. 001\nRev. 2/15-05-17", '', 8);
        $pdf->Image(resource_path('images/ptri-logo.jpg'), 55, 60, 58, 58);
        $this->text($pdf, 42, 52, 514, 12, 'Department of Science and Technology', '', 8, 'C');
        $this->text($pdf, 42, 64, 514, 12, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 9, 'C');
        $this->text($pdf, 42, 76, 514, 12, 'Research and Development Division', 'B', 8, 'C');
        $this->text($pdf, 42, 88, 514, 12, 'Gen. Santos Ave., Bicutan Taguig City, 1631 Philippines', '', 7, 'C');
        $this->text($pdf, 42, 100, 514, 12, 'Tel Nos. (632) 827-2171 to 82 loc. 2367 Telefax No. 88371349', '', 7, 'C');
        $this->text($pdf, 42, 112, 514, 12, 'http://www.ptri.dost.gov.ph', '', 7, 'C');
        $this->text($pdf, 42, 141, 514, 18, 'SERVICE REQUEST FORM', 'BI', 11, 'C');
        $this->text($pdf, 42, 164, 514, 16, 'Section 1  (To be filled out by customer)', 'I', 9, 'C');
        $this->text($pdf, 42, 188, 210, 16, 'Customer Information', 'B');
        $this->text($pdf, 320, 188, 236, 16, 'Date/Time: '.$request['created_at'], '', 8, 'R');
        $client = $request['client'];
        $y = 216.0;
        $y += max($this->field($pdf, 42, $y, 308, 'Requesting Official/Name:', $client['fullname']), $this->field($pdf, 350, $y, 206, 'Designation:', $client['type_client'] ?? ''));
        $y += $this->field($pdf, 42, $y, 514, 'Company/Affiliation:', $client['company_or_school'] ?? '');
        $y += $this->field($pdf, 42, $y, 514, 'Address:', $client['address']);
        $y += max($this->field($pdf, 42, $y, 260, 'Email:', $client['email']), $this->field($pdf, 302, $y, 125, 'Fax No.:', $client['fax_no'] ?: 'N/A'), $this->field($pdf, 427, $y, 129, 'Contact No.:', $client['mobile_no']));
        $y += $this->field($pdf, 42, $y, 514, 'Sample Description:', $request['description']);
        $y += 8;
        $this->text($pdf, 42, $y, 514, 18, 'Section 2  (To be filled out by Receiving Officer)', 'I', 9, 'C');
        $y += 28;
        $y += max($this->field($pdf, 42, $y, 300, 'Customer Reference No.:', $rdd['reference_no']), $this->field($pdf, 342, $y, 214, 'Due Date:', $rdd['due_date']));
        $widths = [188, 91, 37, 85, 113];
        $this->row($pdf, $y, $widths, ['SERVICE REQUEST', 'SPECIFICATIONS', 'QTY', 'UNIT FEE', 'TOTAL FEE'], 20, 'B');
        $y += 20;
        foreach ($rdd['items'] as $item) {
            $values = [$item['item'], $item['specification'], (string) $item['quantity'], $this->money($item['unit_fee']), $this->money($item['total_fee'])];
            $pdf->SetFont('helvetica', '', 9);
            $height = 18.0;
            foreach ($values as $i => $value) {
                $height = max($height, $pdf->getStringHeight($widths[$i], (string) $value) + 6);
            }
            if ($y + $height > 725) {
                $pdf->AddPage();
                $y = 42;
                $this->row($pdf, $y, $widths, ['SERVICE REQUEST', 'SPECIFICATIONS', 'QTY', 'UNIT FEE', 'TOTAL FEE'], 20, 'B');
                $y += 20;
            }
            $this->row($pdf, $y, $widths, $values, $height);
            $y += $height;
        }
        if ($y + 170 > 735) {
            $pdf->AddPage();
            $y = 42;
        }
        $y += 10;
        foreach (['SUB-TOTAL:' => 'sub_total', 'DISCOUNT:' => 'discount', 'TOTAL FEE:' => 'total_fee'] as $label => $key) {
            $this->text($pdf, 412, $y, 68, 15, $label, '', 8);
            $this->text($pdf, 480, $y, 76, 15, $this->money($rdd[$key]), 'BU', 8, 'R');
            $y += 15;
        }
        $paymentY = $y - 30;
        $this->field($pdf, 42, $paymentY, 238, 'OP No.:', $rdd['op_no'] ?? '');
        $this->field($pdf, 280, $paymentY, 118, 'Date:', $rdd['op_no'] ? ($rdd['payment_verified_at'] ?? '') : '');
        $this->field($pdf, 42, $paymentY + 18, 238, 'Official Receipt No.:', $rdd['or_no'] ?? '');
        $this->field($pdf, 280, $paymentY + 18, 118, 'Date:', $rdd['or_no'] ? ($rdd['payment_verified_at'] ?? '') : '');
        $this->text($pdf, 42, $paymentY + 36, 360, 18, 'Mode of Charging:          (  ) Cash          (  ) Managers Check', '', 9);
        $y = $paymentY + 72;
        foreach ([['', 'SIGNATURE', 'DATE'], ['Customer/Authorized Representative', '', ''], ['Received by:', '', ''], ['Reviewed by:', '', '']] as $i => $values) {
            $this->row($pdf, $y, [216, 186, 112], $values, 20, $i === 0 ? 'B' : '', $i !== 0);
            $y += 20;
        }
        $pdf->SetLineStyle(['dash' => '4,2']);
        $pdf->Line(42, 750, 556, 750);
        $pdf->SetLineStyle(['dash' => 0]);
        $this->text($pdf, 42, 762, 514, 16, 'Materials accepted in good quality condition.', 'B', 9, 'C');
        $this->field($pdf, 42, 784, 216, 'Released by:', '');
        $this->field($pdf, 258, 784, 186, 'Received by:', '');
        $this->field($pdf, 444, 784, 112, 'Date:', '');

        return $pdf->Output('', 'S');
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

    private function field(TCPDF $pdf, float $x, float $y, float $width, string $label, ?string $value): float
    {
        $pdf->SetFont('helvetica', '', 9);
        $labelWidth = $pdf->GetStringWidth($label) + 8;
        $pdf->SetFont('helvetica', 'BU', 9);
        $height = max(18, $pdf->getStringHeight($width - $labelWidth, $value ?? '') + 4);
        $this->text($pdf, $x, $y, $labelWidth, $height, $label);
        $this->text($pdf, $x + $labelWidth, $y, $width - $labelWidth, $height, $value ?? '', 'BU');
        if (! $value) {
            $pdf->Line($x + $labelWidth + 3, $y + 12, $x + $width - 5, $y + 12);
        }

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
