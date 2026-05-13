<?php
// ============================================================
// Page : Détail d'un chantier
// ============================================================

$pageTitle = 'Chantier';
$isAdmin = Auth::hasRole('administrateur');
$id = (int)($_GET['id'] ?? 0);

if (!$id) { flash('error', 'ID invalide.'); redirect('index.php?page=chantier_list'); }

$chantier = Chantier::findById($id);
if (!$chantier) { flash('error', 'Chantier introuvable.'); redirect('index.php?page=chantier_list'); }

// Assigner / retirer un technicien
if (isPost() && $isAdmin) {
    $action = $_POST['action'] ?? '';
    if ($action === 'assign' && ($uid = (int)($_POST['user_id'] ?? 0))) {
        Chantier::assignTechnicien($id, $uid);
        flash('success', 'Technicien assigné.');
    }
    if ($action === 'remove' && ($uid = (int)($_POST['user_id'] ?? 0))) {
        Chantier::removeTechnicien($id, $uid);
        flash('success', 'Technicien retiré.');
    }
    if ($action === 'delete' && $isAdmin) {
        Chantier::delete($id);
        flash('success', 'Chantier supprimé.');
        redirect('index.php?page=chantier_list');
    }
    redirect('index.php?page=chantier_detail&id=' . $id);
}

$techniciens = Chantier::getTechniciens($id);
$availableTechs = Chantier::getAvailableTechniciens($id);
$stats = Chantier::getStats($id);
$tests = Chantier::getTests($id);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header--between">
    <div>
        <h1 class="page-title"><?= h($chantier['nom']) ?></h1>
        <p class="page-desc">Créé par <?= h($chantier['createur_nom'] ?? '?') ?> le <?= formatDate($chantier['created_at']) ?></p>
    </div>
    <div class="page-header__actions">
        <?php if ($isAdmin): ?>
        <a href="index.php?page=chantier_form&id=<?= $id ?>" class="btn btn--secondary">Modifier</a>
        <?php endif; ?>
        <?php if (!empty($tests)): ?>
        <a href="index.php?page=chantier_export&id=<?= $id ?>" class="btn btn--primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter tous les PDF
        </a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce chantier et ses liaisons ?')">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn--danger">Supprimer</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Infos -->
<div class="card">
    <div class="card__body">
        <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
            <span class="badge badge--<?= $chantier['statut'] === 'actif' ? 'success' : ($chantier['statut'] === 'termine' ? 'info' : 'warning') ?>" style="font-size:0.85rem;padding:6px 16px"><?= h($chantier['statut']) ?></span>
            <?php if ($chantier['description']): ?><span style="color:var(--color-text-secondary)"><?= h($chantier['description']) ?></span><?php endif; ?>
            <?php if ($chantier['adresse']): ?><span style="color:var(--color-text-light)">📍 <?= h($chantier['adresse']) ?></span><?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="card stat-card" style="padding:12px;text-align:center">
        <div class="stat-card__value"><?= (int)$stats['total'] ?></div>
        <div class="stat-card__label">Tests</div>
    </div>
    <div class="card stat-card" style="padding:12px;text-align:center">
        <div class="stat-card__value" style="color:var(--color-success)"><?= (int)$stats['valide'] ?></div>
        <div class="stat-card__label">Validés</div>
    </div>
    <div class="card stat-card" style="padding:12px;text-align:center">
        <div class="stat-card__value" style="color:var(--color-warning)"><?= (int)$stats['attente'] ?></div>
        <div class="stat-card__label">Attente</div>
    </div>
    <div class="card stat-card" style="padding:12px;text-align:center">
        <div class="stat-card__value" style="color:var(--color-danger)"><?= (int)$stats['rework'] ?></div>
        <div class="stat-card__label">Rework</div>
    </div>
</div>

<!-- Techniciens assignés + Formulaire d'assignation -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Techniciens assignés (<?= count($techniciens) ?>)</h2>
    </div>
    <div class="card__body">
        <?php if (empty($techniciens)): ?>
        <p style="color:var(--color-text-light);font-size:0.9rem">Aucun technicien assigné à ce chantier.</p>
        <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">
            <?php foreach ($techniciens as $t): ?>
            <div style="display:flex;align-items:center;gap:6px;background:var(--color-bg);padding:6px 12px;border-radius:var(--radius-sm);font-size:0.85rem">
                <span>👷 <?= h($t['nom']) ?></span>
                <?php if ($isAdmin): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="user_id" value="<?= $t['id'] ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--color-danger);font-size:1.1rem;line-height:1">&times;</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin && !empty($availableTechs)): ?>
        <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap">
            <input type="hidden" name="action" value="assign">
            <select name="user_id" class="form-input" style="flex:1;min-width:160px">
                <option value="">Assigner un technicien...</option>
                <?php foreach ($availableTechs as $t): ?>
                <option value="<?= $t['id'] ?>"><?= h($t['nom']) ?> (<?= h($t['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--primary">Assigner</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Tests du chantier -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Tests du chantier (<?= count($tests) ?>)</h2>
        <a href="index.php?page=test_form&chantier_id=<?= $id ?>" class="btn btn--primary" style="padding:6px 14px;font-size:0.8rem">+ Nouveau test</a>
    </div>
    <div class="card__body">
        <?php if (empty($tests)): ?>
        <p style="color:var(--color-text-light);font-size:0.9rem">Aucun test pour ce chantier. Créez-en un.</p>
        <?php else: ?>
        <div class="test-list">
            <?php foreach ($tests as $test): ?>
            <a href="index.php?page=test_detail&id=<?= $test['id'] ?>" class="test-item">
                <div class="test-item__info">
                    <span class="test-item__title"><?= h($test['liaison_id'] ?: 'N°' . $test['id']) ?></span>
                    <span class="test-item__meta"><?= h($test['batiment'] ?? '') ?> <?= $test['service'] ? '— ' . h($test['service']) : '' ?></span>
                    <span class="test-item__sub">par <?= h($test['createur_nom'] ?? '?') ?> — <?= formatDate($test['date_test']) ?></span>
                </div>
                <div class="test-item__right">
                    <span class="<?= validationBadge($test['validation'] ?? 'en_attente') ?>"><?= validationLabel($test['validation'] ?? 'en_attente') ?></span>
                    <span class="test-item__date"><?= formatDatetime($test['created_at']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
