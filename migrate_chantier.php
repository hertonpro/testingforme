<?php
// ============================================================
// Migration : Ajout des tables chantiers
// À exécuter UNE FOIS après avoir mis à jour les fichiers
// ============================================================

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

try {
    $pdo = Database::getInstance();

    // Chantiers
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chantiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            adresse VARCHAR(500) DEFAULT NULL,
            statut ENUM('actif', 'termine', 'suspendu') NOT NULL DEFAULT 'actif',
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table 'chantiers' créée\n";

    // Liaison chantier-techniciens
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chantier_techniciens (
            chantier_id INT NOT NULL,
            user_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (chantier_id, user_id),
            FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table 'chantier_techniciens' créée\n";

    // Ajout de chantier_id dans tests
    try {
        $pdo->exec("ALTER TABLE tests ADD COLUMN chantier_id INT DEFAULT NULL AFTER id");
        echo "✓ Colonne 'chantier_id' ajoutée à 'tests'\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "→ Colonne 'chantier_id' existe déjà\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE tests ADD INDEX idx_tests_chantier (chantier_id)");
    } catch (PDOException $e) {
        if (!str_contains($e->getMessage(), 'Duplicate key name')) throw $e;
    }

    try {
        $pdo->exec("ALTER TABLE tests ADD FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        if (!str_contains($e->getMessage(), 'Duplicate foreign key')) throw $e;
    }

    echo "✓ Index et contrainte ajoutés\n";
    echo "\n✅ Migration terminée avec succès.\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
