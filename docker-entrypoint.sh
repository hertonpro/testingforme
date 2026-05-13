#!/bin/bash
# ============================================================
# Entrypoint Docker — Testing Réseau VDI
# Initialise la base de données au premier démarrage
# ============================================================

set -e

# Attendre que MariaDB soit prête
echo "⏳ Attente de la base de données..."
max_tries=30
counter=0

while ! php -r "
    try {
        new PDO('mysql:host=${DB_HOST};port=${DB_PORT};charset=utf8mb4', '${DB_USER}', '${DB_PASS}');
        echo 'OK';
    } catch (PDOException \$e) {
        echo \$e->getMessage();
    }
" 2>/dev/null | grep -q "OK"; do
    counter=$((counter + 1))
    if [ $counter -ge $max_tries ]; then
        echo "❌ Base de données indisponible après $max_tries tentatives."
        exit 1
    fi
    sleep 2
done

echo "✅ Base de données disponible."

# Initialiser la base si nécessaire
php -r "
    try {
        \$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};charset=utf8mb4', '${DB_USER}', '${DB_PASS}', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        \$pdo->exec('USE \`${DB_NAME}\`');
        \$result = \$pdo->query('SHOW TABLES LIKE \"tests\"');
        if (\$result->rowCount() == 0) {
            echo 'NEED_INIT';
        } else {
            echo 'EXISTS';
        }
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
" 2>/dev/null | grep -q "NEED_INIT" && {
    echo "🗄️  Initialisation de la base de données..."

    php -r "
        \$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME};charset=utf8mb4', '${DB_USER}', '${DB_PASS}', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        \$pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            role ENUM(\"administrateur\", \"technicien\") NOT NULL DEFAULT \"technicien\",
            equipe VARCHAR(255) DEFAULT NULL,
            entreprise VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        \$pdo->exec('CREATE TABLE IF NOT EXISTS tests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site VARCHAR(255) DEFAULT NULL,
            batiment VARCHAR(255) DEFAULT NULL,
            service VARCHAR(255) DEFAULT NULL,
            localisation_a VARCHAR(255) DEFAULT NULL,
            localisation_b VARCHAR(255) DEFAULT NULL,
            liaison_id VARCHAR(255) DEFAULT NULL,
            cable_categorie VARCHAR(50) DEFAULT \"Cat 6A\",
            type_liaison VARCHAR(50) DEFAULT NULL,
            cheminement_conforme TINYINT(1) DEFAULT 0,
            respect_courbure TINYINT(1) DEFAULT 0,
            etiquetage_present TINYINT(1) DEFAULT 0,
            boitier_fixe TINYINT(1) DEFAULT 0,
            patch_panel_raccorde TINYINT(1) DEFAULT 0,
            absence_contrainte TINYINT(1) DEFAULT 0,
            continuite_paire_1_2 TINYINT(1) DEFAULT 0,
            continuite_paire_3_6 TINYINT(1) DEFAULT 0,
            continuite_paire_4_5 TINYINT(1) DEFAULT 0,
            continuite_paire_7_8 TINYINT(1) DEFAULT 0,
            inversion_paires TINYINT(1) DEFAULT 0,
            court_circuit TINYINT(1) DEFAULT 0,
            circuit_ouvert TINYINT(1) DEFAULT 0,
            split_pair TINYINT(1) DEFAULT 0,
            longueur_estimee DECIMAL(10,2) DEFAULT NULL,
            longueur_conforme TINYINT(1) DEFAULT 0,
            debit_descendant VARCHAR(50) DEFAULT NULL,
            debit_montant VARCHAR(50) DEFAULT NULL,
            latence VARCHAR(50) DEFAULT NULL,
            perte_paquets VARCHAR(10) DEFAULT NULL,
            stabilite_lien TINYINT(1) DEFAULT 0,
            ip_obtenue TINYINT(1) DEFAULT 0,
            acces_passerelle TINYINT(1) DEFAULT 0,
            acces_serveur_local TINYINT(1) DEFAULT 0,
            acces_internet TINYINT(1) DEFAULT 0,
            interferences_electriques TINYINT(1) DEFAULT 0,
            proximite_cable_energie TINYINT(1) DEFAULT 0,
            terre_fonctionnelle VARCHAR(10) DEFAULT \"N/A\",
            conforme_normes TINYINT(1) DEFAULT 0,
            validee_production TINYINT(1) DEFAULT 0,
            rework_necessaire TINYINT(1) DEFAULT 0,
            observations TEXT DEFAULT NULL,
            test_realise_par VARCHAR(255) DEFAULT NULL,
            equipe_validation VARCHAR(255) DEFAULT NULL,
            date_test DATE DEFAULT NULL,
            signature_technicien TEXT DEFAULT NULL,
            signature_superviseur TEXT DEFAULT NULL,
            signature_service TEXT DEFAULT NULL,
            validation ENUM(\"en_attente\", \"valide\", \"rework\") DEFAULT \"en_attente\",
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        \$pdo->exec('CREATE TABLE IF NOT EXISTS photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT NOT NULL,
            chemin_image VARCHAR(500) NOT NULL,
            description TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Chantiers
        \$pdo->exec('CREATE TABLE IF NOT EXISTS chantiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            adresse VARCHAR(500) DEFAULT NULL,
            statut ENUM(\"actif\", \"termine\", \"suspendu\") NOT NULL DEFAULT \"actif\",
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        \$pdo->exec('CREATE TABLE IF NOT EXISTS chantier_techniciens (
            chantier_id INT NOT NULL,
            user_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (chantier_id, user_id),
            FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Ajout chantier_id dans tests
        try {
            \$pdo->exec('ALTER TABLE tests ADD COLUMN chantier_id INT DEFAULT NULL AFTER id');
        } catch (Exception \$e) {}

        // Index
        \$pdo->exec('CREATE INDEX idx_tests_site ON tests(site)');
        \$pdo->exec('CREATE INDEX idx_tests_liaison_id ON tests(liaison_id)');
        \$pdo->exec('CREATE INDEX idx_tests_validation ON tests(validation)');

        // Admin par défaut
        \$stmt = \$pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        \$stmt->execute(['admin@testing-reseau.fr']);
        if (\$stmt->fetchColumn() == 0) {
            \$hash = password_hash('${ADMIN_PASSWORD:-admin123}', PASSWORD_BCRYPT);
            \$insert = \$pdo->prepare('INSERT INTO users (nom, email, mot_de_passe, role, equipe, entreprise) VALUES (?, ?, ?, ?, ?, ?)');
            \$insert->execute(['Administrateur', 'admin@testing-reseau.fr', \$hash, 'administrateur', 'Direction technique', 'Docker']);
            echo 'OK';
        }
    " 2>/dev/null && echo "✅ Base de données initialisée." || echo "⚠️  Erreur initialisation."
}

# Nettoyage production
if [ "${APP_ENV}" = "production" ]; then
    rm -f /var/www/html/install.php /var/www/html/install.sql /var/www/html/generate_logo.php 2>/dev/null || true
fi

# Permissions
chown -R www-data:www-data /var/www/html/uploads /var/www/html/assets/images 2>/dev/null || true

echo ""
echo "============================================"
echo "  🚀 Testing Réseau VDI est prêt !"
echo "============================================"
echo ""

exec "$@"
