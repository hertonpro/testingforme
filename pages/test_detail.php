<?php
// ============================================================
// Page : Détail d'un test
// ============================================================

$pageTitle = 'Détail du test';
$user = Auth::user();
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
$isOwner = ($test['created_by'] ?? 0) === ($user['id'] ?? 0);
$isAdmin = Auth::hasRole('administrateur');

// Actions POST
if (isPost()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && ($isOwner || $isAdmin)) {
        Test::delete($testId);
        flash('success', 'Test supprimé.');
        redirect('index.php?page=test_list');
    }

    if ($action === 'update_status' && $isAdmin) {
        $newStatus = $_POST['validation'] ?? 'en_attente';
        Test::update($testId, ['validation' => $newStatus]);
        flash('success', 'Statut mis à jour.');
        redirect('index.php?page=test_detail&id=' . $testId);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header--between">
    <div>
        <h1 class="page-title">Test : <?= h($test['liaison_id'] ?: 'N°' . $testId) ?></h1>
        <p class="page-desc">Créé par <?= h($test['createur_nom'] ?? 'Inconnu') ?> le <?= formatDatetime($test['created_at']) ?></p>
    </div>
    <div class="page-header__actions">
        <a href="index.php?page=test_form&id=<?= $testId ?>" class="btn btn--secondary">Modifier</a>
        <a href="pdf.php?id=<?= $testId ?>" class="btn btn--primary" target="_blank">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            PDF
        </a>
        <?php if ($isOwner || $isAdmin): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce test définitivement ?')">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn--danger">Supprimer</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Statut -->
<div class="detail-status">
    <span class="<?= validationBadge($test['validation'] ?? 'en_attente') ?> detail-status__badge">
        <?= validationLabel($test['validation'] ?? 'en_attente') ?>
    </span>
    <?php if ($isAdmin): ?>
    <form method="POST" class="detail-status__form">
        <input type="hidden" name="action" value="update_status">
        <select name="validation" class="form-input form-input--sm" onchange="this.form.submit()">
            <option value="en_attente" <?= ($test['validation'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
            <option value="valide" <?= ($test['validation'] ?? '') === 'valide' ? 'selected' : '' ?>>Validé</option>
            <option value="rework" <?= ($test['validation'] ?? '') === 'rework' ? 'selected' : '' ?>>Rework</option>
        </select>
    </form>
    <?php endif; ?>
</div>

<div class="detail-grid">
    <!-- Section 1: Identification -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">1. Identification</h2></div>
        <div class="card__body detail-fields">
            <div class="detail-field"><span class="detail-field__label">Site</span><span class="detail-field__value"><?= h($test['site'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Bâtiment</span><span class="detail-field__value"><?= h($test['batiment'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Service</span><span class="detail-field__value"><?= h($test['service'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Point A (Patch Panel)</span><span class="detail-field__value"><?= h($test['localisation_a'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Point B (Prise RJ45)</span><span class="detail-field__value"><?= h($test['localisation_b'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">ID Liaison</span><span class="detail-field__value"><?= h($test['liaison_id'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Catégorie</span><span class="detail-field__value"><?= h($test['cable_categorie'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Type liaison</span><span class="detail-field__value"><?= h($test['type_liaison'] ?? '-') ?></span></div>
        </div>
    </div>

    <!-- Section 2: Visuel -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">2. Contrôle visuel</h2></div>
        <div class="card__body">
            <?php
            $visuelItems = [
                'cheminement_conforme' => 'Cheminement conforme',
                'respect_courbure' => 'Respect courbure',
                'etiquetage_present' => 'Étiquetage',
                'boitier_fixe' => 'Boîtier fixé',
                'patch_panel_raccorde' => 'Patch panel raccordé',
                'absence_contrainte' => 'Absence contrainte',
            ];
            ?>
            <?php foreach ($visuelItems as $key => $label): ?>
            <div class="detail-field">
                <span class="detail-field__label"><?= $label ?></span>
                <span class="detail-field__value <?= ($test[$key] ?? 0) ? 'text--success' : 'text--danger' ?>">
                    <?= ($test[$key] ?? 0) ? '✓ Oui' : '✗ Non' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 3: Continuité -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">3. Continuité</h2></div>
        <div class="card__body">
            <h4 class="detail-subtitle">Paires</h4>
            <?php
            $contItems = [
                'continuite_paire_1_2' => 'Paire 1-2',
                'continuite_paire_3_6' => 'Paire 3-6',
                'continuite_paire_4_5' => 'Paire 4-5',
                'continuite_paire_7_8' => 'Paire 7-8',
            ];
            ?>
            <?php foreach ($contItems as $key => $label): ?>
            <div class="detail-field">
                <span class="detail-field__label"><?= $label ?></span>
                <span class="detail-field__value <?= ($test[$key] ?? 0) ? 'text--success' : 'text--danger' ?>">
                    <?= ($test[$key] ?? 0) ? '✓ OK' : '✗ NOK' ?>
                </span>
            </div>
            <?php endforeach; ?>

            <h4 class="detail-subtitle">Défauts</h4>
            <?php
            $defautItems = [
                'inversion_paires' => 'Inversion paires',
                'court_circuit' => 'Court-circuit',
                'circuit_ouvert' => 'Circuit ouvert',
                'split_pair' => 'Split pair',
            ];
            ?>
            <?php foreach ($defautItems as $key => $label): ?>
            <div class="detail-field">
                <span class="detail-field__label"><?= $label ?></span>
                <span class="detail-field__value <?= ($test[$key] ?? 0) ? 'text--danger' : 'text--success' ?>">
                    <?= ($test[$key] ?? 0) ? '✓ Détecté' : '✗ Non détecté' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 4: Longueur -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">4. Longueur</h2></div>
        <div class="card__body">
            <div class="detail-field"><span class="detail-field__label">Longueur estimée</span><span class="detail-field__value"><?= h($test['longueur_estimee'] ?? '-') ?> m</span></div>
            <div class="detail-field">
                <span class="detail-field__label">Conforme Cat 6A</span>
                <span class="detail-field__value <?= ($test['longueur_conforme'] ?? 0) ? 'text--success' : 'text--danger' ?>">
                    <?= ($test['longueur_conforme'] ?? 0) ? '✓ Oui' : '✗ Non' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Section 5: Performance -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">5. Performance (iPerf3)</h2></div>
        <div class="card__body">
            <div class="detail-field"><span class="detail-field__label">Débit descendant</span><span class="detail-field__value"><?= h($test['debit_descendant'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Débit montant</span><span class="detail-field__value"><?= h($test['debit_montant'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Latence</span><span class="detail-field__value"><?= h($test['latence'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Perte paquets</span><span class="detail-field__value"><?= h($test['perte_paquets'] ?? '-') ?></span></div>
            <div class="detail-field">
                <span class="detail-field__label">Stabilité</span>
                <span class="detail-field__value <?= ($test['stabilite_lien'] ?? 0) ? 'text--success' : 'text--danger' ?>">
                    <?= ($test['stabilite_lien'] ?? 0) ? '✓ Stable' : '✗ Instable' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Section 6: Connectivité -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">6. Connectivité réseau</h2></div>
        <div class="card__body">
            <?php
            $connItems = [
                'ip_obtenue' => 'Adresse IP',
                'acces_passerelle' => 'Passerelle',
                'acces_serveur_local' => 'Serveur local',
                'acces_internet' => 'Internet',
            ];
            ?>
            <?php foreach ($connItems as $key => $label): ?>
            <div class="detail-field">
                <span class="detail-field__label"><?= $label ?></span>
                <span class="detail-field__value <?= ($test[$key] ?? 0) ? 'text--success' : 'text--danger' ?>">
                    <?= ($test[$key] ?? 0) ? '✓ OK' : '✗ NOK' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 7: Électrique -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">7. Contrôle électrique</h2></div>
        <div class="card__body">
            <div class="detail-field"><span class="detail-field__label">Interférences</span><span class="detail-field__value <?= ($test['interferences_electriques'] ?? 0) ? 'text--danger' : 'text--success' ?>"><?= ($test['interferences_electriques'] ?? 0) ? 'Oui' : 'Non' ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Proximité énergie</span><span class="detail-field__value <?= ($test['proximite_cable_energie'] ?? 0) ? 'text--danger' : 'text--success' ?>"><?= ($test['proximite_cable_energie'] ?? 0) ? 'Oui' : 'Non' ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Terre fonctionnelle</span><span class="detail-field__value"><?= h($test['terre_fonctionnelle'] ?? 'N/A') ?></span></div>
        </div>
    </div>

    <!-- Section 8: Conformité -->
    <div class="card detail-card">
        <div class="card__header"><h2 class="card__title">8. Conformité</h2></div>
        <div class="card__body">
            <div class="detail-field"><span class="detail-field__label">Conforme normes</span><span class="detail-field__value <?= ($test['conforme_normes'] ?? 0) ? 'text--success' : 'text--danger' ?>"><?= ($test['conforme_normes'] ?? 0) ? '✓ Oui' : '✗ Non' ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Validée production</span><span class="detail-field__value <?= ($test['validee_production'] ?? 0) ? 'text--success' : 'text--danger' ?>"><?= ($test['validee_production'] ?? 0) ? '✓ Oui' : '✗ Non' ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Rework nécessaire</span><span class="detail-field__value <?= ($test['rework_necessaire'] ?? 0) ? 'text--danger' : 'text--success' ?>"><?= ($test['rework_necessaire'] ?? 0) ? '✓ Oui' : '✗ Non' ?></span></div>
        </div>
    </div>
</div>

<!-- Observations -->
<?php if (!empty($test['observations'])): ?>
<div class="card">
    <div class="card__header"><h2 class="card__title">9. Observations</h2></div>
    <div class="card__body">
        <p class="detail-obs"><?= nl2br(h($test['observations'])) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Photos -->
<?php if (!empty($photos)): ?>
<div class="card">
    <div class="card__header"><h2 class="card__title">Photos (<?= count($photos) ?>)</h2></div>
    <div class="card__body">
        <div class="gallery">
            <?php foreach ($photos as $photo): ?>
            <div class="gallery__item">
                <a href="<?= h($photo['chemin_image']) ?>" target="_blank">
                    <img src="<?= h($photo['chemin_image']) ?>" alt="Photo test" loading="lazy" class="gallery__img">
                </a>
                <?php if (!empty($photo['description'])): ?>
                <p class="gallery__desc"><?= h($photo['description']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Section 10-11: Validation -->
<div class="card">
    <div class="card__header"><h2 class="card__title">Validation</h2></div>
    <div class="card__body">
        <div class="form-row">
            <div class="detail-field"><span class="detail-field__label">Test réalisé par</span><span class="detail-field__value"><?= h($test['test_realise_par'] ?? '-') ?></span></div>
            <div class="detail-field"><span class="detail-field__label">Équipe</span><span class="detail-field__value"><?= h($test['equipe_validation'] ?? '-') ?></span></div>
        </div>
        <div class="detail-field"><span class="detail-field__label">Date du test</span><span class="detail-field__value"><?= formatDate($test['date_test']) ?></span></div>

        <div class="signatures">
            <div class="signature-box">
                <span class="signature-box__label">Technicien test</span>
                <div class="signature-box__line"><?= h($test['signature_technicien'] ?? '_______________________') ?></div>
            </div>
            <div class="signature-box">
                <span class="signature-box__label">Superviseur technique</span>
                <div class="signature-box__line"><?= h($test['signature_superviseur'] ?? '_______________________') ?></div>
            </div>
            <div class="signature-box">
                <span class="signature-box__label">Service technique HGR</span>
                <div class="signature-box__line"><?= h($test['signature_service'] ?? '_______________________') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="form-actions">
    <a href="pdf.php?id=<?= $testId ?>" class="btn btn--primary btn--lg btn--block" target="_blank">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Télécharger le rapport PDF
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
