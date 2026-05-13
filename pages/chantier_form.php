<?php
// ============================================================
// Page : Création / Modification d'un chantier
// ============================================================

Auth::requireRole('administrateur');
$pageTitle = 'Chantier';

$id = (int)($_GET['id'] ?? 0);
$chantier = null;

if ($id) {
    $chantier = Chantier::findById($id);
    if (!$chantier) { flash('error', 'Chantier introuvable.'); redirect('index.php?page=chantier_list'); }
}

if (isPost()) {
    $data = clean($_POST);
    $nom = trim($data['nom'] ?? '');
    $description = trim($data['description'] ?? '');
    $adresse = trim($data['adresse'] ?? '');
    $statut = $data['statut'] ?? 'actif';

    if (empty($nom)) {
        flash('error', 'Le nom du chantier est obligatoire.');
    } elseif ($id) {
        Chantier::update($id, ['nom' => $nom, 'description' => $description, 'adresse' => $adresse, 'statut' => $statut]);
        flash('success', 'Chantier mis à jour.');
        redirect('index.php?page=chantier_detail&id=' . $id);
    } else {
        $newId = Chantier::create([
            'nom' => $nom, 'description' => $description, 'adresse' => $adresse,
            'statut' => $statut, 'created_by' => Auth::userId(),
        ]);
        flash('success', 'Chantier créé.');
        redirect('index.php?page=chantier_detail&id=' . $newId);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><?= $id ? 'Modifier' : 'Nouveau' ?> chantier</h1>
</div>

<form method="POST" action="index.php?page=chantier_form<?= $id ? '&id=' . $id : '' ?>">
    <div class="card">
        <div class="card__body">
            <div class="form-group">
                <label class="form-label" for="nom">Nom du chantier *</label>
                <input type="text" name="nom" id="nom" class="form-input" value="<?= h($chantier['nom'] ?? '') ?>" required placeholder="Ex: Déploiement VDI Bâtiment A">
            </div>
            <div class="form-group" style="margin-top:12px">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input form-textarea" rows="3" placeholder="Description du chantier..."><?= h($chantier['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin-top:12px">
                <label class="form-label" for="adresse">Adresse / Lieu</label>
                <input type="text" name="adresse" id="adresse" class="form-input" value="<?= h($chantier['adresse'] ?? '') ?>" placeholder="Ex: HGR de Panzi, Avenue X">
            </div>
            <?php if ($id): ?>
            <div class="form-group" style="margin-top:12px">
                <label class="form-label" for="statut">Statut</label>
                <select name="statut" id="statut" class="form-input">
                    <option value="actif" <?= ($chantier['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="termine" <?= ($chantier['statut'] ?? '') === 'termine' ? 'selected' : '' ?>>Terminé</option>
                    <option value="suspendu" <?= ($chantier['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg btn--block">
            <?= $id ? 'Enregistrer les modifications' : 'Créer le chantier' ?>
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
