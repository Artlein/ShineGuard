<?php
require_once 'dbconnect.php';
// Minimalist TCPDF test
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'ShineGuard TCPDF Test', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(10);
$pdf->MultiCell(0, 10, "If you see this, TCPDF is working perfectly.\n\nDatabase: " . ($conn ? 'Connected' : 'Failed'), 0, 'L');
$pdf->Output('test_output.pdf', 'I');
?>
