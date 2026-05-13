<?php
// ============================================================
// Page : Paramètres de l'application (Administrateur)
// ============================================================

Auth::requireRole('administrateur');
$pageTitle = 'Paramètres';

$configFile = __DIR__ . '/../config/app.php';
$logoPath = __DIR__ . '/../assets/images/logo.png';
$logoSvg = __DIR__ . '/../assets/images/logo.svg';

// Traitement formulaire
if (isPost()) {
    $action = $_POST['action'] ?? '';

    // Sauvegarde des textes
    if ($action === 'save_text') {
        $data = $_POST;
        $org = trim($data['app_org'] ?? APP_ORG);
        $subtitle = trim($data['app_org_subtitle'] ?? APP_ORG_SUBTITLE);
        $docTitle = trim($data['app_doc_title'] ?? APP_DOC_TITLE);
        $siteDefault = trim($data['app_site_default'] ?? APP_SITE_DEFAULT);

        if (empty($org) || empty($docTitle)) {
            flash('error', 'Le nom de l\'organisation et le titre du document sont obligatoires.');
        } else {
            $content = file_get_contents($configFile);
            $content = preg_replace("/define\('APP_ORG',\s*'[^']*'/", "define('APP_ORG', '" . str_replace("'", "\\'", $org) . "'", $content);
            $content = preg_replace("/define\('APP_ORG_SUBTITLE',\s*'[^']*'/", "define('APP_ORG_SUBTITLE', '" . str_replace("'", "\\'", $subtitle) . "'", $content);
            $content = preg_replace("/define\('APP_DOC_TITLE',\s*'[^']*'/", "define('APP_DOC_TITLE', '" . str_replace("'", "\\'", $docTitle) . "'", $content);
            $content = preg_replace("/define\('APP_SITE_DEFAULT',\s*'[^']*'/", "define('APP_SITE_DEFAULT', '" . str_replace("'", "\\'", $siteDefault) . "'", $content);

            if (file_put_contents($configFile, $content)) {
                flash('success', 'Paramètres textes mis à jour.');
            } else {
                flash('error', 'Erreur d\'écriture du fichier de configuration.');
            }
        }
        redirect('index.php?page=settings');
    }

    // Upload logo
    if ($action === 'save_logo' && !empty($_FILES['logo'])) {
        $file = $_FILES['logo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Erreur lors de l\'upload du logo.');
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            flash('error', 'Le logo ne doit pas dépasser 2 Mo.');
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($ext, $allowed)) {
                flash('error', 'Format non autorisé (jpg, png, webp, gif).');
            } else {
                // Redimensionner si nécessaire et convertir en PNG
                $info = getimagesize($file['tmp_name']);
                if ($info) {
                    list($w, $h) = $info;
                    $maxW = 300;
                    $maxH = 100;
                    $ratio = min($maxW / $w, $maxH / $h, 1);

                    $newW = (int)round($w * $ratio);
                    $newH = (int)round($h * $ratio);

                    $src = match ($info['mime']) {
                        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                        'image/png'  => imagecreatefrompng($file['tmp_name']),
                        'image/webp' => imagecreatefromwebp($file['tmp_name']),
                        'image/gif'  => imagecreatefromgif($file['tmp_name']),
                        default      => false,
                    };

                    if ($src) {
                        $dst = imagecreatetruecolor($newW, $newH);
                        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
                        imagepng($dst, $logoPath);
                        imagedestroy($src);
                        imagedestroy($dst);
                        flash('success', 'Logo mis à jour avec succès.');
                    } else {
                        flash('error', 'Impossible de traiter l\'image.');
                    }
                }
            }
        }
        redirect('index.php?page=settings');
    }

    // Suppression logo
    if ($action === 'delete_logo') {
        if (file_exists($logoPath)) {
            @unlink($logoPath);
            flash('success', 'Logo supprimé.');
        }
        redirect('index.php?page=settings');
    }
}

$logoExists = file_exists($logoPath) && filesize($logoPath) > 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Paramètres</h1>
    <p class="page-desc">Personnalisez les informations de votre organisation</p>
</div>

<!-- Logo -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Logo de l'entreprise</h2>
    </div>
    <div class="card__body">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-bottom:16px">
            <div style="background:var(--color-bg);border-radius:var(--radius-sm);padding:12px;min-width:100px;text-align:center">
                <?php if ($logoExists): ?>
                    <img src="assets/images/logo.png?t=<?= filemtime($logoPath) ?>" alt="Logo" style="max-height:60px;max-width:200px">
                <?php else: ?>
                    <div style="width:100px;height:60px;display:flex;align-items:center;justify-content:center;color:var(--color-text-light);font-size:0.75rem;border:2px dashed var(--color-border);border-radius:4px">Aucun logo</div>
                <?php endif; ?>
            </div>

            <form method="POST" action="index.php?page=settings" enctype="multipart/form-data" style="flex:1">
                <input type="hidden" name="action" value="save_logo">
                <div class="form-group">
                    <label class="form-label" for="logo">Choisir un logo</label>
                    <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input" style="padding:8px">
                    <small style="color:var(--color-text-light);font-size:0.75rem">JPEG, PNG, WebP ou GIF — Max 2 Mo — Redimensionné automatiquement</small>
                </div>
                <button type="submit" class="btn btn--primary" style="margin-top:8px">Uploader le logo</button>
            </form>

            <?php if ($logoExists): ?>
            <form method="POST" action="index.php?page=settings" style="display:inline">
                <input type="hidden" name="action" value="delete_logo">
                <button type="submit" class="btn btn--danger" onclick="return confirm('Supprimer le logo ?')">Supprimer</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Textes -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Informations générales</h2>
    </div>
    <div class="card__body">
        <p style="color: var(--color-text-secondary); font-size:0.85rem; margin-bottom:16px;">
            Ces informations apparaissent sur l'interface et les rapports PDF.
        </p>

        <form method="POST" action="index.php?page=settings">
            <input type="hidden" name="action" value="save_text">
            <div class="form-group">
                <label class="form-label" for="app_org">Organisation / Entreprise</label>
                <input type="text" name="app_org" id="app_org" class="form-input" value="<?= h(APP_ORG) ?>" required>
                <small style="color:var(--color-text-light);font-size:0.75rem">Ex: HGR de Panzi, Clinique XYZ, etc.</small>
            </div>

            <div class="form-group" style="margin-top:12px">
                <label class="form-label" for="app_org_subtitle">Sous-titre / Service</label>
                <input type="text" name="app_org_subtitle" id="app_org_subtitle" class="form-input" value="<?= h(APP_ORG_SUBTITLE) ?>">
                <small style="color:var(--color-text-light);font-size:0.75rem">Ex: Service Technique — Réseau VDI</small>
            </div>

            <div class="form-group" style="margin-top:12px">
                <label class="form-label" for="app_doc_title">Titre du document PDF</label>
                <input type="text" name="app_doc_title" id="app_doc_title" class="form-input" value="<?= h(APP_DOC_TITLE) ?>" required>
                <small style="color:var(--color-text-light);font-size:0.75rem">Ex: FICHE DE TEST UNITAIRE — LIAISON RÉSEAU</small>
            </div>

            <div class="form-group" style="margin-top:12px">
                <label class="form-label" for="app_site_default">Site par défaut dans les formulaires</label>
                <input type="text" name="app_site_default" id="app_site_default" class="form-input" value="<?= h(APP_SITE_DEFAULT) ?>">
                <small style="color:var(--color-text-light);font-size:0.75rem">Valeur pré-remplie dans le champ "Site"</small>
            </div>

            <div class="form-actions">
                <button type="submit" name="save" class="btn btn--primary btn--lg btn--block">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Aperçu -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Aperçu</h2>
    </div>
    <div class="card__body" style="background:var(--color-bg);border-radius:var(--radius-sm);padding:20px;text-align:center">
        <?php if ($logoExists): ?>
        <img src="assets/images/logo.png?t=<?= filemtime($logoPath) ?>" alt="Logo" style="max-height:50px;max-width:200px;margin-bottom:12px">
        <?php endif; ?>
        <div style="font-weight:700;font-size:1.1rem;color:var(--color-primary)"><?= h(APP_ORG) ?></div>
        <div style="font-size:0.85rem;color:var(--color-text-secondary);margin-top:4px"><?= h(APP_ORG_SUBTITLE) ?></div>
        <div style="font-size:0.75rem;color:var(--color-text-light);margin-top:8px;font-weight:600"><?= h(APP_DOC_TITLE) ?></div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
