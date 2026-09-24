<?php

declare(strict_types=1);

session_start();

$root = dirname(__DIR__);
$file = $root . '/config/config.php';

spl_autoload_register(function ($class) use ($root) {
    if (str_starts_with($class, 'App\\')) {
        $f = $root
            . '/app/'
            . str_replace('\\', '/', substr($class, 4))
            . '.php';

        if (is_file($f)) {
            require $f;
        }
    }
});

App\Core\Env::load($root . '/.env');

if (!is_file($file)) {
    http_response_code(503);
    exit('config.example.php を config.php にコピーしてください。');
}

$config = require $file;

date_default_timezone_set(
    $config['app']['timezone'] ?? 'Asia/Tokyo'
);

try {
    $visitorId = App\Core\VisitorIdCookie::resolve();
    $db = App\Core\Database::connect($config['db']);
    App\Core\SearchControllerFactory::create($db, $config, $visitorId)->index();

} catch (Throwable $e) {

    \App\Core\RequestLog::failure('application.failed', $e);

    http_response_code(500);

    echo '設定またはデータベースをご確認ください。';
}
