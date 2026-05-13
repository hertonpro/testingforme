<?php
// ============================================================
// Configuration de la base de données
// Support des variables d'environnement Docker
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: '31.97.118.239');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'testing_reseau');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '12iDrc5UjdPvtysptc7jgQI88');
define('DB_CHARSET', 'utf8mb4');

// DSN PDO
define('DB_DSN', sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
));
