<?php
// ============================================================
// Configuration de l'application
// ============================================================

define('APP_NAME', 'Testing Réseau VDI');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/testing-reseau');
define('APP_LOGO', 'assets/images/logo.png');

// Chemins
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', BASE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('PHOTO_PATH', UPLOAD_PATH . 'photos' . DIRECTORY_SEPARATOR);

// Uploads
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 Mo
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('PHOTO_QUALITY', 80);
define('PHOTO_MAX_WIDTH', 1920);
define('PHOTO_MAX_HEIGHT', 1080);

// Session
define('SESSION_LIFETIME', 7200); // 2 heures
define('SESSION_NAME', 'TESTING_RESEAU_SID');

// Pagination
define('ITEMS_PER_PAGE', 15);

// Date
date_default_timezone_set('Africa/Kinshasa');
