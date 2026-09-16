<?php

namespace App\Services;

use TCPDF;

/**
 * A TCPDF instance that prints a "Page X of Y" footer, centered at the
 * bottom of every page, including pages added mid-render via AddPage().
 */
class NumberedPdf extends TCPDF
{
    public function Footer(): void
    {
        $this->SetY(-30);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}
