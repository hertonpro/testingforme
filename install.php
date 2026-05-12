<?php
// ============================================================
// Script d'installation / migration de la base de données
// ============================================================

// À exécuter UNE SEULE FOIS après avoir configuré config/database.php
// Via navigateur : http://localhost/chemin/install.php
// Ou en CLI : php install.php

// === SÉCURITÉ : supprimer ce fichier après installation ! ===

$step = $_GET['step'] ?? 'start';

switch ($step) {
    case 'start':
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Installation — Testing Réseau VDI</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); max-width: 500px; width: 90%; text-align: center; }
                h1 { color: #003366; font-size: 1.5rem; margin-bottom: 8px; }
                p { color: #6b7280; margin-bottom: 24px; }
                .btn { display: inline-block; padding: 14px 32px; background: #003366; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 1rem; }
                .btn:hover { background: #002244; }
                .warning { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>Testing Réseau VDI</h1>
                <p>Assistant d'installation de la base de données</p>
                <div class="warning">⚠ Assurez-vous d'avoir configuré <strong>config/database.php</strong> avant de continuer.</div>
                <a href="?step=install" class="btn">Installer la base de données</a>
                <p style="margin-top:16px;font-size:0.8rem">Ce script va créer les tables et le compte administrateur par défaut.</p>
            </div>
        </body>
        </html>
        <?php
        break;

    case 'install':
        require_once __DIR__ . '/config/app.php';
        require_once __DIR__ . '/config/database.php';

        try {
            // Connexion sans sélection de DB pour créér la base
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Création de la base
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");

            // Exécuter le schéma SQL
            $sql = file_get_contents(__DIR__ . '/install.sql');
            $statements = explode(';', $sql);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }

            // Création compte admin par défaut
            $nom = 'Administrateur';
            $email = 'admin@testing-reseau.fr';
            $password = password_hash('admin123', PASSWORD_BCRYPT);

            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetchColumn() == 0) {
                $insert = $pdo->prepare("INSERT INTO users (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'administrateur')");
                $insert->execute([$nom, $email, $password]);
            }

            $success = true;
        } catch (PDOException $e) {
            $error = $e->getMessage();
            $success = false;
        }

        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Installation terminée — Testing Réseau VDI</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); max-width: 500px; width: 90%; text-align: center; }
                h1 { font-size: 1.5rem; margin-bottom: 8px; }
                h1.success { color: #10b981; }
                h1.error { color: #ef4444; }
                p { color: #6b7280; margin-bottom: 8px; }
                .details { background: #f3f4f6; padding: 12px; border-radius: 8px; font-size: 0.8rem; text-align: left; margin: 16px 0; font-family: monospace; word-break: break-word; }
                .btn { display: inline-block; padding: 14px 32px; background: #003366; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 1rem; margin-top: 16px; }
                .btn:hover { background: #002244; }
                .warning { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 8px; font-size: 0.85rem; margin-top: 16px; }
                .creds { background: #e8f5e9; padding: 16px; border-radius: 8px; margin: 16px 0; }
                .creds strong { font-size: 0.9rem; }
                .creds code { background: rgba(0,0,0,0.05); padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1 class="<?= $success ? 'success' : 'error' ?>">
                    <?= $success ? '✓ Installation réussie' : '✗ Erreur d\'installation' ?>
                </h1>

                <?php if ($success): ?>
                    <div class="creds">
                        <strong>Compte administrateur par défaut :</strong><br><br>
                        <code>Email : admin@testing-reseau.fr</code><br>
                        <code>Mot de passe : admin123</code>
                    </div>
                    <p>Les tables ont été créées avec succès.</p>
                    <a href="index.php" class="btn">Accéder à l'application</a>
                    <div class="warning">⚠ Supprimez le fichier <strong>install.php</strong> après installation !</div>
                <?php else: ?>
                    <div class="details"><?= h($error ?? 'Erreur inconnue') ?></div>
                    <p>Vérifiez votre configuration dans <strong>config/database.php</strong></p>
                    <a href="?step=start" class="btn">Réessayer</a>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        break;
}

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}
