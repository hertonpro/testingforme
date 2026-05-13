<?php
// ============================================================
// Export ZIP de tous les PDF d'un chantier
// ============================================================

Auth::requireAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('HTTP/1.0 400 Bad Request'); die('ID requis'); }

$chantier = Chantier::findById($id);
if (!$chantier) { header('HTTP/1.0 404 Not Found'); die('Chantier introuvable'); }

$tests = Chantier::getTests($id);
if (empty($tests)) { header('HTTP/1.0 404 Not Found'); die('Aucun test sur ce chantier'); }

require_once __DIR__ . '/../classes/PDF.php';

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'chantier_') . '.zip';

if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
    die('Erreur création ZIP');
}

foreach ($tests as $test) {
    $photos = Photo::getByTestId($test['id']);

    $pdf = new PDF();
    try {
        $pdfContent = $pdf->generateReport($test, $photos);
    } catch (Exception $e) {
        continue;
    }

    $filename = 'Test_' . ($test['liaison_id'] ?? 'N' . $test['id']) . '_' . date('Ymd', strtotime($test['date_test'] ?? 'now')) . '.pdf';
    $zip->addFromString($filename, $pdfContent);
}

$zip->close();

$zipName = 'Chantier_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $chantier['nom']) . '_' . date('Ymd') . '.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($tmpFile);
unlink($tmpFile);
exit;
