<?php
// ============================================================
// Page : Suppression d'une photo
// ============================================================

Auth::requireAuth();

$photoId = (int)($_GET['id'] ?? 0);
$testId = (int)($_GET['test_id'] ?? 0);

if (!$photoId || !$testId) {
    flash('error', 'Paramètres invalides.');
    redirect('index.php?page=test_list');
}

Photo::delete($photoId);
flash('success', 'Photo supprimée.');
redirect('index.php?page=test_detail&id=' . $testId);
