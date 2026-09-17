<?php

namespace App\Services;

use TCPDF;

class PlantTourRequestPdfService
{
    /** @param array<string, mixed> $request */
    public function render(array $request): string
    {
        $pdf = new NumberedPdf('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetTitle('Plant Tour Request');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCellPaddings(0, 0, 0, 0);

        $pdf->AddPage();
        $this->form($pdf, 88, 42, $request);

        return $pdf->Output('', 'S');
    }

    /** @param array<string, mixed> $request */
    private function form(TCPDF $pdf, float $x, float $y, array $request): void
    {
        $width = 420.0;
        $client = $request['client'];
        $tour = $request['tour'];

        $pdf->Image(resource_path('images/ptri-logo.jpg'), $x, $y, 44, 44);
        $this->text($pdf, $x + 56, $y + 2, $width - 56, 14, 'Department of Science and Technology', 'B', 10);
        $this->text($pdf, $x + 56, $y + 15, $width - 56, 15, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 'B', 12);
        $this->text($pdf, $x + 56, $y + 30, $width - 56, 14, 'Technology Transfer, Information and Promotion Staff', 'B', 9);
        $this->text($pdf, $x, $y + 70, $width, 20, 'PLANT TOUR REQUEST FORM', 'B', 13, 'C');

        $this->labelValue($pdf, $x, $y + 102, 'Date/Time:', $request['created_at']);
        $this->labelValue($pdf, $x, $y + 120, 'For:', $client['company_or_school'] ?: $client['fullname']);
        $this->text($pdf, $x, $y + 150, 156, 16, 'This is to inform you that', '', 10);
        $this->text($pdf, $x + 156, $y + 150, 126, 16, $client['fullname'], 'BU', 10, 'C');
        $this->text($pdf, $x + 292, $y + 150, 128, 16, 'wish to visit your facilities.', '', 10);

        $this->text($pdf, $x, $y + 178, 180, 16, 'Testing Laboratories', 'B', 10);
        $this->text($pdf, $x + 210, $y + 178, 180, 16, 'TSD Pilot Plant', 'B', 10);
        $this->check($pdf, $x, $y + 198, in_array('Physical', $tour['testing_lab'], true), 'Physical');
        $this->check($pdf, $x, $y + 214, in_array('Chemical', $tour['testing_lab'], true), 'Chemical');
        $this->check($pdf, $x + 210, $y + 198, in_array('Spinning', $tour['pilot_plant'], true), 'Spinning');
        $this->check($pdf, $x + 330, $y + 198, in_array('Knitting', $tour['pilot_plant'], true), 'Knitting');
        $this->check($pdf, $x + 210, $y + 214, in_array('Weaving', $tour['pilot_plant'], true), 'Weaving');
        $this->check($pdf, $x + 330, $y + 214, in_array('Finishing', $tour['pilot_plant'], true), 'Finishing');
        $this->check($pdf, $x + 210, $y + 230, in_array('Powerloom', $tour['pilot_plant'], true), 'Powerloom');
        $this->check($pdf, $x + 330, $y + 230, in_array('Handloom', $tour['pilot_plant'], true), 'Handloom');

        $this->text($pdf, $x, $y + 250, $width, 16, 'Others:', 'B', 10);
        foreach ([
            'TELA Gallery, Textile Design and Innovation Hub',
            'Textile Product Development Center',
            'Technology Business Incubation Center',
            'Natural Fiber Utilization Section',
        ] as $index => $label) {
            $rowY = $y + 268 + ($index * 15);
            $this->text($pdf, $x, $rowY, 26, 16, in_array($label, $tour['others'], true) ? 'X' : '___', 'B', 9, 'C');
            $this->text($pdf, $x + 30, $rowY, 380, 16, $label, 'B', 9);
        }

        $this->labelValue($pdf, $x, $y + 336, 'On', trim($tour['visit_date'].' '.$tour['visit_time']));
        $this->text($pdf, $x, $y + 354, 102, 16, 'Please expect', '', 10);
        $this->underlined($pdf, $x + 102, $y + 354, 28, (string) ($tour['no_persons'] ?? ''));
        $this->text($pdf, $x + 134, $y + 354, 28, 16, 'pax', '', 10);
        $this->underlined($pdf, $x + 162, $y + 354, 28, (string) ($tour['no_groups'] ?? ''));
        $this->text($pdf, $x + 194, $y + 354, 170, 16, 'group(s) / batch(es)', '', 10);
        $this->text($pdf, $x, $y + 382, $width, 16, 'The objective of this visit is:', '', 10);
        $this->text($pdf, $x, $y + 400, $width, 28, $tour['visit_objectives'] ?: $tour['technology_assistance'], 'BU', 10);

        $this->text($pdf, $x, $y + 444, 150, 16, 'Prepared by:', 'B', 10);
        $this->text($pdf, $x + 210, $y + 444, 150, 16, 'Noted by:', 'B', 10);
        $this->signature($pdf, $x, $y + 468, 170, $tour['prepared_by'], 'PTA V, OD-TIPS');
        $this->signature($pdf, $x + 210, $y + 468, 170, $tour['noted_by'], 'Head, OD-TIPS');
        $this->signature($pdf, $x, $y + 550, 170, $tour['conforme_primary'], 'Chief, TSD | Date Signed');
        $this->signature($pdf, $x + 210, $y + 550, 170, $tour['conforme_secondary'], "Chief, FAD | Date Signed\nChief, RDD");
        $this->text($pdf, $x, $y + 600, $width, 12, 'Remarks: '.($tour['remarks'] ?? ''), 'U', 8);
    }

    private function labelValue(TCPDF $pdf, float $x, float $y, string $label, string $value): void
    {
        $this->text($pdf, $x, $y, 72, 16, $label, '', 10);
        $this->underlined($pdf, $x + 76, $y, 300, $value);
    }

    private function underlined(TCPDF $pdf, float $x, float $y, float $width, string $value): void
    {
        $this->text($pdf, $x, $y, $width, 16, $value, 'BU', 10);
    }

    private function check(TCPDF $pdf, float $x, float $y, bool $selected, string $label): void
    {
        $this->text($pdf, $x, $y, 26, 16, $selected ? 'X' : '___', 'B', 9, 'C');
        $this->text($pdf, $x + 30, $y, 185, 16, $label, 'B', 10);
    }

    private function signature(TCPDF $pdf, float $x, float $y, float $width, ?string $name, string $title): void
    {
        $this->text($pdf, $x, $y, $width, 16, $name ?? '', 'U', 10);
        $this->text($pdf, $x, $y + 16, $width, 30, $title, '', 10);
    }

    private function text(TCPDF $pdf, float $x, float $y, float $width, float $height, ?string $text, string $style = '', float $size = 9, string $align = 'L'): void
    {
        $pdf->SetFont('helvetica', $style, $size);
        $pdf->MultiCell($width, $height, $text ?? '', 0, $align, false, 0, $x, $y);
    }
}
