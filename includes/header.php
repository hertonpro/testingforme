<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1E293B">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">

    <title><?= h(APP_NAME) ?> <?= isset($pageTitle) ? '— ' . h($pageTitle) : '' ?></title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231E293B'%3E%3Crect x='2' y='2' width='20' height='8' rx='2'/%3E%3Crect x='2' y='14' width='20' height='8' rx='2'/%3E%3Cline x1='6' y1='6' x2='6.01' y2='6' stroke='white' stroke-width='2'/%3E%3Cline x1='6' y1='18' x2='6.01' y2='18' stroke='white' stroke-width='2'/%3E%3C/svg%3E">

    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231E293B'%3E%3Crect x='2' y='2' width='20' height='8' rx='2'/%3E%3Crect x='2' y='14' width='20' height='8' rx='2'/%3E%3Cline x1='6' y1='6' x2='6.01' y2='6' stroke='white' stroke-width='2'/%3E%3Cline x1='6' y1='18' x2='6.01' y2='18' stroke='white' stroke-width='2'/%3E%3C/svg%3E">
</head>
<body>
    <div class="app">
        <?php if (Auth::check()): ?>
        <header class="header">
            <div class="header__inner">
                <button class="header__menu-btn" id="menuToggle" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
                <div class="header__brand">
                    <span class="header__title"><?= h(APP_NAME) ?></span>
                    <span class="header__subtitle">HGR de Panzi</span>
                </div>
                <div class="header__actions">
                    <span class="header__user"><?= h($_SESSION['user_nom'] ?? '') ?></span>
                    <a href="index.php?page=logout" class="header__logout" title="Déconnexion">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </a>
                </div>
            </div>
        </header>

        <nav class="sidebar" id="sidebar">
            <div class="sidebar__header">
                <span class="sidebar__brand">Menu</span>
                <button class="sidebar__close" id="sidebarClose">&times;</button>
            </div>
            <ul class="sidebar__nav">
                <li>
                    <a href="index.php?page=dashboard" class="sidebar__link <?= ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="index.php?page=test_form" class="sidebar__link <?= ($_GET['page'] ?? '') === 'test_form' ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nouveau test
                    </a>
                </li>
                <li>
                    <a href="index.php?page=test_list" class="sidebar__link <?= ($_GET['page'] ?? '') === 'test_list' ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        Mes tests
                    </a>
                </li>
                <?php if (Auth::hasRole('administrateur')): ?>
                <li class="sidebar__divider">Administration</li>
                <li>
                    <a href="index.php?page=users" class="sidebar__link <?= ($_GET['page'] ?? '') === 'users' ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Utilisateurs
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main">
            <div class="container">
                <?= renderFlash() ?>
        <?php else: ?>
        <main class="main main--auth">
            <div class="container container--auth">
        <?php endif; ?>
