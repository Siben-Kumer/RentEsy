<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('includes/fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica','B',16);
$pdf->Cell(40,10,'Hello World!');
$pdf->Output();
?>