<?php

namespace App\Services;

use TCPDF;

class FeedbackPdfService
{
    /**
     * @param  array<int, array{id: int, name: string, items: array<int, array{id: int, description: string}>}>  $dimensions
     * @param  array<int, array{id: int, name: string}>  $questions
     * @param  array<int, array{id: int, name: string, value: string|int}>  $ratings
     * @param  array<string, mixed>|null  $client
     * @param  array<int|string, mixed>|null  $responseRatings
     * @param  array<int|string, string>|null  $responseAnswers
     */
    public function render(
        array $dimensions,
        array $questions,
        array $ratings,
        ?array $client = null,
        ?array $responseRatings = null,
        ?array $responseAnswers = null,
    ): string {
        $pdf = new NumberedPdf('P', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator((string) config('app.name'));
        $pdf->SetTitle('Customer Satisfaction Feedback');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(24, 24, 24);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $margin = 24.0;
        $width = $pdf->getPageWidth() - ($margin * 2);
        $bottom = $pdf->getPageHeight() - 48.0;
        $y = $this->drawDocumentHeader($pdf, $margin, $width);
        $y = $this->drawProfile($pdf, $margin, $width, $y + 16, $client);
        $y += 16;

        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetXY($margin, $y);
        $pdf->MultiCell($width, 14, 'We value your opinion! Please rate your experience with us, 5 being the highest. Thank you!', 0, 'L');
        $y += 20;

        $columnWidths = $this->columnWidths($width, count($ratings));
        $y = $this->drawRatingsHeader($pdf, $margin, $y, $columnWidths, $ratings);

        foreach ($dimensions as $dimension) {
            $rowHeights = array_map(
                fn (array $item): float => $this->rowHeight($pdf, $item['description'], $columnWidths['description']),
                $dimension['items'],
            );
            $dimensionHeight = array_sum($rowHeights) ?: 24.0;

            if ($y + $dimensionHeight > $bottom && $y > 100) {
                $pdf->AddPage();
                $y = $this->drawRatingsHeader($pdf, $margin, 24, $columnWidths, $ratings);
            }

            $this->drawDimension($pdf, $margin, $y, $dimension, $rowHeights, $columnWidths, $ratings, $responseRatings);
            $y += $dimensionHeight;
        }

        foreach ($questions as $question) {
            $questionHeight = 78.0;

            if ($y + $questionHeight + 12 > $bottom) {
                $pdf->AddPage();
                $y = 24.0;
            } else {
                $y += 10;
            }

            $this->drawQuestion($pdf, $margin, $y, $width, $question['name'], $questionHeight, $responseAnswers[$question['id']] ?? null);
            $y += $questionHeight;
        }

        if ($y + 48 > $bottom) {
            $pdf->AddPage();
            $y = 24.0;
        }

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Line($margin, $y + 12, $margin + $width, $y + 12);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY($margin, $y + 16);
        $pdf->Cell($width, 10, 'Thank you for taking the time to share your feedback.', 0, 0, 'C');

        return $pdf->Output('customer-satisfaction-feedback.pdf', 'S');
    }

    private function drawDocumentHeader(TCPDF $pdf, float $x, float $width): float
    {
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x + $width - 150, 20);
        $pdf->MultiCell(150, 20, "TSD Form No. 008\nRev 2/31-10-23", 0, 'R');

        $logoSize = 34.0;
        $logoGap = 22.0;
        $textWidth = 240.0;
        $groupWidth = $logoSize + $logoGap + $textWidth;
        $groupX = $x + (($width - $groupWidth) / 2);
        $textX = $groupX + $logoSize + $logoGap;

        $pdf->Image(resource_path('images/ptri-logo.jpg'), $groupX, 22, $logoSize, $logoSize);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($textX, 27);
        $pdf->Cell($textWidth, 13, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($textX, 41);
        $pdf->Cell($textWidth, 12, 'Technical Services Division', 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($textX, 52);
        $pdf->Cell($textWidth, 11, 'Gen. Santos Ave., Bicutan, Taguig City', 0, 0, 'C');

        $pdf->SetDrawColor(15, 23, 42);
        $pdf->SetLineWidth(0.75);
        $pdf->Line($x, 68, $x + $width, 68);
        $pdf->SetLineWidth(0.2);

        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetXY($x, 74);
        $pdf->Cell($width, 20, 'CUSTOMER  SATISFACTION  FEEDBACK', 0, 0, 'C');

        $pdf->SetLineWidth(0.75);
        $pdf->Line($x, 98, $x + $width, 98);
        $pdf->SetLineWidth(0.2);

        return 98.0;
    }

    /** @param  array<string, mixed>|null  $client */
    private function drawProfile(TCPDF $pdf, float $x, float $width, float $y, ?array $client = null): float
    {
        $rowHeight = 20.0;
        $half = $width / 2;
        $gender = isset($client['gender']) ? strtolower((string) $client['gender']) : null;
        $ageBracket = $client['age_bracket'] ?? null;

        $this->profileField($pdf, $x, $y, 'PSR No.', $half - 20, $client['reference_no'] ?? null);
        $this->profileField($pdf, $x + $half, $y, 'Date:', $half - 8, $client['date'] ?? null);
        $y += $rowHeight;

        $this->profileField($pdf, $x, $y, "Customer's Name (optional):", $half + 40, $client['fullname'] ?? null);
        $this->profileField($pdf, $x + $half + 48, $y, 'Company/School:', $half - 56, $client['company_or_school'] ?? null);
        $y += $rowHeight;

        $this->profileField($pdf, $x, $y, 'Address:', $half + 40, $client['address'] ?? null);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x + $half + 48, $y + 5, 'Gender:');
        $cursor = $x + $half + 90;
        foreach (['Male', 'Female'] as $label) {
            $cursor = $this->checkboxLabel($pdf, $cursor, $y + 4, $label, $gender === strtolower($label)) + 14;
        }
        $y += $rowHeight;

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x, $y + 5, 'Age:');
        $cursor = $x + 24;
        foreach (['less than 20 yrs old', '21-30 yrs old', '31-50 yrs old', '51-59 yrs old', '60 yrs old and above'] as $label) {
            $cursor = $this->checkboxLabel($pdf, $cursor, $y + 4, $label, $ageBracket === $label) + 12;
        }
        $y += $rowHeight;

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x, $y + 5, 'Type of Service:');
        $cursor = $x + 72;
        foreach (['Spinning', 'Weaving', 'Finishing'] as $label) {
            $cursor = $this->checkboxLabel($pdf, $cursor, $y + 4, $label) + 16;
        }
        $y += $rowHeight;

        return $y;
    }

    private function profileField(TCPDF $pdf, float $x, float $y, string $label, float $lineWidth, ?string $value = null): void
    {
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x, $y, $label);
        $labelWidth = $pdf->GetStringWidth($label) + 4;

        if ($value !== null && $value !== '') {
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text($x + $labelWidth, $y, $value);
        }

        $pdf->SetDrawColor(15, 23, 42);
        $pdf->Line($x + $labelWidth, $y + 10, $x + $lineWidth, $y + 10);
    }

    private function checkboxLabel(TCPDF $pdf, float $x, float $y, string $label, bool $checked = false): float
    {
        $pdf->SetDrawColor(15, 23, 42);

        if ($checked) {
            $pdf->SetFillColor(15, 23, 42);
            $pdf->Rect($x, $y, 8, 8, 'DF');
        } else {
            $pdf->Rect($x, $y, 8, 8);
        }

        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Text($x + 12, $y, $label);

        return $x + 12 + $pdf->GetStringWidth($label);
    }

    /** @return array{dimension: float, description: float, rating: float} */
    private function columnWidths(float $width, int $ratingCount): array
    {
        $dimension = $width * 0.15;
        $description = $width * 0.28;

        return [
            'dimension' => $dimension,
            'description' => $description,
            'rating' => ($width - $dimension - $description) / max($ratingCount, 1),
        ];
    }

    /** @param array<int, array{id: int, name: string, value: string|int}> $ratings */
    private function drawRatingsHeader(TCPDF $pdf, float $x, float $y, array $widths, array $ratings): float
    {
        $headerHeight = 34.0;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 8);
        $this->tableCell($pdf, $x, $y, $widths['dimension'], $headerHeight, 'Dimension', true, 'C', 'T');
        $cursor = $x + $widths['dimension'];
        $this->tableCell($pdf, $cursor, $y, $widths['description'], $headerHeight, 'Description', true, 'C', 'T');
        $cursor += $widths['description'];

        foreach ($ratings as $rating) {
            $this->tableCell($pdf, $cursor, $y, $widths['rating'], $headerHeight, "{$rating['value']}\n{$rating['name']}", true, 'C', 'T');
            $cursor += $widths['rating'];
        }

        if ($ratings === []) {
            $this->tableCell($pdf, $cursor, $y, $widths['rating'], $headerHeight, 'Rating', true, 'C', 'T');
        }

        return $y + $headerHeight;
    }

    private function rowHeight(TCPDF $pdf, string $description, float $descriptionWidth): float
    {
        $pdf->SetFont('helvetica', '', 8);

        return max(22.0, ($pdf->getNumLines($description, $descriptionWidth - 10) * 9) + 8);
    }

    /**
     * @param  array{id: int, name: string, items: array<int, array{id: int, description: string}>}  $dimension
     * @param  array<int, float>  $rowHeights
     * @param  array{dimension: float, description: float, rating: float}  $widths
     * @param  array<int, array{id: int, name: string, value: string|int}>  $ratings
     * @param  array<int|string, mixed>|null  $responseRatings
     */
    private function drawDimension(TCPDF $pdf, float $x, float $y, array $dimension, array $rowHeights, array $widths, array $ratings, ?array $responseRatings = null): void
    {
        $dimensionHeight = array_sum($rowHeights) ?: 24.0;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 7);
        $this->tableCell($pdf, $x, $y, $widths['dimension'], $dimensionHeight, strtoupper($dimension['name']), true, 'L');

        if ($dimension['items'] === []) {
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('helvetica', 'I', 8);
            $this->tableCell($pdf, $x + $widths['dimension'], $y, $widths['description'] + ($widths['rating'] * max(count($ratings), 1)), $dimensionHeight, 'No descriptions configured.', false, 'L');

            return;
        }

        $cursorY = $y;
        foreach ($dimension['items'] as $index => $item) {
            $height = $rowHeights[$index];
            $pdf->SetTextColor(51, 65, 85);
            $pdf->SetFont('helvetica', '', 8);
            $this->tableCell($pdf, $x + $widths['dimension'], $cursorY, $widths['description'], $height, $item['description'], false, 'L');
            $cursorX = $x + $widths['dimension'] + $widths['description'];
            $selectedValue = $responseRatings[$item['id']] ?? null;

            foreach ($ratings as $rating) {
                $this->tableCell($pdf, $cursorX, $cursorY, $widths['rating'], $height, '', false, 'C');
                $isSelected = $selectedValue !== null && (string) $selectedValue === (string) $rating['value'];
                $pdf->SetDrawColor(15, 23, 42);

                if ($isSelected) {
                    $pdf->SetFillColor(15, 23, 42);
                    $pdf->Rect($cursorX + ($widths['rating'] / 2) - 4, $cursorY + ($height / 2) - 4, 8, 8, 'DF');
                } else {
                    $pdf->Rect($cursorX + ($widths['rating'] / 2) - 4, $cursorY + ($height / 2) - 4, 8, 8);
                }

                $cursorX += $widths['rating'];
            }

            if ($ratings === []) {
                $this->tableCell($pdf, $cursorX, $cursorY, $widths['rating'], $height, '', false, 'C');
            }

            $cursorY += $height;
        }
    }

    private function drawQuestion(TCPDF $pdf, float $x, float $y, float $width, string $question, float $height, ?string $answer = null): void
    {
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->Rect($x, $y, $width, $height);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'BI', 9);
        $pdf->Text($x + 8, $y + 8, "{$question}:");

        if ($answer !== null && $answer !== '') {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetXY($x + 8, $y + 24);
            $pdf->MultiCell($width - 16, $height - 30, $answer, 0, 'L');
        }
    }

    private function tableCell(TCPDF $pdf, float $x, float $y, float $width, float $height, string $text, bool $fill, string $align, string $valign = 'M'): void
    {
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->Rect($x, $y, $width, $height, $fill ? 'DF' : 'D');
        $pdf->SetXY($x + 4, $y + 3);
        $pdf->MultiCell($width - 8, $height - 6, $text, 0, $align, false, 0, '', '', true, 0, false, true, $height - 6, $valign);
    }
}
