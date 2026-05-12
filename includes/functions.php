<?php
// ============================================================
// Fonctions utilitaires globales
// ============================================================

/**
 * Nettoie une chaîne pour affichage sécurisé (XSS)
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Nettoie les entrées utilisateur
 */
function clean(array $data): array
{
    $cleaned = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $cleaned[$key] = trim(strip_tags($value));
        } elseif (is_array($value)) {
            $cleaned[$key] = clean($value);
        } else {
            $cleaned[$key] = $value;
        }
    }
    return $cleaned;
}

/**
 * Raccourcit une chaîne
 */
function truncate(string $text, int $length = 50): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Formate une date
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date || $date === '0000-00-00') return '-';
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) {
        $dt = new DateTime($date);
    }
    return $dt->format($format);
}

/**
 * Formate une date heure
 */
function formatDatetime(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if (!$datetime) return '-';
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Retourne la classe CSS pour le statut de validation
 */
function validationBadge(string $status): string
{
    return match ($status) {
        'valide'     => 'badge badge--success',
        'rework'     => 'badge badge--danger',
        'en_attente' => 'badge badge--warning',
        default      => 'badge badge--info',
    };
}

/**
 * Retourne le libellé du statut
 */
function validationLabel(string $status): string
{
    return match ($status) {
        'valide'     => 'Validé',
        'rework'     => 'Rework',
        'en_attente' => 'En attente',
        default      => 'Inconnu',
    };
}

/**
 * Redirection sécurisée
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Message flash en session
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Affiche et efface les messages flash
 */
function renderFlash(): string
{
    if (empty($_SESSION['flash'])) return '';

    $html = '';
    foreach ($_SESSION['flash'] as $type => $message) {
        $icon = match ($type) {
            'success' => '✓',
            'error'   => '✗',
            'warning' => '⚠',
            'info'    => 'ℹ',
            default   => '',
        };
        $html .= '<div class="alert alert--' . $type . '">
            <span class="alert__icon">' . $icon . '</span>
            <span class="alert__message">' . h($message) . '</span>
            <button class="alert__close" onclick="this.parentElement.remove()">&times;</button>
        </div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/**
 * Génère un token CSRF
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie la méthode HTTP
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Retourne une valeur POST sécurisée
 */
function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Retourne une valeur GET sécurisée
 */
function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}
