<?php
// ============================================================
// Classe Chantier - Gestion des chantiers
// ============================================================

class Chantier
{
    /**
     * Liste tous les chantiers
     */
    public static function list(string $statut = '', int $page = 1, int $perPage = 15): array
    {
        $where = '';
        $params = [];

        if ($statut) {
            $where = 'WHERE c.statut = :statut';
            $params[':statut'] = $statut;
        }

        $countStmt = Database::prepare("SELECT COUNT(*) FROM chantiers c {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $totalPages = max(1, (int)ceil($total / $perPage));

        $sql = "
            SELECT c.*, u.nom AS createur_nom,
                (SELECT COUNT(*) FROM chantier_techniciens ct WHERE ct.chantier_id = c.id) AS tech_count,
                (SELECT COUNT(*) FROM tests t WHERE t.chantier_id = c.id) AS test_count
            FROM chantiers c
            LEFT JOIN users u ON c.created_by = u.id
            {$where}
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = Database::prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'chantiers'   => $stmt->fetchAll(),
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => $totalPages,
        ];
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::prepare("
            SELECT c.*, u.nom AS createur_nom
            FROM chantiers c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::prepare("
            INSERT INTO chantiers (nom, description, adresse, statut, created_by)
            VALUES (:nom, :description, :adresse, :statut, :created_by)
        ");
        $stmt->execute([
            ':nom'        => $data['nom'] ?? '',
            ':description'=> $data['description'] ?? '',
            ':adresse'    => $data['adresse'] ?? '',
            ':statut'     => $data['statut'] ?? 'actif',
            ':created_by' => $data['created_by'] ?? null,
        ]);
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];
        foreach (['nom', 'description', 'adresse', 'statut'] as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        if (empty($sets)) return false;

        $params[':id'] = $id;
        $sql = 'UPDATE chantiers SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = Database::prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::prepare('DELETE FROM chantiers WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Gestion des techniciens assignés
    public static function getTechniciens(int $chantierId): array
    {
        $stmt = Database::prepare("
            SELECT u.id, u.nom, u.email, u.equipe, ct.assigned_at
            FROM chantier_techniciens ct
            JOIN users u ON ct.user_id = u.id
            WHERE ct.chantier_id = :cid
            ORDER BY u.nom
        ");
        $stmt->execute([':cid' => $chantierId]);
        return $stmt->fetchAll();
    }

    public static function assignTechnicien(int $chantierId, int $userId): void
    {
        $stmt = Database::prepare("
            INSERT IGNORE INTO chantier_techniciens (chantier_id, user_id) VALUES (:cid, :uid)
        ");
        $stmt->execute([':cid' => $chantierId, ':uid' => $userId]);
    }

    public static function removeTechnicien(int $chantierId, int $userId): void
    {
        $stmt = Database::prepare("
            DELETE FROM chantier_techniciens WHERE chantier_id = :cid AND user_id = :uid
        ");
        $stmt->execute([':cid' => $chantierId, ':uid' => $userId]);
    }

    // Techniciens disponibles (non encore assignés)
    public static function getAvailableTechniciens(int $chantierId): array
    {
        $stmt = Database::prepare("
            SELECT id, nom, email FROM users
            WHERE role = 'technicien'
            AND id NOT IN (
                SELECT user_id FROM chantier_techniciens WHERE chantier_id = :cid
            )
            ORDER BY nom
        ");
        $stmt->execute([':cid' => $chantierId]);
        return $stmt->fetchAll();
    }

    // Tous les techniciens (pour les formulaires)
    public static function getAllTechniciens(): array
    {
        $stmt = Database::prepare("
            SELECT id, nom, email FROM users WHERE role = 'technicien' ORDER BY nom
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Tests liés à un chantier
    public static function getTests(int $chantierId): array
    {
        $stmt = Database::prepare("
            SELECT t.*, u.nom AS createur_nom
            FROM tests t
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.chantier_id = :cid
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([':cid' => $chantierId]);
        return $stmt->fetchAll();
    }

    // Stats pour le dashboard chantier
    public static function getStats(int $chantierId): array
    {
        $stmt = Database::prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN validation = 'valide' THEN 1 ELSE 0 END) AS valide,
                SUM(CASE WHEN validation = 'en_attente' THEN 1 ELSE 0 END) AS attente,
                SUM(CASE WHEN validation = 'rework' THEN 1 ELSE 0 END) AS rework
            FROM tests WHERE chantier_id = :cid
        ");
        $stmt->execute([':cid' => $chantierId]);
        return $stmt->fetch() ?: ['total'=>0,'valide'=>0,'attente'=>0,'rework'=>0];
    }
}
