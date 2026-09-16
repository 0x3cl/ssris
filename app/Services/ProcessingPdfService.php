<?php

namespace App\Services;

use TCPDF;

class ProcessingPdfService
{
    /** @var list<array{text: string, subs: list<string>}> */
    private const TERMS = [
        ['text' => 'PTRI agrees to provide processing services in accordance with:', 'subs' => [
            'the Processing Services Request (PSR) form and the terms and conditions herein stated, unless otherwise specifically stipulated and agreed upon in writing by the parties;',
            "the customer's specific instructions only, or of any party authorized by the customer;",
            'processing procedures considered by PTRI to be appropriate based on technical, operational, and/or financial grounds.',
        ]],
        ['text' => "PTRI agrees to exercise reasonable diligence in the manner of performing the processing services, however, no warranty, either expressed or implied, is herein stipulated relative to PTRI's Processing services. In no event shall PTRI be liable for collateral, special, or consequential damage, any fault, or negligence of its officers or employees.", 'subs' => []],
        ['text' => 'Customer agrees to have the processing done according to the stated schedule. Cancellation of processing request shall not be allowed.', 'subs' => []],
        ['text' => 'Customer understands that any information (written, verbal, or other form) obtained during the services request shall remain confidential or may also be legally privileged.', 'subs' => []],
        ['text' => 'All personal information necessary for the purpose of this transaction are obtained with consent of the customer. PTRI agrees to keep all information confidential unless authorized by the customer or unless required by law.', 'subs' => []],
        ['text' => 'The customer shall:', 'subs' => [
            'ensure that the instructions to PTRI and other relevant information are provided in due time to enable effective performance of processing services;',
            'provide the required quantity and description of the materials for processing;',
            'pay in full as quoted, i.e., no deduction or withholding tax, prior to processing;',
            "allow PTRI to use his/her personal information and/or the company's information, including photos, for documentation and preparation of PTRI reports, subject to the Implementing Rules and Regulations (IRR) of RA 10173 Data Privacy Act of 2012.",
        ]],
        ['text' => 'This contract is only for such items/materials and work as specified herein. Any other additions or amendments after acceptance will be separately charged.', 'subs' => []],
        ['text' => 'Excess materials shall be stored for three (3) months. Beyond this period materials shall be disposed of.', 'subs' => []],
        ['text' => 'Processed materials not picked up by customers one (1) week after the due date are considered PTRI property and shall be handled accordingly.', 'subs' => []],
        ['text' => 'The customer agrees that complaint, question, or dispute shall be given due course only if made in writing. For verbal communications, the customer shall fill-out the required form for the purpose.', 'subs' => []],
        ['text' => 'The terms and conditions herein stated shall in all respects operate as a contract between the parties in conformity with Philippine laws.', 'subs' => []],
    ];

    /** @param array<string, mixed> $request
     * @param  array<string, mixed>  $processing
     */
    public function render(array $request, array $processing): string
    {
        $pdf = new TCPDF('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($processing['reference_no']);
        $pdf->SetMargins(42, 24, 42);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(3, 2, 3, 2);
        $pdf->AddPage();
        $pdf->SetLineWidth(0.5);
        $this->formNumber($pdf);
        $pdf->Rect(43, 45, 512, 85);
        $pdf->Image(resource_path('images/ptri-logo.jpg'), 55, 60, 58, 58);
        $this->text($pdf, 132, 52, 399, 12, 'Department of Science and Technology', '', 8, 'C');
        $this->text($pdf, 132, 64, 399, 12, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 9, 'C');
        $this->text($pdf, 132, 76, 399, 12, 'Technical Services Division', 'B', 8, 'C');
        $this->text($pdf, 132, 88, 399, 12, 'Gen. Santos Ave., Bicutan Taguig City, 1631 Philippines', '', 7, 'C');
        $this->text($pdf, 132, 100, 399, 12, 'Tel Nos. (632) 827-2171 to 82 loc. 2367 Telefax No. 88371349', '', 7, 'C');
        $this->text($pdf, 132, 112, 399, 12, 'http://www.ptri.dost.gov.ph', '', 7, 'C');
        $this->text($pdf, 42, 141, 514, 18, 'PROCESSING SERVICES REQUEST', 'BI', 11, 'C');
        $this->text($pdf, 42, 164, 514, 16, 'PART 1  (To be filled out by customer)', 'I', 9, 'C');
        $this->text($pdf, 42, 188, 514, 16, 'Date/Time: '.$request['created_at'], '', 8, 'R');
        $client = $request['client'];
        $y = 216.0;
        $y += max($this->field($pdf, 42, $y, 308, 'Requesting Official/Person:', $client['fullname']), $this->field($pdf, 350, $y, 206, 'Designation:', $client['type_client'] ?? ''));
        $y += $this->field($pdf, 42, $y, 514, 'Company/School/Project:', $client['company_or_school'] ?? '');
        $y += $this->field($pdf, 42, $y, 514, 'Address:', $client['address']);
        $y += max($this->field($pdf, 42, $y, 260, 'Email:', $client['email']), $this->field($pdf, 302, $y, 125, 'Fax No.:', $client['fax_no'] ?: 'N/A'), $this->field($pdf, 427, $y, 129, 'Contact No.:', $client['mobile_no']));
        $y += max($this->field($pdf, 42, $y, 220, 'Type of Client:', $client['type_client'] ?? ''), $this->field($pdf, 262, $y, 160, 'Gender:', $client['gender'] ?? ''), $this->field($pdf, 422, $y, 134, 'Age:', (string) ($client['age'] ?? '')));
        $y += 8;
        $this->text($pdf, 42, $y, 514, 18, 'PART 2  (To be filled out by Receiving Officer)', 'I', 9, 'C');
        $y += 28;
        $y += $this->field($pdf, 42, $y, 514, 'Sample Description:', $request['description']);
        $y += max($this->field($pdf, 42, $y, 254, 'Reference No.:', $processing['reference_no']), $this->field($pdf, 306, $y, 250, 'Sample No.:', $processing['sample_no']));
        $y += max($this->field($pdf, 42, $y, 254, 'Type of Sample:', $processing['sample_type']), $this->field($pdf, 306, $y, 250, 'Due Date:', $processing['due_date']));
        $widths = [214, 100, 90, 110];
        $this->row($pdf, $y, $widths, ['SERVICE REQUEST', 'WEIGHT/QTY', 'UNIT FEE', 'TOTAL FEE'], 20, 'B');
        $y += 20;
        foreach ($processing['items'] as $item) {
            $values = [$item['item'], $item['weight'].' × '.$item['quantity'], $this->money($item['unit_fee']), $this->money($item['total_fee'])];
            $pdf->SetFont('helvetica', '', 9);
            $height = 18.0;
            foreach ($values as $i => $value) {
                $height = max($height, $pdf->getStringHeight($widths[$i], (string) $value) + 6);
            }
            if ($y + $height > 725) {
                $pdf->AddPage();
                $y = 42;
                $this->row($pdf, $y, $widths, ['SERVICE REQUEST', 'WEIGHT/QTY', 'UNIT FEE', 'TOTAL FEE'], 20, 'B');
                $y += 20;
            }
            $this->row($pdf, $y, $widths, $values, $height);
            $y += $height;
        }
        if ($y + 150 > 735) {
            $pdf->AddPage();
            $y = 42;
        }
        $y += 10;
        foreach (['SUB-TOTAL:' => 'sub_total', 'DISCOUNT:' => 'discount', 'TOTAL FEE:' => 'total_fee'] as $label => $key) {
            $this->text($pdf, 412, $y, 68, 15, $label, '', 8);
            $this->text($pdf, 480, $y, 76, 15, $this->money($processing[$key]), 'BU', 8, 'R');
            $y += 15;
        }
        $paymentY = $y - 30;
        $this->field($pdf, 42, $paymentY, 238, 'OP No.:', $processing['op_no'] ?? '');
        $this->field($pdf, 280, $paymentY, 118, 'Date:', $processing['op_no'] ? ($processing['payment_verified_at'] ?? '') : '');
        $this->field($pdf, 42, $paymentY + 18, 238, 'Official Receipt No.:', $processing['or_no'] ?? '');
        $this->field($pdf, 280, $paymentY + 18, 118, 'Date:', $processing['or_no'] ? ($processing['payment_verified_at'] ?? '') : '');
        $y = $paymentY + 46;
        foreach ([['', 'SIGNATURE', 'DATE'], ['Customer/Authorized Representative', '', ''], ['Received by:', '', ''], ['Reviewed by:', '', '']] as $i => $values) {
            $this->row($pdf, $y, [216, 186, 112], $values, 20, $i === 0 ? 'B' : '', $i !== 0);
            $y += 20;
        }
        $y += 20;
        $this->text($pdf, 42, $y, 514, 14, 'Please refer at the back page for the terms and conditions', 'I', 8, 'C');
        $this->text($pdf, 42, $y + 18, 514, 14, 'Page 1 of 2', '', 8, 'C');

        $pdf->AddPage();
        $this->formNumber($pdf);
        $y = 120.0;
        $this->text($pdf, 42, $y, 514, 18, 'TERMS AND CONDITIONS', 'BIU', 11, 'C');
        $y += 34;
        foreach (self::TERMS as $index => $term) {
            $this->term($pdf, $y, $index + 1, $term['text'], $term['subs']);
        }
        $y += 20;
        $this->text($pdf, 42, $y, 514, 16, '(  )  I have read and agreed to the Terms and Conditions', 'BI', 9, 'C');
        $this->text($pdf, 42, 800, 514, 14, 'Page 2 of 2', '', 8, 'C');

        return $pdf->Output('', 'S');
    }

    private function formNumber(TCPDF $pdf): void
    {
        $this->text($pdf, 474, 22, 84, 24, "TSD Form No. 001\nRev. 4/04-10-21", '', 8);
    }

    /** @param list<string> $subs */
    private function term(TCPDF $pdf, float &$y, int $number, string $text, array $subs): void
    {
        $indent = 68.0;
        $width = 556 - $indent;
        $pdf->SetFont('helvetica', 'I', 9);
        $height = max(12, $pdf->getStringHeight($width, $text) + 3);
        $this->text($pdf, 42, $y, $indent - 42, $height, $number.'.', 'I', 9);
        $this->text($pdf, $indent, $y, $width, $height, $text, 'I', 9);
        $y += $height;

        $subIndent = 88.0;
        $subWidth = 556 - $subIndent;
        foreach ($subs as $letterIndex => $subText) {
            $letter = chr(97 + $letterIndex);
            $pdf->SetFont('helvetica', 'I', 9);
            $subHeight = max(11, $pdf->getStringHeight($subWidth, $subText) + 2);
            $this->text($pdf, $indent, $y, $subIndent - $indent, $subHeight, $letter.'.', 'I', 9);
            $this->text($pdf, $subIndent, $y, $subWidth, $subHeight, $subText, 'I', 9);
            $y += $subHeight;
        }
        $y += 5;
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
