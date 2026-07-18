<?php
declare(strict_types=1);

function hoteltv_run_migrations(PDO $pdo, $driver, $migrationDirectory)
{
    $id = $driver === 'mysql' ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id {$id}, migration VARCHAR(255) NOT NULL UNIQUE, applied_at VARCHAR(255) NOT NULL)");
    $files = glob($migrationDirectory . '/*.php');
    sort($files);
    foreach ($files as $file) {
        $name = basename($file);
        $statement = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
        $statement->execute(array($name));
        if ((int)$statement->fetchColumn() > 0) {
            continue;
        }
        $pdo->beginTransaction();
        try {
            $migration = require $file;
            $migration($pdo, $driver);
            $statement = $pdo->prepare('INSERT INTO migrations (migration, applied_at) VALUES (?, ?)');
            $statement->execute(array($name, gmdate('c')));
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
