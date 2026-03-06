<?php
// config/database.php
 
class Database {
    private static ?PDO $instance = null;
 
    private static array $config = [
        'host'    => 'localhost',
        'dbname'  => 'api_demo',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ];
 
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                self::$config['host'],
                self::$config['dbname'],
                self::$config['charset']
            );
            self::$instance = new PDO($dsn,
                self::$config['user'],
                self::$config['pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
        return self::$instance;
    }
}
