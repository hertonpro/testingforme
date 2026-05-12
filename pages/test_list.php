<?php
// ============================================================
// Page : Liste des tests
// ============================================================

$pageTitle = 'Mes tests';
$user = Auth::user();
$isAdmin = Auth::hasRole('administrateur');

$page = max(1, (int)($_GET['p'] ?? 1));
$search = $_GET['search'] ?? '';
$filterValidation = $_GET['validation'] ?? '';
$filterSite = $_GET['site'] ?? '';

$filters = [];
if ($search) $filters['search'] = $search;
if ($filterValidation) $filters['validation'] = $filterValidation;
if ($filterSite) $filters['site'] = $filterSite;
if (!$isAdmin) $filters['created_by'] = $user['id'];

$result = Test::list($filters, $page, ITEMS_PER_PAGE);
$tests = $result['tests'];
$totalPages = $result['totalPages'];
$total = $result['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header--between">
    <div>
        <h1 class="page-title">Tests réseau</h1>
        <p class="page-desc"><?= $total ?> test(s) trouvé(s)</p>
    </div>
    <a href="index.php?page=test_form" class="btn btn--primary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouveau
    </a>
</div>

<!-- Filtres -->
<div class="filters">
    <form method="GET" action="index.php" class="filters__form">
        <input type="hidden" name="page" value="test_list">
        <div class="filters__row">
            <div class="filters__search">
                <input type="search" name="search" class="form-input" placeholder="Rechercher..." value="<?= h($search) ?>">
            </div>
            <select name="validation" class="form-input filters__select">
                <option value="">Tous statuts</option>
                <option value="en_attente" <?= $filterValidation === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                <option value="valide" <?= $filterValidation === 'valide' ? 'selected' : '' ?>>Validé</option>
                <option value="rework" <?= $filterValidation === 'rework' ? 'selected' : '' ?>>Rework</option>
            </select>
            <button type="submit" class="btn btn--primary">Filtrer</button>
        </div>
    </form>
</div>

<!-- Résultats -->
<?php if (empty($tests)): ?>
<div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <h3>Aucun test trouvé</h3>
    <p>Commencez par créer un nouveau test réseau.</p>
    <a href="index.php?page=test_form" class="btn btn--primary">Créer un test</a>
</div>
<?php else: ?>
<div class="test-list test-list--full">
    <?php foreach ($tests as $test): ?>
    <a href="index.php?page=test_detail&id=<?= $test['id'] ?>" class="test-item">
        <div class="test-item__info">
            <span class="test-item__title"><?= h($test['liaison_id'] ?: 'N°' . $test['id']) ?></span>
            <span class="test-item__meta">
                <?= h($test['site'] ?? '') ?> — <?= h($test['batiment'] ?? '') ?>
                <?php if ($test['service']): ?> — <?= h($test['service']) ?><?php endif; ?>
            </span>
            <span class="test-item__sub">
                Par <?= h($test['createur_nom'] ?? 'Inconnu') ?> — <?= formatDate($test['date_test']) ?>
                <?php if ($test['photo_count'] > 0): ?> — 📷 <?= $test['photo_count'] ?><?php endif; ?>
            </span>
        </div>
        <div class="test-item__right">
            <span class="<?= validationBadge($test['validation'] ?? 'en_attente') ?>"><?= validationLabel($test['validation'] ?? 'en_attente') ?></span>
            <span class="test-item__date"><?= formatDatetime($test['created_at']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=test_list&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&validation=<?= urlencode($filterValidation) ?>" class="pagination__link">Précédent</a>
    <?php endif; ?>
    <span class="pagination__info">Page <?= $page ?> / <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
    <a href="?page=test_list&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&validation=<?= urlencode($filterValidation) ?>" class="pagination__link">Suivant</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
