<?php
// ============================================================
// Classe PDF - Génération de rapports PDF professionnels
// Utilise TCPDF
// ============================================================

require_once BASE_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';

class PDF extends TCPDF
{
    private string $siteName;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);

        $this->siteName = defined('APP_ORG') ? APP_ORG : 'HGR de Panzi';

        $this->SetCreator(defined('APP_NAME') ? APP_NAME : 'Testing Réseau VDI');
        $this->SetAuthor($this->siteName . ' - Service Technique');
        $this->SetTitle('Rapport de Test Réseau');
        $this->SetSubject('Fiche de Test Unitaire - Liaison Réseau VDI');

        // Marges
        $this->SetMargins(15, 35, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(20);

        // Police par défaut
        $this->SetDefaultMonospacedFont('courier');
        $this->SetFont('helvetica', '', 9);

        // Métadonnées
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
    }

    public function Header(): void
    {
        $logoFile = BASE_PATH . 'assets/images/logo.png';
        $logoPath = file_exists($logoFile) ? $logoFile : '';

        // Logo
        if ($logoPath) {
            $this->Image($logoPath, 15, 8, 25);
        }

        // Titre
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(45, 8);
        $this->Cell(0, 6, defined('APP_ORG') ? APP_ORG : 'HGR de Panzi', 0, 1, 'L');

        $this->SetFont('helvetica', '', 9);
        $this->SetXY(45, 14);
        $this->Cell(0, 5, defined('APP_ORG_SUBTITLE') ? APP_ORG_SUBTITLE : 'Service Technique - Réseau VDI', 0, 1, 'L');

        $this->SetXY(45, 19);
        $this->Cell(0, 5, defined('APP_DOC_TITLE') ? APP_DOC_TITLE : "FICHE DE TEST UNITAIRE - LIAISON RÉSEAU", 0, 1, 'L');

        // Numéro de page
        $this->SetFont('helvetica', 'I', 8);
        $this->SetXY(-35, 10);
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 1, 'R');

        // Ligne de séparation
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.5);
        $this->Line(15, 28, 195, 28);
    }

    public function Footer(): void
    {
        $this->SetY(-18);
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(100, 100, 100);

        $org = defined('APP_ORG') ? APP_ORG : 'HGR de Panzi';
        $subtitle = defined('APP_ORG_SUBTITLE') ? APP_ORG_SUBTITLE : 'Service Technique';
        $this->Cell(0, 5, $org . ' - ' . $subtitle . ' - Document officiel - Généré le ' . date('d/m/Y H:i'), 0, 0, 'C');

        // Ligne
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.3);
        $this->Line(15, -20, 195, -20);
    }

    /**
     * Génère un rapport PDF complet pour un test
     */
    public function generateReport(array $test, array $photos = []): string
    {
        $this->AddPage();
        $this->SetFont('helvetica', '', 9);

        $this->buildIdentification($test);
        $this->buildVisualControl($test);
        $this->buildContinuity($test);
        $this->buildLength($test);
        $this->buildPerformance($test);
        $this->buildConnectivity($test);
        $this->buildElectrical($test);
        $this->buildConformity($test);
        $this->buildObservations($test);
        $this->buildPhotos($photos);
        $this->buildValidation($test);

        return $this->Output('', 'S');
    }

    private function sectionTitle(string $title): void
    {
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(0, 51, 102);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 7, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->Ln(2);
    }

    private function fieldRow(string $label, string $value): void
    {
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(65, 5, $label . ' :', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, $value, 0, 1, 'L');
    }

    private function checkRow(string $label, bool $checked): void
    {
        $status = $checked ? '✓ OUI' : '✗ NON';
        $this->SetFont('helvetica', '', 9);
        $this->Cell(5, 5, '', 0, 0);
        $this->Cell(0, 5, $label . ' : ' . $status, 0, 1);
    }

    private function buildIdentification(array $t): void
    {
        $this->sectionTitle('1. IDENTIFICATION DE LA LIAISON');
        $this->fieldRow('Site', $t['site'] ?? (defined('APP_SITE_DEFAULT') ? APP_SITE_DEFAULT : 'HGR de Panzi'));
        $this->fieldRow('Bâtiment', $t['batiment'] ?? '-');
        $this->fieldRow('Service', $t['service'] ?? '-');
        $this->fieldRow('Localisation point A (Patch Panel)', $t['localisation_a'] ?? '-');
        $this->fieldRow('Localisation point B (Prise RJ45)', $t['localisation_b'] ?? '-');
        $this->fieldRow('N° de câble / ID liaison', $t['liaison_id'] ?? '-');
        $this->fieldRow('Catégorie câble', $t['cable_categorie'] ?? 'Cat 6A');
        $this->fieldRow('Type de liaison', $t['type_liaison'] ?? '-');
        $this->Ln(3);
    }

    private function buildVisualControl(array $t): void
    {
        $this->sectionTitle('2. CONTRÔLE VISUEL DE L\'INSTALLATION');
        $this->checkRow('Cheminement conforme', (bool)($t['cheminement_conforme'] ?? false));
        $this->checkRow('Respect des rayons de courbure', (bool)($t['respect_courbure'] ?? false));
        $this->checkRow('Étiquetage des deux extrémités', (bool)($t['etiquetage_present'] ?? false));
        $this->checkRow('Boîtier RJ45 correctement fixé', (bool)($t['boitier_fixe'] ?? false));
        $this->checkRow('Patch panel correctement raccordé', (bool)($t['patch_panel_raccorde'] ?? false));
        $this->checkRow('Absence de contrainte mécanique', (bool)($t['absence_contrainte'] ?? false));
        $this->Ln(3);
    }

    private function buildContinuity(array $t): void
    {
        $this->sectionTitle('3. TEST DE CONTINUITÉ');
        $this->checkRow('Continuité paire 1-2', (bool)($t['continuite_paire_1_2'] ?? false));
        $this->checkRow('Continuité paire 3-6', (bool)($t['continuite_paire_3_6'] ?? false));
        $this->checkRow('Continuité paire 4-5', (bool)($t['continuite_paire_4_5'] ?? false));
        $this->checkRow('Continuité paire 7-8', (bool)($t['continuite_paire_7_8'] ?? false));

        $this->Ln(1);
        $this->fieldRow('Inversion de paires', ($t['inversion_paires'] ?? false) ? 'Détectée' : 'Non détectée');
        $this->fieldRow('Court-circuit', ($t['court_circuit'] ?? false) ? 'Oui' : 'Non');
        $this->fieldRow('Circuit ouvert', ($t['circuit_ouvert'] ?? false) ? 'Oui' : 'Non');
        $this->fieldRow('Split pair', ($t['split_pair'] ?? false) ? 'Oui' : 'Non');
        $this->Ln(3);
    }

    private function buildLength(array $t): void
    {
        $this->sectionTitle('4. MESURE DE LONGUEUR');
        $this->fieldRow('Longueur estimée', ($t['longueur_estimee'] ?? '') . ' mètres');
        $this->checkRow('Longueur conforme Cat 6A (< 90m)', (bool)($t['longueur_conforme'] ?? false));
        $this->Ln(3);
    }

    private function buildPerformance(array $t): void
    {
        $this->sectionTitle('5. TEST DE PERFORMANCE (iPerf3)');
        $this->fieldRow('Débit descendant', $t['debit_descendant'] ?? '-');
        $this->fieldRow('Débit montant', $t['debit_montant'] ?? '-');
        $this->fieldRow('Latence', $t['latence'] ?? '-');
        $this->fieldRow('Perte de paquets', $t['perte_paquets'] ?? '-');
        $this->checkRow('Stabilité du lien', (bool)($t['stabilite_lien'] ?? false));
        $this->Ln(3);
    }

    private function buildConnectivity(array $t): void
    {
        $this->sectionTitle('6. TEST DE CONNECTIVITÉ RÉSEAU');
        $this->checkRow('Obtention adresse IP', (bool)($t['ip_obtenue'] ?? false));
        $this->checkRow('Accès passerelle', (bool)($t['acces_passerelle'] ?? false));
        $this->checkRow('Accès serveur local', (bool)($t['acces_serveur_local'] ?? false));
        $this->checkRow('Accès Internet', (bool)($t['acces_internet'] ?? false));
        $this->Ln(3);
    }

    private function buildElectrical(array $t): void
    {
        $this->sectionTitle('7. CONTRÔLE ÉLECTRIQUE / ENVIRONNEMENT');
        $this->fieldRow('Interférences électriques', ($t['interferences_electriques'] ?? false) ? 'Oui' : 'Non');
        $this->fieldRow('Proximité câble énergie (<30cm)', ($t['proximite_cable_energie'] ?? false) ? 'Oui' : 'Non');
        $this->fieldRow('Terre fonctionnelle disponible', $t['terre_fonctionnelle'] ?? 'N/A');
        $this->Ln(3);
    }

    private function buildConformity(array $t): void
    {
        $this->sectionTitle('8. CONFORMITÉ GÉNÉRALE');
        $this->checkRow('Liaison conforme aux normes Cat 6A', (bool)($t['conforme_normes'] ?? false));
        $this->checkRow('Liaison validée pour mise en production', (bool)($t['validee_production'] ?? false));
        $this->checkRow('Rework nécessaire', (bool)($t['rework_necessaire'] ?? false));

        // Statut validation
        $this->Ln(2);
        $validationLabels = [
            'en_attente' => 'En attente de validation',
            'valide'     => 'Validé',
            'rework'     => 'Rework nécessaire',
        ];
        $statusLabel = $validationLabels[$t['validation'] ?? 'en_attente'] ?? 'Inconnu';

        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(230, 240, 255);
        $this->Cell(0, 7, '  Statut global : ' . strtoupper($statusLabel), 0, 1, 'L', true);
        $this->SetFont('helvetica', '', 9);
        $this->Ln(3);
    }

    private function buildObservations(array $t): void
    {
        $obs = $t['observations'] ?? '';
        if (empty(trim($obs))) return;

        $this->sectionTitle('9. OBSERVATIONS');
        $this->SetFont('helvetica', '', 9);
        $this->MultiCell(0, 5, $obs, 0, 'L');
        $this->Ln(3);
    }

    private function buildPhotos(array $photos): void
    {
        if (empty($photos)) return;

        $this->sectionTitle('10. DOCUMENTATION PHOTOGRAPHIQUE');

        $margin = $this->lMargin;
        $pageW = $this->getPageWidth() - $this->lMargin - $this->rMargin;
        $maxImgW = min(150, $pageW); // largeur max image en mm

        foreach ($photos as $photo) {
            $filePath = BASE_PATH . $photo['chemin_image'];
            if (!file_exists($filePath)) continue;

            // Obtenir dimensions réelles
            list($w, $h) = getimagesize($filePath);
            if (!$w || !$h) continue;

            // Calculer les dimensions d'affichage
            $ratio = min($maxImgW / $w, 1);
            $imgW = $w * $ratio;
            $imgH = $h * $ratio;

            // Vérifier la place disponible, nouvelle page si nécessaire
            $spaceNeeded = $imgH + 15;
            if ($this->GetY() + $spaceNeeded > $this->getPageHeight() - $this->bMargin) {
                $this->AddPage();
            }

            // Centrer horizontalement
            $x = $margin + ($pageW - $imgW) / 2;
            $yBefore = $this->GetY();

            // Placer l'image
            $this->Image($filePath, $x, $yBefore, $imgW, $imgH);

            // Avancer Y après l'image
            $this->SetY($yBefore + $imgH + 4);

            // Description sous l'image
            if (!empty(trim($photo['description'] ?? ''))) {
                $this->SetFont('helvetica', 'I', 8);
                $this->SetTextColor(100, 100, 100);
                $this->SetX($margin + 5);
                $this->MultiCell($pageW - 10, 4, $photo['description'], 0, 'C');
                $this->SetTextColor(0, 0, 0);
                $this->Ln(2);
            }
        }
        $this->Ln(3);
    }

    private function buildValidation(array $t): void
    {
        $this->sectionTitle('11. VALIDATION');

        $this->fieldRow('Test réalisé par', $t['test_realise_par'] ?? '-');
        $this->fieldRow('Équipe / Entreprise', $t['equipe_validation'] ?? '-');
        $this->fieldRow('Date du test', $t['date_test'] ?? date('d/m/Y'));

        $this->Ln(5);

        // Signatures
        $this->SetFont('helvetica', 'B', 9);

        $sigY = $this->GetY();
        $this->SetFont('helvetica', '', 9);

        $this->Cell(60, 5, 'Technicien test :', 0, 0, 'L');
        $this->Cell(60, 5, 'Superviseur technique :', 0, 0, 'L');
        $this->Cell(60, 5, 'Service technique HGR :', 0, 1, 'L');

        $this->Ln(10);

        $this->Cell(60, 5, '_______________________', 0, 0, 'C');
        $this->Cell(60, 5, '_______________________', 0, 0, 'C');
        $this->Cell(60, 5, '_______________________', 0, 1, 'C');
    }
}
