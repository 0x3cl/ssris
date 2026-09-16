<?php

namespace App\Services;

use TCPDF;

class TrainingServiceRequestPdfService
{
    /** @var list<array{label: string, text: string, subs: list<string>}> */
    private const TERMS = [
        ['label' => '', 'text' => 'DOST-PTRI agrees to provide training services in accordance with:', 'subs' => [
            'the Training Services Request (TSR) form and the terms and conditions herein stated, unless otherwise specifically stipulated and agreed upon in writing by the parties;',
            'the requestor specific instructions only, or of any party authorized by the requestor;',
        ]],
        ['label' => '', 'text' => "DOST-PTRI agrees to exercise reasonable diligence in the manner of performing the training services, however, either expressed or implied, is herein stipulated relative to PTRI's training services. In no event shall DOST-PTRI be liable for collateral, special, or consequential damage, any fault, or negligence of its officers or employees.", 'subs' => []],
        ['label' => 'TRAVEL.', 'text' => 'The requestor agrees to provide the transportation expenses such as round-trip plane, taxi, vehicle, toll fee/s and other transportation expenses. (Regional Training)', 'subs' => []],
        ['label' => 'ACCOMMODATION:', 'text' => 'The requestor agrees to shoulder accommodation, meals and other incidental expenses related to their travel and training. (Regional Training)', 'subs' => []],
        ['label' => '', 'text' => 'Requestor agrees to have the training done according to the stated schedule.', 'subs' => []],
        ['label' => '', 'text' => 'Requestor understands that any information (written, verbal, or other form) obtained during the services request shall remain confidential or may also be legally privileged.', 'subs' => []],
        ['label' => '', 'text' => 'All personal information necessary for the purpose of this transaction are obtained with consent of the requestor. PTRI agrees to keep all information confidential unless authorized by the requestor or unless required by law.', 'subs' => []],
        ['label' => '', 'text' => 'The Requestor shall:', 'subs' => [
            'ensure that the instructions to DOST-PTRI and other relevant information are provided in due time to enable effective performance of training services;',
            'provide materials and equipment necessary for the hands-on training activities (Regional Training);',
            'for in-house training, payment in full amount as quoted, no deduction or withholding tax, should be paid within the training duration;',
            "allow DOST-PTRI to use his/her personal information and/or the company's information, including photos, for documentation and preparation of PTRI reports, subject to the Implementing Rules and Regulations (IRR) of RA 10173 Data Privacy Act of 2012.",
        ]],
        ['label' => '', 'text' => 'This Terms and Conditions is only for such items/materials and work as specified herein. Any other additions or amendments after acceptance will be separately charged.', 'subs' => []],
        ['label' => 'CANCELLATION:', 'text' => 'The trainer/training officer reserves the right to cancel or terminate the training, notwithstanding any other provisions of this terms and conditions, when immediate action is required to safeguard the life and safety or jeopardize the welfare of the trainers, or in an event the requestor fails to perform any of its duties, until such issues are resolved.', 'subs' => []],
        ['label' => '', 'text' => 'The Requestor agrees that complaint, question, or dispute shall be given due course only if made in writing.', 'subs' => []],
        ['label' => '', 'text' => 'The terms and conditions herein stated shall, in all respects, operate as a contract between the parties in conformity with Philippine laws.', 'subs' => []],
        ['label' => 'CONFORME:', 'text' => 'By signing below, I hereby agree that I have completely read and fully understand this training service contract.', 'subs' => []],
    ];

    /** @param array<string, mixed> $request
     * @param  array<string, mixed>  $training
     */
    public function render(array $request, array $training): string
    {
        $pdf = new NumberedPdf('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetTitle($training['reference_no'] ?? 'Training Services Request');
        $pdf->SetMargins(42, 24, 42);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(3, 2, 3, 2);
        $pdf->AddPage();
        $pdf->SetLineWidth(0.5);
        $this->text($pdf, 474, 22, 84, 24, "TSD Form No. 025\nRev. 1/09-03-25", '', 8);
        $pdf->Image(resource_path('images/ptri-logo.jpg'), 55, 60, 58, 58);
        $this->text($pdf, 132, 50, 399, 12, 'Republic of the Philippines', '', 8, 'L');
        $this->text($pdf, 132, 61, 399, 12, 'DEPARTMENT OF SCIENCE AND TECHNOLOGY', 'B', 9, 'L');
        $this->text($pdf, 132, 73, 399, 12, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 8, 'L');
        $this->text($pdf, 132, 85, 399, 12, 'TECHNICAL SERVICES DIVISION', 'B', 8, 'L');
        $this->text($pdf, 132, 97, 399, 12, 'General Santos Avenue, Bicutan, Taguig City, 1631 Philippines', '', 7, 'L');
        $this->text($pdf, 132, 108, 399, 12, 'Tel Nos. (632) 837-2071 to 82 loc. 2369 Telefax No. (632) 837-1157', '', 7, 'L');
        $this->text($pdf, 132, 119, 399, 12, 'http://www.ptri.dost.gov.ph / Email: ptri.training@ptri.dost.gov.ph', '', 7, 'L');
        $this->text($pdf, 42, 152, 514, 18, 'TRAINING SERVICES REQUEST', 'BI', 11, 'C');
        $pdf->Line(42, 172, 556, 172);
        $this->text($pdf, 42, 178, 514, 16, 'PART 1  (Requestor Information)', 'I', 9, 'C');

        $client = $request['client'];
        $y = 200.0;
        $y += max($this->field($pdf, 42, $y, 254, 'Reference No.:', $training['reference_no'] ?? ''), $this->field($pdf, 316, $y, 240, 'Date/Time:', $request['created_at']));
        $y += max($this->field($pdf, 42, $y, 308, 'Requesting Official/Person:', $client['fullname']), $this->field($pdf, 360, $y, 196, 'Designation:', $client['type_client'] ?? ''));
        $y += max($this->field($pdf, 42, $y, 308, 'Company/School/Project:', $client['company_or_school'] ?? ''), $this->field($pdf, 360, $y, 196, 'Region:', $client['region'] ?? ''));
        $y += $this->field($pdf, 42, $y, 514, 'Address:', $client['address']);
        $y += max($this->field($pdf, 42, $y, 210, 'Email:', $client['email']), $this->field($pdf, 262, $y, 130, 'Fax No.:', $client['fax_no'] ?: 'N/A'), $this->field($pdf, 402, $y, 154, 'Contact No.:', $client['mobile_no']));
        $y += 6;

        $rowHeight = 13.0;
        $typeOptions = [
            'academe' => 'Academe',
            'government' => 'Government',
            'non-government-organizations' => 'Non-Government Organizations',
            'private-companies' => 'Private Companies',
            'individual' => 'Individual',
        ];
        $genderOptions = ['male' => 'Male', 'female' => 'Female'];
        $ageOptions = ['21-31' => '21-31', '31-40' => '31-40', '41-50' => '41-50', '>= 51' => '>= 51'];
        $height = $this->checkboxColumn($pdf, 42, $y, 220, 'Type of Client:', $typeOptions, $client['type_client_value'] ?? null, $rowHeight);
        $this->checkboxColumn($pdf, 270, $y, 140, 'Gender:', $genderOptions, $client['gender_value'] ?? null, $rowHeight);
        $this->checkboxColumn($pdf, 418, $y, 138, 'Age:', $ageOptions, $this->ageBracket($client['age'] ?? null), $rowHeight);
        $y += $height + 10;

        $this->text($pdf, 42, $y, 514, 16, 'PART 2  (Training Details)', 'I', 9, 'C');
        $y += 18;
        if ($y + 140 > 740) {
            $pdf->AddPage();
            $y = 42;
        }
        $y = $this->infoTable($pdf, $y, [
            ['Training Course Requested:', $training['training_course_requested']],
            ['Estimated number of Participants:', (string) $training['estimated_participants']],
            ['Proposed Training Date:', $training['proposed_training_date']],
            ['Proposed Training Venue:', $training['proposed_training_venue']],
            ['Name of Beneficiary/Community:', $training['beneficiary_name']],
            ['Purpose of training:', $training['purpose_of_training']],
        ]);

        $y += 6;
        if ($y + 150 > 750) {
            $pdf->AddPage();
            $y = 42;
        }
        $this->text($pdf, 42, $y, 514, 16, 'PART 3  (To be filled up of TSD Training Officer)', 'I', 9, 'C');
        $y += 18;
        $y = $this->infoTable($pdf, $y, [
            ['Assigned Trainer:', $training['assigned_trainer']],
            ['Assigned Assistant Trainer:', $training['assigned_assistant_trainer'] ?: ''],
            ['Official Course Title:', $training['official_course_title']],
            ['Approved Training Duration:', $training['approved_training_duration']],
        ]);

        $trainingTypeHeight = 40.0;
        $pdf->Rect(42, $y, 190, $trainingTypeHeight);
        $this->text($pdf, 46, $y + 4, 182, $trainingTypeHeight - 8, 'Type of Training', 'B', 9);
        $pdf->Rect(232, $y, 324, $trainingTypeHeight);
        $trainingTypeOptions = ['In-house' => 'in-house', 'Regional' => 'regional', 'Virtual' => 'virtual'];
        $cx = 240.0;
        foreach ($trainingTypeOptions as $label => $value) {
            $this->checkbox($pdf, $cx, $y + 6, $value === ($training['training_type'] ?? null));
            $this->text($pdf, $cx + 12, $y + 5, 70, 12, $label, '', 8);
            $cx += 92;
        }
        $this->field($pdf, 240, $y + 21, 300, 'If special pls. specify:', $training['special_type_details'] ?: '');
        $y += $trainingTypeHeight;

        $y += 10;
        if ($y + 90 > 810) {
            $pdf->AddPage();
            $y = 42;
        }
        foreach ([['', 'SIGNATURE', 'DATE'], ['Requesting Official/Person', '', ''], ['Assessed by: Training Officer', '', ''], ['Approved by: Division/Section Chief', '', '']] as $i => $values) {
            $this->row($pdf, $y, [216, 186, 112], $values, 20, $i === 0 ? 'B' : '', $i !== 0);
            $y += 20;
        }
        $y += 8;
        if ($y + 14 > 798) {
            $pdf->AddPage();
            $y = 42;
        }
        $this->text($pdf, 42, $y, 514, 14, 'Please refer at the back page for the terms and conditions', 'I', 8, 'C');

        $pdf->AddPage();
        $this->text($pdf, 42, 42, 514, 18, 'TERMS AND CONDITIONS', 'BU', 11, 'C');
        $y = 76.0;
        foreach (self::TERMS as $index => $term) {
            $this->term($pdf, $y, $index + 1, $term['label'], $term['text'], $term['subs']);
        }
        $y += 16;
        $this->text($pdf, 100, $y, 120, 14, 'Name & Signature:', '', 9);
        $pdf->Line(220, $y + 11, 400, $y + 11);
        $this->text($pdf, 410, $y, 40, 14, 'Date:', '', 9);
        $pdf->Line(450, $y + 11, 540, $y + 11);

        return $pdf->Output('', 'S');
    }

    private function ageBracket(mixed $age): ?string
    {
        if ($age === null || $age === '') {
            return null;
        }

        $age = (int) $age;

        return match (true) {
            $age <= 31 => '21-31',
            $age <= 40 => '31-40',
            $age <= 50 => '41-50',
            default => '>= 51',
        };
    }

    /** @param array<string, string> $options */
    private function checkboxColumn(TCPDF $pdf, float $x, float $y, float $width, string $label, array $options, ?string $selected, float $rowHeight): float
    {
        $this->text($pdf, $x, $y, $width, $rowHeight, $label, 'B', 9);
        $rowY = $y + $rowHeight;
        foreach ($options as $value => $option) {
            $this->checkbox($pdf, $x, $rowY + 3, $value === $selected);
            $this->text($pdf, $x + 12, $rowY + 1, $width - 12, $rowHeight, $option, '', 8);
            $rowY += $rowHeight;
        }

        return $rowY - $y;
    }

    private function checkbox(TCPDF $pdf, float $x, float $y, bool $checked): void
    {
        $pdf->Rect($x, $y, 8, 8);
        if ($checked) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetCellPadding(0);
            $pdf->MultiCell(8, 8, 'X', 0, 'C', false, 0, $x, $y - 1);
            $pdf->SetCellPaddings(3, 2, 3, 2);
        }
    }

    /** @param list<array{0: string, 1: string}> $rows */
    private function infoTable(TCPDF $pdf, float $y, array $rows): float
    {
        foreach ($rows as [$label, $value]) {
            $pdf->SetFont('helvetica', '', 9);
            $height = max(18, $pdf->getStringHeight(316, $value) + 6);
            $pdf->Rect(42, $y, 190, $height);
            $pdf->Rect(232, $y, 324, $height);
            $this->text($pdf, 47, $y + 4, 180, $height - 8, $label, 'B', 9);
            $this->text($pdf, 238, $y + 4, 312, $height - 8, $value, '', 9);
            if ($value === '' || $value === null) {
                $pdf->Line(238, $y + $height - 6, 550, $y + $height - 6);
            }
            $y += $height;
        }

        return $y;
    }

    private function text(TCPDF $pdf, float $x, float $y, float $width, float $height, string $text, string $style = '', float $size = 9, string $align = 'L'): void
    {
        $pdf->SetFont('helvetica', $style, $size);
        $pdf->MultiCell($width, $height, $text, 0, $align, false, 0, $x, $y);
    }

    /** @param list<string> $subs */
    private function term(TCPDF $pdf, float &$y, int $number, string $label, string $text, array $subs): void
    {
        $indent = 68.0;
        $pdf->SetFont('helvetica', 'B', 9);
        $labelWidth = $label !== '' ? $pdf->GetStringWidth($label) + 11 : 0;
        $contentX = $indent + $labelWidth;
        $contentWidth = 556 - $contentX;
        $pdf->SetFont('helvetica', '', 9);
        $height = max(12, $pdf->getStringHeight($contentWidth, $text) + 3);
        $this->text($pdf, 42, $y, $indent - 42, $height, $number.'.', '', 9);
        if ($label !== '') {
            $this->text($pdf, $indent, $y, $labelWidth, $height, $label, 'B', 9);
        }
        $this->text($pdf, $contentX, $y, $contentWidth, $height, $text, '', 9);
        $y += $height;

        $subIndent = 88.0;
        $subWidth = 556 - $subIndent;
        foreach ($subs as $letterIndex => $subText) {
            $letter = chr(97 + $letterIndex);
            $subHeight = max(11, $pdf->getStringHeight($subWidth, $subText) + 2);
            $this->text($pdf, $indent, $y, $subIndent - $indent, $subHeight, $letter.'.', '', 9);
            $this->text($pdf, $subIndent, $y, $subWidth, $subHeight, $subText, '', 9);
            $y += $subHeight;
        }
        $y += 5;
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
            $align = $leftFirst && $i === 0 ? 'L' : 'C';
            $textX = $align === 'L' ? $x + 4 : $x;
            $textWidth = $align === 'L' ? $width - 8 : $width;
            $this->text($pdf, $textX, $y, $textWidth, $height, (string) $values[$i], $style, 8, $align);
            $x += $width;
        }
    }
}
