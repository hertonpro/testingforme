<?php
// ============================================================
// Routeur principal
// ============================================================

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Autoloader des classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

Auth::init();

$page = $_GET['page'] ?? 'login';

// Pages accessibles sans authentification
$publicPages = ['login', 'logout'];

if (!in_array($page, $publicPages) && !Auth::check()) {
    redirect('index.php?page=login');
}

// Routage
$pageFile = __DIR__ . '/pages/' . basename($page) . '.php';

if (!file_exists($pageFile)) {
    $pageFile = __DIR__ . '/pages/dashboard.php';
}

ob_start();
require $pageFile;
$content = ob_get_clean();

echo $content;
