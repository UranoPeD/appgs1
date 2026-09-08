<?php

declare(strict_types=1);

namespace App;

final class AdminAuth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $https,
        ]);
        session_start();
    }

    public static function configured(): bool
    {
        return Env::get('APP_ADMIN_TOKEN') !== '';
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['admin']);
    }

    public static function attempt(string $token): bool
    {
        self::start();
        $expected = Env::get('APP_ADMIN_TOKEN');
        if ($expected === '' || !hash_equals($expected, $token)) {
            return false;
        }
        $_SESSION['admin'] = true;
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/');
        }
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfOk(?string $token): bool
    {
        self::start();
        $expected = isset($_SESSION['csrf']) && is_string($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
        if ($expected === '' || $token === null || $token === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }
}
