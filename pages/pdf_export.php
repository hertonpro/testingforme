<?php
// ============================================================
// Page : Export PDF d'un test
// ============================================================

Auth::requireAuth();

$testId = (int)($_GET['id'] ?? 0);
if (!$testId) {
    flash('error', 'ID de test invalide.');
    redirect('index.php?page=test_list');
}

$test = Test::findById($testId);
if (!$test) {
    flash('error', 'Test introuvable.');
    redirect('index.php?page=test_list');
}

$photos = Photo::getByTestId($testId);

// Génération du PDF
require_once __DIR__ . '/../classes/PDF.php';

$pdf = new PDF();
$pdfContent = $pdf->generateReport($test, $photos);

// Envoi au navigateur
$filename = 'Test_Reseau_' . ($test['liaison_id'] ?? 'N' . $testId) . '_' . date('Ymd') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfContent;
exit;
