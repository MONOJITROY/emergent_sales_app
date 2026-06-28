<?php
namespace App\Core;

final class Auth
{
    public static function check(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function user(): array
    {
        $u = self::check();
        if (!$u) {
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
            } else {
                $base = App::config('base_url') ?: '';
                header('Location: ' . $base . '/login');
            }
            exit;
        }
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::user();
        if (($u['role'] ?? '') !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Admin access required']);
            exit;
        }
        return $u;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'email' => $user['email'],
            'name'  => $user['name'],
            'role'  => $user['role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
