<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Env.php';

use App\Env;

Env::load(dirname(__DIR__) . '/.env');
$url = Env::get('DATABASE_URL');
$parts = parse_url($url);
$pass = rawurldecode((string) ($parts['pass'] ?? ''));

echo 'host=' . ($parts['host'] ?? 'MISSING') . PHP_EOL;
echo 'port=' . ($parts['port'] ?? '') . PHP_EOL;
echo 'user=' . ($parts['user'] ?? '') . PHP_EOL;
echo 'pass_len=' . strlen($pass) . PHP_EOL;
echo 'placeholder=' . (stripos($url, 'YOUR-PASSWORD') !== false ? 'yes' : 'no') . PHP_EOL;
echo 'ssl=' . ((isset($parts['query']) && strpos($parts['query'], 'sslmode') !== false) ? 'yes' : 'no') . PHP_EOL;
$encoded = rawurlencode($pass);
echo 'needs_urlencode=' . ($encoded !== $pass ? 'yes' : 'no') . PHP_EOL;
echo 'parse_pass_ok=' . (isset($parts['pass']) ? 'yes' : 'no') . PHP_EOL;
