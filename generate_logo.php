<?php
// ============================================================
// Générateur de logo PNG pour l'application
// Utilisé par le PDF et l'interface
// ============================================================

// Accès direct pour générer le fichier PNG
// Nécessite GD installé

$outputFile = __DIR__ . '/assets/images/logo.png';
$width = 200;
$height = 60;

if (!extension_loaded('gd')) {
    // Copier le SVG comme placeholder
    copy(__DIR__ . '/assets/images/logo.svg', $outputFile);
    echo "GD non disponible. Logo SVG copié.\n";
    exit;
}

// Création de l'image
$img = imagecreatetruecolor($width, $height);
imagesavealpha($img, true);

// Couleurs
$bgColor = imagecolorallocate($img, 30, 41, 59); // #1E293B
$textColor = imagecolorallocate($img, 255, 255, 255);
$subColor = imagecolorallocatealpha($img, 255, 255, 255, 50);
$borderColor = imagecolorallocatealpha($img, 255, 255, 255, 30);

imagefill($img, 0, 0, $bgColor);

// Rectangle icône réseau
imagerectangle($img, 168, 10, 190, 48, $borderColor);
imageline($img, 179, 18, 179, 22, $borderColor);
imageline($img, 179, 28, 179, 40, $borderColor);

// Texte
$fontFile = __DIR__ . '/assets/fonts/arial.ttf';
if (!file_exists($fontFile)) {
    // Fallback à une police système
    $fontFile = 'C:/Windows/Fonts/arial.ttf';
    if (!file_exists($fontFile)) {
        $fontFile = __DIR__ . '/vendor/tecnickcom/tcpdf/fonts/helvetica.php';
    }
}

if (file_exists($fontFile) && is_file($fontFile)) {
    putenv('GDFONTPATH=' . dirname($fontFile));
    @imagettftext($img, 14, 0, 14, 34, $textColor, basename($fontFile), 'HGR de Panzi');
    @imagettftext($img, 8, 0, 14, 48, $subColor, basename($fontFile), 'Service Technique - Reseau VDI');
} else {
    imagestring($img, 3, 14, 20, 'HGR de Panzi', $textColor);
    imagestring($img, 1, 14, 44, 'Service Technique - Reseau VDI', $subColor);
}

// Sauvegarde
imagepng($img, $outputFile);
imagedestroy($img);

echo "Logo PNG généré : $outputFile\n";
