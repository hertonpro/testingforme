<?php
// ============================================================
// Page : Formulaire de test réseau
// ============================================================

$pageTitle = 'Nouveau test';
$user = Auth::user();
$testId = (int)($_GET['id'] ?? 0);
$isEdit = $testId > 0;
$test = null;
$photos = [];
$errors = [];

if ($isEdit) {
    $test = Test::findById($testId);
    if (!$test) {
        flash('error', 'Test introuvable.');
        redirect('index.php?page=test_list');
    }
    $photos = Photo::getByTestId($testId);
}

// Traitement POST
if (isPost()) {
    $data = clean($_POST);
    $testData = [];

    // Section 1: Identification
    $testData['site'] = $data['site'] ?? 'HGR de Panzi';
    $testData['batiment'] = $data['batiment'] ?? '';
    $testData['service'] = $data['service'] ?? '';
    $testData['localisation_a'] = $data['localisation_a'] ?? '';
    $testData['localisation_b'] = $data['localisation_b'] ?? '';
    $testData['liaison_id'] = $data['liaison_id'] ?? '';
    $testData['cable_categorie'] = $data['cable_categorie'] ?? 'Cat 6A';
    $testData['type_liaison'] = $data['type_liaison'] ?? '';

    // Section 2: Contrôle visuel (booléens)
    $visuelFields = ['cheminement_conforme', 'respect_courbure', 'etiquetage_present', 'boitier_fixe', 'patch_panel_raccorde', 'absence_contrainte'];
    foreach ($visuelFields as $f) {
        $testData[$f] = isset($data[$f]) ? 1 : 0;
    }

    // Section 3: Continuité
    $contFields = ['continuite_paire_1_2', 'continuite_paire_3_6', 'continuite_paire_4_5', 'continuite_paire_7_8'];
    foreach ($contFields as $f) {
        $testData[$f] = isset($data[$f]) ? 1 : 0;
    }
    $testData['inversion_paires'] = isset($data['inversion_paires']) ? 1 : 0;
    $testData['court_circuit'] = isset($data['court_circuit']) ? 1 : 0;
    $testData['circuit_ouvert'] = isset($data['circuit_ouvert']) ? 1 : 0;
    $testData['split_pair'] = isset($data['split_pair']) ? 1 : 0;

    // Section 4: Longueur
    $testData['longueur_estimee'] = !empty($data['longueur_estimee']) ? (float)$data['longueur_estimee'] : null;
    $testData['longueur_conforme'] = isset($data['longueur_conforme']) ? 1 : 0;

    // Section 5: Performance
    $testData['debit_descendant'] = $data['debit_descendant'] ?? '';
    $testData['debit_montant'] = $data['debit_montant'] ?? '';
    $testData['latence'] = $data['latence'] ?? '';
    $testData['perte_paquets'] = $data['perte_paquets'] ?? '';
    $testData['stabilite_lien'] = isset($data['stabilite_lien']) ? 1 : 0;

    // Section 6: Connectivité
    $connFields = ['ip_obtenue', 'acces_passerelle', 'acces_serveur_local', 'acces_internet'];
    foreach ($connFields as $f) {
        $testData[$f] = isset($data[$f]) ? 1 : 0;
    }

    // Section 7: Électrique
    $testData['interferences_electriques'] = isset($data['interferences_electriques']) ? 1 : 0;
    $testData['proximite_cable_energie'] = isset($data['proximite_cable_energie']) ? 1 : 0;
    $testData['terre_fonctionnelle'] = $data['terre_fonctionnelle'] ?? 'N/A';

    // Section 8: Conformité
    $testData['conforme_normes'] = isset($data['conforme_normes']) ? 1 : 0;
    $testData['validee_production'] = isset($data['validee_production']) ? 1 : 0;
    $testData['rework_necessaire'] = isset($data['rework_necessaire']) ? 1 : 0;

    // Section 9: Observations
    $testData['observations'] = $data['observations'] ?? '';

    // Section 10: Validation
    $testData['test_realise_par'] = $data['test_realise_par'] ?? $user['nom'] ?? '';
    $testData['equipe_validation'] = $data['equipe_validation'] ?? '';
    $testData['date_test'] = $data['date_test'] ?? date('Y-m-d');
    $testData['validation'] = $data['validation'] ?? 'en_attente';

    if ($isEdit) {
        $testData['id'] = $testId;
        // Remove id from data for update
        Test::update($testId, $testData);
        flash('success', 'Test mis à jour avec succès.');

        // Upload photos
        if (!empty($_FILES['photos']['name'][0])) {
            $desc = $_POST['photo_desc'] ?? [];
            $uploadResult = Photo::upload($_FILES['photos'], $testId, $desc);
            if (!empty($uploadResult['errors'])) {
                foreach ($uploadResult['errors'] as $err) {
                    flash('error', $err);
                }
            }
            if (!empty($uploadResult['uploaded'])) {
                flash('success', count($uploadResult['uploaded']) . ' photo(s) ajoutée(s).');
            }
        }

        redirect('index.php?page=test_detail&id=' . $testId);
    } else {
        $testData['created_by'] = $user['id'] ?? null;
        $newId = Test::create($testData);
        flash('success', 'Test créé avec succès.');

        // Upload photos
        if (!empty($_FILES['photos']['name'][0])) {
            $desc = $_POST['photo_desc'] ?? [];
            $uploadResult = Photo::upload($_FILES['photos'], $newId, $desc);
            foreach ($uploadResult['errors'] as $err) {
                flash('error', $err);
            }
        }

        redirect('index.php?page=test_detail&id=' . $newId);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Modifier le test' : 'Nouveau test réseau' ?></h1>
    <p class="page-desc">Fiche de test unitaire — Liaison réseau VDI</p>
</div>

<form method="POST" action="index.php?page=test_form<?= $isEdit ? '&id=' . $testId : '' ?>" enctype="multipart/form-data" class="test-form" id="testForm">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <!-- 1. IDENTIFICATION -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">1. Identification de la liaison</h2>
        </div>
        <div class="card__body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="site">Site</label>
                    <input type="text" name="site" id="site" class="form-input" value="<?= h($test['site'] ?? 'HGR de Panzi') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="batiment">Bâtiment</label>
                    <input type="text" name="batiment" id="batiment" class="form-input" value="<?= h($test['batiment'] ?? '') ?>" placeholder="Ex: Bâtiment A" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="service">Service</label>
                    <input type="text" name="service" id="service" class="form-input" value="<?= h($test['service'] ?? '') ?>" placeholder="Ex: Radiologie">
                </div>
                <div class="form-group">
                    <label class="form-label" for="type_liaison">Type de liaison</label>
                    <select name="type_liaison" id="type_liaison" class="form-input">
                        <option value="">Sélectionner...</option>
                        <option value="Poste utilisateur" <?= ($test['type_liaison'] ?? '') === 'Poste utilisateur' ? 'selected' : '' ?>>Poste utilisateur</option>
                        <option value="Données" <?= ($test['type_liaison'] ?? '') === 'Données' ? 'selected' : '' ?>>Données</option>
                        <option value="WiFi AP" <?= ($test['type_liaison'] ?? '') === 'WiFi AP' ? 'selected' : '' ?>>WiFi AP</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="localisation_a">Localisation point A (Patch Panel)</label>
                    <input type="text" name="localisation_a" id="localisation_a" class="form-input" value="<?= h($test['localisation_a'] ?? '') ?>" placeholder="Baie / rack n°">
                </div>
                <div class="form-group">
                    <label class="form-label" for="localisation_b">Localisation point B (Prise RJ45)</label>
                    <input type="text" name="localisation_b" id="localisation_b" class="form-input" value="<?= h($test['localisation_b'] ?? '') ?>" placeholder="Bureau / salle n°">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="liaison_id">N° de câble / ID liaison</label>
                    <input type="text" name="liaison_id" id="liaison_id" class="form-input" value="<?= h($test['liaison_id'] ?? '') ?>" placeholder="Ex: LNK-001" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="cable_categorie">Catégorie câble</label>
                    <select name="cable_categorie" id="cable_categorie" class="form-input">
                        <option value="Cat 5e" <?= ($test['cable_categorie'] ?? '') === 'Cat 5e' ? 'selected' : '' ?>>Cat 5e</option>
                        <option value="Cat 6" <?= ($test['cable_categorie'] ?? '') === 'Cat 6' ? 'selected' : '' ?>>Cat 6</option>
                        <option value="Cat 6A" <?= ($test['cable_categorie'] ?? 'Cat 6A') === 'Cat 6A' ? 'selected' : '' ?>>Cat 6A</option>
                        <option value="Cat 7" <?= ($test['cable_categorie'] ?? '') === 'Cat 7' ? 'selected' : '' ?>>Cat 7</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. CONTRÔLE VISUEL -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">2. Contrôle visuel de l'installation</h2>
        </div>
        <div class="card__body">
            <div class="form-checkboxes">
                <?php
                $visuelChecks = [
                    'cheminement_conforme' => 'Cheminement conforme (goulotte / faux plafond / encastrement)',
                    'respect_courbure' => 'Respect des rayons de courbure',
                    'etiquetage_present' => 'Étiquetage des deux extrémités',
                    'boitier_fixe' => 'Boîtier RJ45 correctement fixé',
                    'patch_panel_raccorde' => 'Patch panel correctement raccordé',
                    'absence_contrainte' => 'Absence de contrainte mécanique sur câble',
                ];
                foreach ($visuelChecks as $key => $label):
                    $checked = $isEdit && ($test[$key] ?? 0) ? true : false;
                ?>
                <label class="checkbox">
                    <input type="checkbox" name="<?= $key ?>" class="checkbox__input" <?= $checked ? 'checked' : '' ?>>
                    <span class="checkbox__check"></span>
                    <span class="checkbox__label"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 3. TEST DE CONTINUITÉ -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">3. Test de continuité (Testeur NOYAFA)</h2>
        </div>
        <div class="card__body">
            <p class="form-section__subtitle">Continuité des paires</p>
            <div class="form-checkboxes">
                <?php
                $contChecks = [
                    'continuite_paire_1_2' => 'Continuité paire 1-2',
                    'continuite_paire_3_6' => 'Continuité paire 3-6',
                    'continuite_paire_4_5' => 'Continuité paire 4-5',
                    'continuite_paire_7_8' => 'Continuité paire 7-8',
                ];
                foreach ($contChecks as $key => $label):
                    $checked = $isEdit && ($test[$key] ?? 0) ? true : false;
                ?>
                <label class="checkbox">
                    <input type="checkbox" name="<?= $key ?>" class="checkbox__input" <?= $checked ? 'checked' : '' ?>>
                    <span class="checkbox__check"></span>
                    <span class="checkbox__label"><?= $label ?> <span class="badge badge--success">OK</span></span>
                </label>
                <?php endforeach; ?>
            </div>

            <p class="form-section__subtitle">Défauts détectés</p>
            <div class="form-checkboxes">
                <?php
                $defautChecks = [
                    'inversion_paires' => 'Inversion de paires détectée',
                    'court_circuit' => 'Court-circuit détecté',
                    'circuit_ouvert' => 'Circuit ouvert détecté',
                    'split_pair' => 'Split pair détecté',
                ];
                foreach ($defautChecks as $key => $label):
                    $checked = $isEdit && ($test[$key] ?? 0) ? true : false;
                ?>
                <label class="checkbox checkbox--danger">
                    <input type="checkbox" name="<?= $key ?>" class="checkbox__input" <?= $checked ? 'checked' : '' ?>>
                    <span class="checkbox__check"></span>
                    <span class="checkbox__label"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 4. LONGUEUR -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">4. Mesure de longueur</h2>
        </div>
        <div class="card__body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="longueur_estimee">Longueur estimée (mètres)</label>
                    <input type="number" name="longueur_estimee" id="longueur_estimee" class="form-input" value="<?= h($test['longueur_estimee'] ?? '') ?>" placeholder="Ex: 45" step="0.1" min="0">
                </div>
            </div>
            <label class="checkbox">
                <input type="checkbox" name="longueur_conforme" class="checkbox__input" <?= ($test['longueur_conforme'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Longueur conforme Cat 6A (&lt; 90m permanent link)</span>
            </label>
        </div>
    </div>

    <!-- 5. PERFORMANCE -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">5. Test de performance (iPerf3)</h2>
        </div>
        <div class="card__body">
            <div class="form-row two-cols">
                <div class="form-group">
                    <label class="form-label" for="debit_descendant">Débit descendant</label>
                    <input type="text" name="debit_descendant" id="debit_descendant" class="form-input" value="<?= h($test['debit_descendant'] ?? '') ?>" placeholder="Ex: 1 Gbps">
                </div>
                <div class="form-group">
                    <label class="form-label" for="debit_montant">Débit montant</label>
                    <input type="text" name="debit_montant" id="debit_montant" class="form-input" value="<?= h($test['debit_montant'] ?? '') ?>" placeholder="Ex: 950 Mbps">
                </div>
            </div>
            <div class="form-row two-cols">
                <div class="form-group">
                    <label class="form-label" for="latence">Latence</label>
                    <input type="text" name="latence" id="latence" class="form-input" value="<?= h($test['latence'] ?? '') ?>" placeholder="Ex: 2 ms">
                </div>
                <div class="form-group">
                    <label class="form-label" for="perte_paquets">Perte de paquets</label>
                    <select name="perte_paquets" id="perte_paquets" class="form-input">
                        <option value="0%" <?= ($test['perte_paquets'] ?? '') === '0%' ? 'selected' : '' ?>>0%</option>
                        <option value="<1%" <?= ($test['perte_paquets'] ?? '') === '<1%' ? 'selected' : '' ?>>&lt;1%</option>
                        <option value=">1%" <?= ($test['perte_paquets'] ?? '') === '>1%' ? 'selected' : '' ?>>&gt;1%</option>
                    </select>
                </div>
            </div>
            <label class="checkbox">
                <input type="checkbox" name="stabilite_lien" class="checkbox__input" <?= ($test['stabilite_lien'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Stabilité du lien : <strong>Stable</strong></span>
            </label>
        </div>
    </div>

    <!-- 6. CONNECTIVITÉ -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">6. Test de connectivité réseau</h2>
        </div>
        <div class="card__body">
            <div class="form-checkboxes">
                <?php
                $connChecks = [
                    'ip_obtenue' => 'Obtention adresse IP',
                    'acces_passerelle' => 'Accès passerelle',
                    'acces_serveur_local' => 'Accès serveur local',
                    'acces_internet' => 'Accès Internet',
                ];
                foreach ($connChecks as $key => $label):
                    $checked = $isEdit && ($test[$key] ?? 0) ? true : false;
                ?>
                <label class="checkbox">
                    <input type="checkbox" name="<?= $key ?>" class="checkbox__input" <?= $checked ? 'checked' : '' ?>>
                    <span class="checkbox__check"></span>
                    <span class="checkbox__label"><?= $label ?> <span class="badge badge--success">OK</span></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 7. ÉLECTRIQUE -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">7. Contrôle électrique / Environnement</h2>
        </div>
        <div class="card__body">
            <label class="checkbox">
                <input type="checkbox" name="interferences_electriques" class="checkbox__input" <?= ($test['interferences_electriques'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Présence d'interférences électriques</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="proximite_cable_energie" class="checkbox__input" <?= ($test['proximite_cable_energie'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Proximité câble énergie (&lt; 30 cm)</span>
            </label>
            <div class="form-group">
                <label class="form-label" for="terre_fonctionnelle">Terre fonctionnelle disponible (si blindé)</label>
                <select name="terre_fonctionnelle" id="terre_fonctionnelle" class="form-input">
                    <option value="Oui" <?= ($test['terre_fonctionnelle'] ?? '') === 'Oui' ? 'selected' : '' ?>>Oui</option>
                    <option value="Non" <?= ($test['terre_fonctionnelle'] ?? '') === 'Non' ? 'selected' : '' ?>>Non</option>
                    <option value="N/A" <?= ($test['terre_fonctionnelle'] ?? 'N/A') === 'N/A' ? 'selected' : '' ?>>N/A</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 8. CONFORMITÉ -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">8. Conformité générale</h2>
        </div>
        <div class="card__body">
            <label class="checkbox">
                <input type="checkbox" name="conforme_normes" class="checkbox__input" <?= ($test['conforme_normes'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Liaison conforme aux normes Cat 6A</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="validee_production" class="checkbox__input" <?= ($test['validee_production'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Liaison validée pour mise en production</span>
            </label>
            <label class="checkbox checkbox--danger">
                <input type="checkbox" name="rework_necessaire" class="checkbox__input" <?= ($test['rework_necessaire'] ?? 0) ? 'checked' : '' ?>>
                <span class="checkbox__check"></span>
                <span class="checkbox__label">Rework nécessaire</span>
            </label>

            <div class="form-group" style="margin-top:1rem">
                <label class="form-label" for="validation">Statut de validation</label>
                <select name="validation" id="validation" class="form-input">
                    <option value="en_attente" <?= ($test['validation'] ?? 'en_attente') === 'en_attente' ? 'selected' : '' ?>>En attente de validation</option>
                    <option value="valide" <?= ($test['validation'] ?? '') === 'valide' ? 'selected' : '' ?>>Validé</option>
                    <option value="rework" <?= ($test['validation'] ?? '') === 'rework' ? 'selected' : '' ?>>Rework nécessaire</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 9. OBSERVATIONS -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">9. Observations</h2>
        </div>
        <div class="card__body">
            <div class="form-group">
                <textarea name="observations" id="observations" class="form-input form-textarea" rows="4" placeholder="Informations complémentaires, anomalies constatées, travaux à prévoir..."><?= h($test['observations'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- 10. PHOTOS -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">10. Photos</h2>
        </div>
        <div class="card__body">
            <div class="photo-upload">
                <div class="photo-upload__zone" id="dropZone">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <p>Toucher pour ajouter des photos</p>
                    <span class="photo-upload__hint">JPEG, PNG, WebP — Max 10 Mo par photo</span>
                    <input type="file" name="photos[]" id="photoInput" accept="image/jpeg,image/png,image/webp" multiple hidden>
                </div>
                <div class="photo-upload__preview" id="photoPreview"></div>
                <div class="photo-upload__descriptions" id="photoDescriptions"></div>
            </div>

            <?php if ($isEdit && !empty($photos)): ?>
            <div class="gallery">
                <?php foreach ($photos as $photo): ?>
                <div class="gallery__item">
                    <img src="<?= h($photo['chemin_image']) ?>" alt="Photo test" loading="lazy" class="gallery__img">
                    <div class="gallery__info">
                        <?php if (!empty($photo['description'])): ?>
                        <p class="gallery__desc"><?= h($photo['description']) ?></p>
                        <?php endif; ?>
                        <a href="index.php?page=photo_delete&id=<?= $photo['id'] ?>&test_id=<?= $testId ?>" class="gallery__delete" onclick="return confirm('Supprimer cette photo ?')">Supprimer</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 11. VALIDATION -->
    <div class="card form-section">
        <div class="card__header">
            <h2 class="card__title">11. Validation</h2>
        </div>
        <div class="card__body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="test_realise_par">Test réalisé par</label>
                    <input type="text" name="test_realise_par" id="test_realise_par" class="form-input" value="<?= h($test['test_realise_par'] ?? $user['nom'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="equipe_validation">Équipe / Entreprise</label>
                    <input type="text" name="equipe_validation" id="equipe_validation" class="form-input" value="<?= h($test['equipe_validation'] ?? '') ?>" placeholder="Ex: Equipe technique HGR">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="date_test">Date du test</label>
                <input type="date" name="date_test" id="date_test" class="form-input" value="<?= h($test['date_test'] ?? date('Y-m-d')) ?>" required>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg btn--block">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <?= $isEdit ? 'Mettre à jour le test' : 'Enregistrer le test' ?>
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
