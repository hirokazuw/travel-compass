<?php
namespace App\Core;
use PDO;
final class Database { public static function connect(array $c): PDO { return new PDO($c['dsn'],$c['user'],$c['password'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false
]);}}
