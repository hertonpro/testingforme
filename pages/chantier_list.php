<?php
// ============================================================
// Page : Liste des chantiers
// ============================================================

$pageTitle = 'Chantiers';
$user = Auth::user();
$isAdmin = Auth::hasRole('administrateur');

$page = max(1, (int)($_GET['p'] ?? 1));
$filterStatut = $_GET['statut'] ?? '';

$result = Chantier::list($filterStatut, $page, ITEMS_PER_PAGE);
$chantiers = $result['chantiers'];
$totalPages = $result['totalPages'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header--between">
    <div>
        <h1 class="page-title">Chantiers</h1>
        <p class="page-desc"><?= $result['total'] ?> chantier(s)</p>
    </div>
    <?php if ($isAdmin): ?>
    <a href="index.php?page=chantier_form" class="btn btn--primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouveau chantier
    </a>
    <?php endif; ?>
</div>

<div class="filters">
    <form method="GET" action="index.php" class="filters__form">
        <input type="hidden" name="page" value="chantier_list">
        <select name="statut" class="form-input filters__select" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            <option value="actif" <?= $filterStatut === 'actif' ? 'selected' : '' ?>>Actif</option>
            <option value="termine" <?= $filterStatut === 'termine' ? 'selected' : '' ?>>Terminé</option>
            <option value="suspendu" <?= $filterStatut === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
        </select>
    </form>
</div>

<?php if (empty($chantiers)): ?>
<div class="empty-state">
    <h3>Aucun chantier</h3>
    <p>Créez un premier chantier pour organiser vos tests.</p>
    <?php if ($isAdmin): ?>
    <a href="index.php?page=chantier_form" class="btn btn--primary">Créer un chantier</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="chantier-list">
    <?php foreach ($chantiers as $c): ?>
    <a href="index.php?page=chantier_detail&id=<?= $c['id'] ?>" class="card chantier-card">
        <div class="chantier-card__header">
            <h3 class="chantier-card__nom"><?= h($c['nom']) ?></h3>
            <span class="badge badge--<?= $c['statut'] === 'actif' ? 'success' : ($c['statut'] === 'termine' ? 'info' : 'warning') ?>">
                <?= h($c['statut']) ?>
            </span>
        </div>
        <?php if ($c['description']): ?>
        <p class="chantier-card__desc"><?= h(truncate($c['description'], 120)) ?></p>
        <?php endif; ?>
        <div class="chantier-card__meta">
            <span>👷 <?= $c['tech_count'] ?> technicien(s)</span>
            <span>📋 <?= $c['test_count'] ?> test(s)</span>
            <span>📅 <?= formatDate($c['created_at']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=chantier_list&p=<?= $page - 1 ?>&statut=<?= urlencode($filterStatut) ?>" class="pagination__link">Précédent</a>
    <?php endif; ?>
    <span class="pagination__info">Page <?= $page ?> / <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
    <a href="?page=chantier_list&p=<?= $page + 1 ?>&statut=<?= urlencode($filterStatut) ?>" class="pagination__link">Suivant</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<style>
.chantier-list { display:grid; gap:12px; }
.chantier-card { display:block; text-decoration:none; color:inherit; padding:16px; }
.chantier-card:hover { box-shadow:var(--shadow-md); }
.chantier-card__header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.chantier-card__nom { font-size:1.05rem; font-weight:700; }
.chantier-card__desc { font-size:0.85rem; color:var(--color-text-secondary); margin-bottom:8px; }
.chantier-card__meta { display:flex; gap:16px; font-size:0.78rem; color:var(--color-text-light); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
