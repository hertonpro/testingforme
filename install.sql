-- ============================================================
-- Application de Testing Réseau VDI - Structure Base de Données
-- MySQL / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS testing_reseau
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE testing_reseau;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('administrateur', 'technicien') NOT NULL DEFAULT 'technicien',
    equipe VARCHAR(255) DEFAULT NULL,
    entreprise VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: tests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- 1. IDENTIFICATION
    site VARCHAR(255) DEFAULT NULL,
    batiment VARCHAR(255) DEFAULT NULL,
    service VARCHAR(255) DEFAULT NULL,
    localisation_a VARCHAR(255) DEFAULT NULL,
    localisation_b VARCHAR(255) DEFAULT NULL,
    liaison_id VARCHAR(255) DEFAULT NULL,
    cable_categorie VARCHAR(50) DEFAULT 'Cat 6A',
    type_liaison VARCHAR(50) DEFAULT NULL,

    -- 2. CONTRÔLE VISUEL
    cheminement_conforme TINYINT(1) DEFAULT 0,
    respect_courbure TINYINT(1) DEFAULT 0,
    etiquetage_present TINYINT(1) DEFAULT 0,
    boitier_fixe TINYINT(1) DEFAULT 0,
    patch_panel_raccorde TINYINT(1) DEFAULT 0,
    absence_contrainte TINYINT(1) DEFAULT 0,

    -- 3. TEST DE CONTINUITÉ
    continuite_paire_1_2 TINYINT(1) DEFAULT 0,
    continuite_paire_3_6 TINYINT(1) DEFAULT 0,
    continuite_paire_4_5 TINYINT(1) DEFAULT 0,
    continuite_paire_7_8 TINYINT(1) DEFAULT 0,
    inversion_paires TINYINT(1) DEFAULT 0,
    court_circuit TINYINT(1) DEFAULT 0,
    circuit_ouvert TINYINT(1) DEFAULT 0,
    split_pair TINYINT(1) DEFAULT 0,

    -- 4. MESURE DE LONGUEUR
    longueur_estimee DECIMAL(10,2) DEFAULT NULL,
    longueur_conforme TINYINT(1) DEFAULT 0,

    -- 5. TEST DE PERFORMANCE (iPerf3)
    debit_descendant VARCHAR(50) DEFAULT NULL,
    debit_montant VARCHAR(50) DEFAULT NULL,
    latence VARCHAR(50) DEFAULT NULL,
    perte_paquets VARCHAR(10) DEFAULT NULL,
    stabilite_lien TINYINT(1) DEFAULT 0,

    -- 6. TEST DE CONNECTIVITÉ RÉSEAU
    ip_obtenue TINYINT(1) DEFAULT 0,
    acces_passerelle TINYINT(1) DEFAULT 0,
    acces_serveur_local TINYINT(1) DEFAULT 0,
    acces_internet TINYINT(1) DEFAULT 0,

    -- 7. CONTRÔLE ÉLECTRIQUE / ENVIRONNEMENT
    interferences_electriques TINYINT(1) DEFAULT 0,
    proximite_cable_energie TINYINT(1) DEFAULT 0,
    terre_fonctionnelle VARCHAR(10) DEFAULT 'N/A',

    -- 8. CONFORMITÉ GÉNÉRALE
    conforme_normes TINYINT(1) DEFAULT 0,
    validee_production TINYINT(1) DEFAULT 0,
    rework_necessaire TINYINT(1) DEFAULT 0,

    -- 9. OBSERVATIONS
    observations TEXT DEFAULT NULL,

    -- 10. VALIDATION
    test_realise_par VARCHAR(255) DEFAULT NULL,
    equipe_validation VARCHAR(255) DEFAULT NULL,
    date_test DATE DEFAULT NULL,
    signature_technicien TEXT DEFAULT NULL,
    signature_superviseur TEXT DEFAULT NULL,
    signature_service TEXT DEFAULT NULL,

    validation ENUM('en_attente', 'valide', 'rework') DEFAULT 'en_attente',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: photos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    chemin_image VARCHAR(500) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Index
-- ------------------------------------------------------------
CREATE INDEX idx_tests_site ON tests(site);
CREATE INDEX idx_tests_liaison_id ON tests(liaison_id);
CREATE INDEX idx_tests_validation ON tests(validation);
CREATE INDEX idx_tests_created_by ON tests(created_by);
CREATE INDEX idx_tests_date_test ON tests(date_test);
CREATE INDEX idx_photos_test_id ON photos(test_id);

-- Le compte administrateur est créé via install.php avec password_hash()
