<?php

namespace App\Services;

use TCPDF;

class LabPdfService
{
    /** @param array<string, mixed> $request
     * @param  array<string, mixed>  $lab
     */
    public function render(array $request, array $lab): string
    {
        $pdf = new TCPDF('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($lab['quotation_no']);
        $pdf->SetMargins(42, 24, 42);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(3, 2, 3, 2);
        $pdf->AddPage();
        $pdf->SetLineWidth(0.5);

        $this->isoHeader($pdf);

        $y = 150.0;
        $this->text($pdf, 42, $y, 514, 16, 'Section 1  (To be filled-out by customer)', 'I', 9, 'C');
        $y += 24;
        $client = $request['client'];
        $y += max($this->field($pdf, 42, $y, 308, 'Requesting Official/Person:', $client['fullname']), $this->field($pdf, 350, $y, 206, 'Designation:', $client['type_client'] ?? ''));
        $y += $this->field($pdf, 42, $y, 514, 'Company/Affiliation:', $client['company_or_school'] ?? '');
        $y += $this->field($pdf, 42, $y, 514, 'Address:', $client['address']);
        $y += $this->field($pdf, 42, $y, 514, 'Email:', $client['email']);
        $y += $this->field($pdf, 42, $y, 514, 'Contact Number (Telephone/Mobile):', $client['mobile_no']);
        $y += $this->field($pdf, 42, $y, 514, 'Sample Description:', $request['description']);
        $y += 10;
        $pdf->SetLineStyle(['dash' => '4,2']);
        $pdf->Line(42, $y, 556, $y);
        $pdf->SetLineStyle(['dash' => 0]);
        $y += 16;

        $this->text($pdf, 42, $y, 514, 16, 'Section 2  (To be filled-out by Receiving and Releasing Officer)', 'I', 9, 'C');
        $y += 24;
        $this->text($pdf, 42, $y, 514, 14, 'Quotation No.: '.$lab['quotation_no'], '', 9, 'R');
        $y += 14;
        $this->text($pdf, 42, $y, 514, 14, 'Date: '.$request['created_at'], '', 9, 'R');
        $y += 18;
        $y += $this->field($pdf, 42, $y, 514, 'Type/Number of Samples:', $lab['sample_type']);

        $widths = [148, 148, 37, 85, 96];
        $this->row($pdf, $y, $widths, ['TEST/S', 'TEST METHOD CONDITIONS', 'QTY', 'UNIT COST', 'TOTAL'], 20, 'B');
        $y += 20;
        foreach ($lab['items'] as $item) {
            $values = [$item['test'], $item['method'], (string) $item['quantity'], $this->money($item['unit_fee']), $this->money($item['total_fee'])];
            $pdf->SetFont('helvetica', '', 9);
            $height = 18.0;
            foreach ($values as $i => $value) {
                $height = max($height, $pdf->getStringHeight($widths[$i], (string) $value) + 6);
            }
            if ($y + $height > 700) {
                $pdf->AddPage();
                $this->isoHeader($pdf);
                $y = 150.0;
                $this->row($pdf, $y, $widths, ['TEST/S', 'TEST METHOD CONDITIONS', 'QTY', 'UNIT COST', 'TOTAL'], 20, 'B');
                $y += 20;
            }
            $this->row($pdf, $y, $widths, $values, $height);
            $y += $height;
        }
        if ($y + 140 > 735) {
            $pdf->AddPage();
            $this->isoHeader($pdf);
            $y = 150.0;
        }
        $y += 10;
        foreach (['Sub-Total:' => 'sub_total', 'Discount:' => 'discount', 'TOTAL:' => 'total_fee'] as $label => $key) {
            $this->text($pdf, 412, $y, 68, 15, $label, '', 8);
            $this->text($pdf, 480, $y, 76, 15, $this->money($lab[$key]), 'BU', 8, 'R');
            $y += 15;
        }

        $y += 20;
        $cols = [257, 257];
        $rows = [
            ['Assessed by: _________________________', 'Reviewed by: _________________________'],
            ['Receiving and Releasing Officer', 'Technical Manager'],
            ['Date: _____________________', 'Date: _____________________'],
        ];
        $aligns = ['L', 'C', 'L'];
        foreach ($rows as $i => $values) {
            $height = 20.0;
            $x = 42.0;
            foreach ($cols as $c => $width) {
                $pdf->Rect($x, $y, $width, $height);
                $this->text($pdf, $x, $y, $width, $height, $values[$c], '', 8, $aligns[$i]);
                $x += $width;
            }
            $y += $height;
        }

        return $pdf->Output('', 'S');
    }

    private function isoHeader(TCPDF $pdf): void
    {
        $top = 40.0;
        $rowHeights = [20, 20, 20, 32];
        $leftWidth = 330.0;
        $rightWidth = 184.0;
        $total = array_sum($rowHeights);

        $pdf->Rect(42, $top, $leftWidth + $rightWidth, $total);
        $pdf->Line(42 + $leftWidth, $top, 42 + $leftWidth, $top + $total);

        $rowY = $top;
        foreach (array_slice($rowHeights, 0, -1) as $height) {
            $rowY += $height;
            $pdf->Line(42, $rowY, 556, $rowY);
        }

        $y = $top;
        $pdf->Image(resource_path('images/ptri-logo.jpg'), 46, $y + 2, 16, 16);
        $this->text($pdf, 66, $y, $leftWidth - 24, $rowHeights[0], 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 8, 'C');
        $this->text($pdf, 42 + $leftWidth, $y, $rightWidth, $rowHeights[0], 'PNS ISO/IEC 17025-2017', '', 8, 'C');
        $y += $rowHeights[0];
        $this->text($pdf, 42, $y, $leftWidth, $rowHeights[1], 'Testing Laboratories', '', 8, 'C');
        $this->text($pdf, 42 + $leftWidth, $y, $rightWidth, $rowHeights[1], 'PM-07.01-A-F1', 'B', 8, 'C');
        $y += $rowHeights[1];
        $this->text($pdf, 42, $y, $leftWidth, $rowHeights[2], 'PROCEDURES MANUAL FORM', 'B', 8, 'C');
        $this->text($pdf, 42 + $leftWidth, $y, $rightWidth, $rowHeights[2], 'Page 1 of 1', '', 8, 'C');
        $y += $rowHeights[2];
        $this->text($pdf, 42, $y, $leftWidth, $rowHeights[3], 'Quotation for Testing Services', 'B', 11, 'C');
        $this->text($pdf, 42 + $leftWidth + 6, $y + 4, $rightWidth - 10, $rowHeights[3], "Revision.: 1\nDate of Issue: 04 January 2024", '', 7, 'L');
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
