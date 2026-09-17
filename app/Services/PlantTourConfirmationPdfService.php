<?php

namespace App\Services;

use TCPDF;

class PlantTourConfirmationPdfService
{
    /** @param array<string, mixed> $confirmation */
    public function render(array $confirmation): string
    {
        $pdf = new NumberedPdf('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetTitle('Tour Confirmation');
        $pdf->SetMargins(42, 30, 42);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(0, 0, 0, 0);
        $pdf->AddPage();

        $pdf->Image(resource_path('images/ptri-logo.jpg'), 48, 58, 70, 70);
        $this->text($pdf, 130, 58, 330, 14, 'Republic of the Philippines', '', 8);
        $this->text($pdf, 130, 72, 360, 17, 'Department of Science and Technology', '', 10);
        $this->text($pdf, 130, 88, 360, 17, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 11);
        $this->text($pdf, 130, 105, 370, 30, "Gen. Santos Ave., Bicutan Taguig City, 1631 Philippines\nTel Nos. (632) 837-2071 to 82 loc. 2369  Fax No. (632) 8837-1157\nhttp://www.ptri.dost.gov.ph / Email: ptri@ptri.dost.gov.ph", '', 7);
        $this->text($pdf, 460, 24, 96, 28, "TIPS Form No. 021\nRev. 6/20-11-21", '', 8, 'R');
        $this->text($pdf, 42, 150, 514, 22, 'TOUR CONFIRMATION', 'B', 13, 'C');
        $this->text($pdf, 42, 183, 280, 16, 'Tour Request No: '.$confirmation['reference_no'], '', 10);
        $this->text($pdf, 370, 183, 186, 16, 'Date: '.$confirmation['date'], '', 10, 'R');

        $y = 214.0;
        foreach ([
            'RECIPIENT:' => $confirmation['recipient'],
            'REQUESTING PARTY:' => $confirmation['requesting_party'],
            'DATE & TIME:' => $confirmation['visit_date_time'],
            'FACILITIES TO BE VISITED:' => $confirmation['facilities'],
            'PURPOSE OF VISIT:' => $confirmation['purpose'],
        ] as $label => $value) {
            $this->text($pdf, 42, $y, 190, 17, $label, 'B', 10);
            $height = max(17, $pdf->getStringHeight(315, $value) + 2);
            $this->text($pdf, 232, $y, 324, $height, $value, '', 10);
            $y += $height;
        }

        $y += 34;
        $this->text($pdf, 42, $y, 514, 18, 'Below are our few important reminders for the tour:', 'B', 10);
        $y += 20;
        $reminders = [
            'Kindly advise the participants to wear comfortable clothes as it will be hot in some parts of the pilot plant and in open areas.',
            'Observe minimum health standards and protocols by bringing and wearing face mask at all times. Fill-out and submit the Health Declaration Form before entry to the PTRI building using this link: https://tinyurl.com/PTRI-HDF. Show the security guard the tour confirmation form to enter PTRI. Please note that for health protocol and validation purposes, everyone will still have to undergo temperature and vaccination card checking before entry to the PTRI premises and upon arrival in the lobby even if the Health Declaration Form has been accomplished before your visit.',
            'Participants may bring their own refreshments for consumption before or after the tour in designated areas;',
            'You may bring extra face masks for emergency purposes. Please assess and inform participants (especially those with any serious respiratory issues and allergies) that the plant tour exposes participants to different textile processing machines and fiber particles around. The institute shall not be liable for any injury that the participants may sustain or for losses whether personal or pecuniary in the whole duration of the tour;',
            'Onsite demonstration will not be part of the open facility tour as it is part of the training course we offer entitled "Basic Textile Technology Course", which requires participants to enroll in their preferred course. Please see the description of the said courses in this link if you wish to avail the training courses: bit.ly/PTRITrainingRegistration',
            'In case you have processed and intended to take photos or videos for documentation purposes, please do not forget to bring the four (4) original copies of the signed Agreement on Provision of Technical Assistance on the day of the tour. Failure to bring the said documents will not be permitted to perform such activities.',
            'Bringing personal pens are highly encouraged.',
        ];
        foreach ($reminders as $index => $reminder) {
            $height = $pdf->getStringHeight(494, ($index + 1).'. '.$reminder) + 2;
            $this->text($pdf, 42, $y, 514, $height, ($index + 1).'. '.$reminder, '', 8.5);
            $y += $height;
        }

        $y += 24;
        $this->signatory($pdf, $y, "PTRI-DOST\nCoordinator:", $confirmation['prepared_by'], 'PTA V, OD-TIPS');
        $this->signatory($pdf, $y + 55, 'Noted by:', $confirmation['noted_by'], 'Head, OD-TIPS');
        $this->signatory($pdf, $y + 105, 'Approved by:', $confirmation['approved_by'], 'Chief, FAD, PTRI');
        $this->text($pdf, 42, 780, 514, 25, "DOST-PTRI Building, DOST South Compound, General Santos Avenue, Bicutan, Taguig City 1631 Philippines\nURL: https://ptri.dost.gov.ph     @PTRI-DOST     @TELAPilipinas", '', 8, 'C');

        return $pdf->Output('', 'S');
    }

    private function signatory(TCPDF $pdf, float $y, string $label, string $name, string $title): void
    {
        $this->text($pdf, 42, $y, 96, 32, $label, 'B', 10);
        $this->text($pdf, 200, $y, 280, 32, $name."\n".$title, '', 10);
    }

    private function text(TCPDF $pdf, float $x, float $y, float $width, float $height, string $text, string $style = '', float $size = 9, string $align = 'L'): void
    {
        $pdf->SetFont('helvetica', $style, $size);
        $pdf->MultiCell($width, $height, $text, 0, $align, false, 0, $x, $y);
    }
}
