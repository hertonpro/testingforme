<?php
// ============================================================
// Page : Tableau de bord
// ============================================================

$pageTitle = 'Tableau de bord';
$user = Auth::user();
$userId = $user['id'] ?? null;
$isAdmin = Auth::hasRole('administrateur');

// Stats
$stats = Test::getStats($isAdmin ? null : $userId);
$recentTests = Test::getRecent(5, $isAdmin ? null : $userId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <div class="dashboard__welcome">
        <h1 class="dashboard__greeting">Bonjour, <?= h($user['nom'] ?? 'Technicien') ?></h1>
        <p class="dashboard__date"><?= date('l d F Y') ?></p>
    </div>

    <div class="stats-grid">
        <div class="card stat-card stat-card--total">
            <div class="stat-card__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="stat-card__body">
                <span class="stat-card__value"><?= (int)($stats['total'] ?? 0) ?></span>
                <span class="stat-card__label">Total des tests</span>
            </div>
        </div>

        <div class="card stat-card stat-card--success">
            <div class="stat-card__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="stat-card__body">
                <span class="stat-card__value"><?= (int)($stats['valide'] ?? 0) ?></span>
                <span class="stat-card__label">Validés</span>
            </div>
        </div>

        <div class="card stat-card stat-card--warning">
            <div class="stat-card__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="stat-card__body">
                <span class="stat-card__value"><?= (int)($stats['attente'] ?? 0) ?></span>
                <span class="stat-card__label">En attente</span>
            </div>
        </div>

        <div class="card stat-card stat-card--danger">
            <div class="stat-card__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="stat-card__body">
                <span class="stat-card__value"><?= (int)($stats['rework'] ?? 0) ?></span>
                <span class="stat-card__label">Rework</span>
            </div>
        </div>
    </div>

    <div class="dashboard__actions">
        <a href="index.php?page=test_form" class="btn btn--primary btn--lg btn--block">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouveau test réseau
        </a>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Derniers rapports</h2>
            <a href="index.php?page=test_list" class="card__link">Voir tout</a>
        </div>
        <div class="card__body">
            <?php if (empty($recentTests)): ?>
            <div class="empty-state">
                <p>Aucun test pour le moment.</p>
                <a href="index.php?page=test_form" class="btn btn--primary">Créer un test</a>
            </div>
            <?php else: ?>
            <div class="test-list">
                <?php foreach ($recentTests as $test): ?>
                <a href="index.php?page=test_detail&id=<?= $test['id'] ?>" class="test-item">
                    <div class="test-item__info">
                        <span class="test-item__title"><?= h($test['liaison_id'] ?: 'N°' . $test['id']) ?></span>
                        <span class="test-item__meta"><?= h($test['site'] ?? '') ?> — <?= h($test['batiment'] ?? '') ?></span>
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
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
