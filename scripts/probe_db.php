<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Env.php';

use App\Env;

Env::load(dirname(__DIR__) . '/.env');

$url = Env::get('DATABASE_URL');
$parts = parse_url($url);
if ($parts === false || !isset($parts['host'], $parts['user'], $parts['pass'], $parts['path'])) {
    fwrite(STDERR, "parse_url failed\n");
    exit(1);
}

$host = $parts['host'];
$port = (string) ($parts['port'] ?? 5432);
$user = rawurldecode($parts['user']);
$pass = rawurldecode($parts['pass']);
$db = ltrim($parts['path'], '/');

$ca = 'D:\\xampp\\apache\\bin\\curl-ca-bundle.crt';
putenv('PGSSLROOTCERT=' . $ca);

$attempts = [
    'pooler_5432' => [
        'dsn' => sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', $host, $port, $db),
        'user' => $user,
    ],
    'pooler_6543' => [
        'dsn' => sprintf('pgsql:host=%s;port=6543;dbname=%s;sslmode=require', $host, $db),
        'user' => $user,
    ],
    'direct_db' => [
        'dsn' => sprintf('pgsql:host=db.%s.supabase.co;port=5432;dbname=%s;sslmode=require', substr($user, strlen('postgres.')), $db),
        'user' => 'postgres',
    ],
];

foreach ($attempts as $name => $cfg) {
    echo $name . ': ';
    try {
        $pdo = new PDO($cfg['dsn'], $cfg['user'], $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 8,
        ]);
        $one = $pdo->query('SELECT current_user, current_database()')->fetch(PDO::FETCH_ASSOC);
        echo 'ok user=' . ($one['current_user'] ?? '?') . ' db=' . ($one['current_database'] ?? '?') . PHP_EOL;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        $msg = preg_replace('/:[^:@]+@/', ':***@', $msg);
        echo 'FAIL ' . $msg . PHP_EOL;
    }
}
