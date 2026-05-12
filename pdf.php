<?php
// ============================================================
// Point d'entrée pour l'export PDF (sans HTML wrapper)
// ============================================================

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Test.php';
require_once __DIR__ . '/classes/Photo.php';
require_once __DIR__ . '/classes/PDF.php';

Auth::init();

if (!Auth::check()) {
    header('HTTP/1.0 403 Forbidden');
    die('Accès non autorisé');
}

$testId = (int)($_GET['id'] ?? 0);
if (!$testId) {
    header('HTTP/1.0 400 Bad Request');
    die('ID de test invalide');
}

$test = Test::findById($testId);
if (!$test) {
    header('HTTP/1.0 404 Not Found');
    die('Test introuvable');
}

$photos = Photo::getByTestId($testId);

$pdf = new PDF();
$pdfContent = $pdf->generateReport($test, $photos);

$filename = 'Test_Reseau_' . ($test['liaison_id'] ?? 'N' . $testId) . '_' . date('Ymd') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfContent;
exit;
