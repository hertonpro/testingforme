<?php
// ============================================================
// Classe Test - Gestion des fiches de test réseau
// ============================================================

class Test
{
    /**
     * Crée un nouveau test
     */
    public static function create(array $data): int
    {
        $pdo = Database::getInstance();

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO tests ({$columns}) VALUES ({$placeholders})";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Met à jour un test existant
     */
    public static function update(int $id, array $data): bool
    {
        // Exclure 'id' des colonnes à mettre à jour
        unset($data['id'], $data[':id']);

        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
        }

        $sql = 'UPDATE tests SET ' . implode(', ', $sets) . ' WHERE id = :id';

        $stmt = Database::prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Récupère un test par son ID
     */
    public static function findById(int $id): ?array
    {
        $stmt = Database::prepare('
            SELECT t.*, u.nom AS createur_nom
            FROM tests t
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $test = $stmt->fetch();
        return $test ?: null;
    }

    /**
     * Liste paginée des tests avec filtres
     */
    public static function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(t.site LIKE :search OR t.batiment LIKE :search2 OR t.liaison_id LIKE :search3 OR t.service LIKE :search4)';
            $s = '%' . $filters['search'] . '%';
            $params[':search'] = $s;
            $params[':search2'] = $s;
            $params[':search3'] = $s;
            $params[':search4'] = $s;
        }

        if (!empty($filters['validation'])) {
            $where[] = 't.validation = :validation';
            $params[':validation'] = $filters['validation'];
        }

        if (!empty($filters['site'])) {
            $where[] = 't.site LIKE :site';
            $params[':site'] = '%' . $filters['site'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 't.date_test >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 't.date_test <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['created_by'])) {
            $where[] = 't.created_by = :created_by';
            $params[':created_by'] = (int)$filters['created_by'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countSql = "SELECT COUNT(*) FROM tests t {$whereClause}";
        $countStmt = Database::prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Pagination
        $offset = ($page - 1) * $perPage;
        $totalPages = max(1, (int)ceil($total / $perPage));

        // Results
        $sql = "
            SELECT t.*, u.nom AS createur_nom,
                (SELECT COUNT(*) FROM photos p WHERE p.test_id = t.id) AS photo_count
            FROM tests t
            LEFT JOIN users u ON t.created_by = u.id
            {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = Database::prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'tests'       => $stmt->fetchAll(),
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => $totalPages,
        ];
    }

    /**
     * Supprime un test
     */
    public static function delete(int $id): bool
    {
        // Supprimer les photos physiquement
        $photos = Photo::getByTestId($id);
        foreach ($photos as $photo) {
            $filePath = BASE_PATH . $photo['chemin_image'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $stmt = Database::prepare('DELETE FROM tests WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Récupère les statistiques du tableau de bord
     */
    public static function getStats(?int $userId = null): array
    {
        $userCondition = $userId ? 'WHERE created_by = :user_id' : '';

        $stats = [
            'total'    => 0,
            'valide'   => 0,
            'attente'  => 0,
            'rework'   => 0,
        ];

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN validation = 'valide' THEN 1 ELSE 0 END) AS valide,
                    SUM(CASE WHEN validation = 'en_attente' THEN 1 ELSE 0 END) AS attente,
                    SUM(CASE WHEN validation = 'rework' THEN 1 ELSE 0 END) AS rework
                FROM tests {$userCondition}";

        $stmt = Database::prepare($sql);
        if ($userId) {
            $stmt->execute([':user_id' => $userId]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetch() ?: $stats;
    }

    /**
     * Récupère les derniers tests
     */
    public static function getRecent(int $limit = 5, ?int $userId = null): array
    {
        $userCondition = $userId ? 'WHERE t.created_by = :user_id' : '';
        $sql = "
            SELECT t.*, u.nom AS createur_nom
            FROM tests t
            LEFT JOIN users u ON t.created_by = u.id
            {$userCondition}
            ORDER BY t.created_at DESC
            LIMIT :limit
        ";

        $stmt = Database::prepare($sql);
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Récupère tous les tests (pour export global)
     */
    public static function getAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['validation'])) {
            $where[] = 'validation = :validation';
            $params[':validation'] = $filters['validation'];
        }
        if (!empty($filters['site'])) {
            $where[] = 'site LIKE :site';
            $params[':site'] = '%' . $filters['site'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'date_test >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'date_test <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT t.*, u.nom AS createur_nom
                FROM tests t
                LEFT JOIN users u ON t.created_by = u.id
                {$whereClause}
                ORDER BY t.created_at DESC";

        $stmt = Database::prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
