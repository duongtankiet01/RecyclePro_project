<?php
/**
 * Simple Database connection wrapper using PDO.
 * Update the DSN, username, and password to match your MySQL setup.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $configPath = __DIR__ . '/../config/database.php';
            if (!file_exists($configPath)) {
                throw new RuntimeException('Missing database configuration file at config/database.php');
            }

            /** @var array{host:string,port:int,name:string,user:string,password:string,charset:string} $config */
            $config = require $configPath;

            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $db   = $config['name'] ?? '';
            $user = $config['user'] ?? 'root';
            $pass = $config['password'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';

            if ($db === '') {
                throw new RuntimeException('Database name is empty. Please update config/database.php.');
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                // Explicitly set charset to ensure UTF-8 encoding
                self::$instance->exec("SET NAMES '{$charset}'");
                self::$instance->exec("SET CHARACTER SET '{$charset}'");
            } catch (PDOException $e) {
                if ((int)$e->getCode() === 1049) {
                    die('Database connection failed: Unknown database. Please create the database "' . htmlspecialchars($db) . '" or update config/database.php.');
                }

                die('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
