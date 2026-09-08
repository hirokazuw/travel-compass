<?php
// Isolated HTTP entry point: no production configuration or external services.
$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
$_SESSION = ['csrf' => 'test-token'];
$config = [];
$visitorId = 'test-visitor';
$db = new PDO('sqlite::memory:');
ob_start();
App\Core\SearchControllerFactory::create($db, $config, $visitorId)->index();
$body = ob_get_clean();
header('X-Fixture-Csrf: ' . $_SESSION['csrf']);
echo $body;
