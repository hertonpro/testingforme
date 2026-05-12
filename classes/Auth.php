<?php
// ============================================================
// Classe Auth - Authentification et gestion des sessions
// ============================================================

class Auth
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function login(string $email, string $password): bool
    {
        $stmt = Database::prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            if (password_needs_rehash($user['mot_de_passe'], PASSWORD_BCRYPT)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $update = Database::prepare('UPDATE users SET mot_de_passe = :hash WHERE id = :id');
                $update->execute([':hash' => $newHash, ':id' => $user['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['user_nom']   = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['logged_in']  = true;
            $_SESSION['ip']         = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['last_activity'] = time();

            return true;
        }
        return false;
    }

    public static function check(): bool
    {
        if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }

        // Vérification session hijacking
        if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            self::logout();
            return false;
        }

        // Expiration session
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireAuth(): void
    {
        self::init();
        if (!self::check()) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    public static function hasRole(string $role): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if (!self::hasRole($role)) {
            header('Location: index.php?page=dashboard&error=Accès non autorisé');
            exit;
        }
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'    => $_SESSION['user_id'],
            'nom'   => $_SESSION['user_nom'],
            'email' => $_SESSION['user_email'],
            'role'  => $_SESSION['user_role'],
        ];
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
