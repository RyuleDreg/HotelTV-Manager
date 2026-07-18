<?php
declare(strict_types=1);

final class Database
{
    /** @var PDO */
    private $pdo;

    public function __construct(array $config)
    {
        $driver = isset($config['driver']) ? $config['driver'] : 'sqlite';
        if ($driver === 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                throw new RuntimeException('PHP extension pdo_sqlite is required for SQLite.');
            }
            $path = $config['sqlite_path'];
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create database directory: ' . $directory);
            }
            $this->pdo = new PDO('sqlite:' . $path);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
        } elseif ($driver === 'mysql') {
            if (!extension_loaded('pdo_mysql')) {
                throw new RuntimeException('PHP extension pdo_mysql is required for MySQL/MariaDB.');
            }
            $mysql = $config['mysql'];
            $charset = isset($mysql['charset']) ? $mysql['charset'] : 'utf8mb4';
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $mysql['host'], $mysql['port'], $mysql['database'], $charset);
            $this->pdo = new PDO($dsn, $mysql['username'], $mysql['password']);
        } else {
            throw new InvalidArgumentException('Unsupported database driver.');
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function pdo()
    {
        return $this->pdo;
    }
}
