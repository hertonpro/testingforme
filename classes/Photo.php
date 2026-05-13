<?php
// ============================================================
// Classe Photo - Gestion des photos avec compression
// ============================================================

class Photo
{
    /**
     * Upload une ou plusieurs photos avec compression
     */
    public static function upload(array $files, int $testId, array $descriptions = []): array
    {
        $uploaded = [];
        $errors = [];

        // Créer le dossier si nécessaire
        $uploadDir = PHOTO_PATH . $testId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Normaliser en tableau multidimensionnel
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        $fileArray = [];
        for ($i = 0; $i < $fileCount; $i++) {
            $fileArray[] = [
                'name'     => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                'type'     => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error'    => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size'     => is_array($files['size']) ? $files['size'][$i] : $files['size'],
            ];
        }

        foreach ($fileArray as $index => $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur upload fichier {$index}: code {$file['error']}";
                continue;
            }

            // Validation taille
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = "Fichier {$file['name']} trop volumineux (max 10 Mo)";
                continue;
            }

            // Validation extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                $errors[] = "Extension .{$ext} non autorisée pour {$file['name']}";
                continue;
            }

            // Validation MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowedMimes)) {
                $errors[] = "Type MIME invalide pour {$file['name']}";
                continue;
            }

            // Nom de fichier unique
            $newName = $testId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;

            // Compression et conversion WebP
            $success = self::compressAndConvert($file['tmp_name'], $destPath);
            if (!$success) {
                $errors[] = "Erreur compression pour {$file['name']}";
                continue;
            }

            // Chemin relatif pour la base
            $relativePath = 'uploads/photos/' . $testId . '/' . $newName;

            // Sauvegarde en base
            $desc = $descriptions[$index] ?? '';
            $photoId = self::saveToDb($testId, $relativePath, $desc);

            $uploaded[] = [
                'id'           => $photoId,
                'chemin_image' => $relativePath,
                'description'  => $desc,
            ];
        }

        return ['uploaded' => $uploaded, 'errors' => $errors];
    }

    /**
     * Compression intelligente et conversion en WebP
     */
    private static function compressAndConvert(string $source, string $destination): bool
    {
        $info = getimagesize($source);
        if (!$info) return false;

        list($width, $height) = $info;

        // Redimensionnement intelligent si nécessaire
        $ratio = 1;
        if ($width > PHOTO_MAX_WIDTH || $height > PHOTO_MAX_HEIGHT) {
            $ratio = min(PHOTO_MAX_WIDTH / $width, PHOTO_MAX_HEIGHT / $height);
            $newWidth  = (int)round($width * $ratio);
            $newHeight = (int)round($height * $ratio);
        } else {
            $newWidth  = $width;
            $newHeight = $height;
        }

        // Créer l'image source
        switch ($info['mime']) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($source);
                break;
            case 'image/webp':
                $srcImage = @imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$srcImage) return false;

        // Redimensionner
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($dstImage, 0, 0, imagecolorallocate($dstImage, 255, 255, 255));
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Conversion WebP avec qualité ajustable
        $quality = PHOTO_QUALITY;
        $result = imagewebp($dstImage, $destination, $quality);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $result;
    }

    /**
     * Sauvegarde le chemin en base de données
     */
    private static function saveToDb(int $testId, string $path, string $description = ''): int
    {
        $stmt = Database::prepare('INSERT INTO photos (test_id, chemin_image, description) VALUES (:test_id, :chemin, :description)');
        $stmt->execute([
            ':test_id'     => $testId,
            ':chemin'      => $path,
            ':description' => $description,
        ]);
        return (int)Database::lastInsertId();
    }

    /**
     * Récupère les photos d'un test
     */
    public static function getByTestId(int $testId): array
    {
        $stmt = Database::prepare('SELECT * FROM photos WHERE test_id = :test_id ORDER BY created_at ASC');
        $stmt->execute([':test_id' => $testId]);
        return $stmt->fetchAll();
    }

    /**
     * Supprime une photo
     */
    public static function delete(int $photoId): bool
    {
        $stmt = Database::prepare('SELECT chemin_image FROM photos WHERE id = :id');
        $stmt->execute([':id' => $photoId]);
        $photo = $stmt->fetch();

        if ($photo) {
            $filePath = BASE_PATH . $photo['chemin_image'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            $del = Database::prepare('DELETE FROM photos WHERE id = :id');
            return $del->execute([':id' => $photoId]);
        }
        return false;
    }
}
