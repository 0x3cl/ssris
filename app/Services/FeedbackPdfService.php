<?php

namespace App\Services;

use TCPDF;

class FeedbackPdfService
{
    /**
     * @param  array<int, array{id: int, name: string, items: array<int, array{id: int, description: string}>}>  $dimensions
     * @param  array<int, array{id: int, name: string}>  $questions
     * @param  array<int, array{id: int, name: string, value: string|int}>  $ratings
     */
    public function render(array $dimensions, array $questions, array $ratings): string
    {
        $pdf = new TCPDF('L', 'pt', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator((string) config('app.name'));
        $pdf->SetTitle('Customer Satisfaction Feedback');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(24, 24, 24);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $margin = 24.0;
        $width = $pdf->getPageWidth() - ($margin * 2);
        $bottom = $pdf->getPageHeight() - $margin;
        $y = $this->drawDocumentHeader($pdf, $margin, $width);
        $y = $this->drawProfile($pdf, $margin, $width, $y + 16);
        $y += 16;

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetXY($margin, $y);
        $pdf->MultiCell($width, 14, 'We value your opinion. Please rate each statement by marking one response, with the highest rating indicating your highest level of satisfaction.', 0, 'L');
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

            $this->drawDimension($pdf, $margin, $y, $dimension, $rowHeights, $columnWidths, $ratings);
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

            $this->drawQuestion($pdf, $margin, $y, $width, $question['name'], $questionHeight);
            $y += $questionHeight;
        }

        if ($y + 28 > $bottom) {
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
        $pdf->SetTextColor(7, 85, 158);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($x, 26);
        $pdf->Cell($width, 13, 'PHILIPPINE TEXTILE RESEARCH INSTITUTE', 0, 0, 'C');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY($x, 50);
        $pdf->Cell($width, 22, 'CUSTOMER SATISFACTION FEEDBACK', 0, 0, 'C');
        $pdf->SetDrawColor(7, 85, 158);
        $pdf->SetLineWidth(1.5);
        $pdf->Line($x, 84, $x + $width, 84);
        $pdf->SetLineWidth(0.2);

        return 84.0;
    }

    private function drawProfile(TCPDF $pdf, float $x, float $width, float $y): float
    {
        $height = 88.0;
        $half = $width / 2;
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->Rect($x, $y, $width, $height);
        foreach ([20.0, 40.0, 60.0, 74.0] as $offset) {
            $pdf->Line($x, $y + $offset, $x + $width, $y + $offset);
        }
        $pdf->Line($x + $half, $y, $x + $half, $y + 20);

        $this->profileField($pdf, $x + 8, $y + 5, 'PSR No.:', 235);
        $this->profileField($pdf, $x + $half + 8, $y + 5, 'Date:', 235);
        $this->profileField($pdf, $x + 8, $y + 25, "Customer's Name (optional):", 275);
        $this->profileField($pdf, $x + 360, $y + 25, 'Company/School:', 175);
        $this->profileField($pdf, $x + 8, $y + 45, 'Address:', 405);

        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x + 430, $y + 45, 'Gender:');
        $this->checkboxLabel($pdf, $x + 475, $y + 44, 'Male');
        $this->checkboxLabel($pdf, $x + 525, $y + 44, 'Female');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x + 8, $y + 64, 'Age:');
        $cursor = $x + 30;
        foreach (['Less than 20 years old', '21-30 years old', '31-40 years old', '41-60 years old', 'Above 60 years old'] as $label) {
            $cursor = $this->checkboxLabel($pdf, $cursor, $y + 63, $label) + 10;
        }

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x + 8, $y + 78, 'Type of Service:');
        $cursor = $x + 78;
        foreach (['R&D Services', 'Laboratory Services', 'Textile Processing'] as $label) {
            $cursor = $this->checkboxLabel($pdf, $cursor, $y + 77, $label) + 14;
        }

        return $y + $height;
    }

    private function profileField(TCPDF $pdf, float $x, float $y, string $label, float $lineWidth): void
    {
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Text($x, $y, $label);
        $labelWidth = $pdf->GetStringWidth($label) + 4;
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->Line($x + $labelWidth, $y + 10, $x + $lineWidth, $y + 10);
    }

    private function checkboxLabel(TCPDF $pdf, float $x, float $y, string $label): float
    {
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->Rect($x, $y, 8, 8);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Text($x + 12, $y, $label);

        return $x + 12 + $pdf->GetStringWidth($label);
    }

    /** @return array{dimension: float, description: float, rating: float} */
    private function columnWidths(float $width, int $ratingCount): array
    {
        $dimension = 100.0;
        $description = 225.0;

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
        $pdf->SetFillColor(7, 85, 158);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $this->tableCell($pdf, $x, $y, $widths['dimension'], $headerHeight, 'Dimension', true, 'C');
        $cursor = $x + $widths['dimension'];
        $this->tableCell($pdf, $cursor, $y, $widths['description'], $headerHeight, 'Description', true, 'C');
        $cursor += $widths['description'];

        foreach ($ratings as $rating) {
            $this->tableCell($pdf, $cursor, $y, $widths['rating'], $headerHeight, "{$rating['value']}\n{$rating['name']}", true, 'C');
            $cursor += $widths['rating'];
        }

        if ($ratings === []) {
            $this->tableCell($pdf, $cursor, $y, $widths['rating'], $headerHeight, 'Rating', true, 'C');
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
     */
    private function drawDimension(TCPDF $pdf, float $x, float $y, array $dimension, array $rowHeights, array $widths, array $ratings): void
    {
        $dimensionHeight = array_sum($rowHeights) ?: 24.0;
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(7, 85, 158);
        $pdf->SetFont('helvetica', 'B', 8);
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

            foreach ($ratings as $rating) {
                $this->tableCell($pdf, $cursorX, $cursorY, $widths['rating'], $height, '', false, 'C');
                $pdf->SetDrawColor(71, 85, 105);
                $pdf->Circle($cursorX + ($widths['rating'] / 2), $cursorY + ($height / 2), 4);
                $cursorX += $widths['rating'];
            }

            if ($ratings === []) {
                $this->tableCell($pdf, $cursorX, $cursorY, $widths['rating'], $height, '', false, 'C');
            }

            $cursorY += $height;
        }
    }

    private function drawQuestion(TCPDF $pdf, float $x, float $y, float $width, string $question, float $height): void
    {
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->Rect($x, $y, $width, $height);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'BI', 9);
        $pdf->Text($x + 8, $y + 8, "{$question}:");
    }

    private function tableCell(TCPDF $pdf, float $x, float $y, float $width, float $height, string $text, bool $fill, string $align): void
    {
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->Rect($x, $y, $width, $height, $fill ? 'DF' : 'D');
        $pdf->SetXY($x + 4, $y + 3);
        $pdf->MultiCell($width - 8, $height - 6, $text, 0, $align, false, 0, '', '', true, 0, false, true, $height - 6, 'M');
    }
}
