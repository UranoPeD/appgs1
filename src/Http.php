<?php

declare(strict_types=1);

namespace App;

final class Http
{
    public static function redirect(string $to): void
    {
        header('Location: ' . $to, true, 302);
        exit;
    }

    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return strtoupper((string) $method);
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        return $path === '' ? '/' : $path;
    }

    public static function post(string $key, string $default = ''): string
    {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
            return $default;
        }
        return trim($_POST[$key]);
    }

    /**
     * @return array<string, string>
     */
    public static function postArray(string $key): array
    {
        if (!isset($_POST[$key]) || !is_array($_POST[$key])) {
            return [];
        }
        $out = [];
        foreach ($_POST[$key] as $name => $value) {
            if (is_string($value)) {
                $out[(string) $name] = trim($value);
            }
        }
        return $out;
    }
}
