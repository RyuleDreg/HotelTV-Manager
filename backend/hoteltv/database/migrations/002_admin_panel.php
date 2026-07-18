<?php
declare(strict_types=1);
return function (PDO $pdo, $driver) {
    $text = $driver === 'mysql' ? 'VARCHAR(255)' : 'TEXT';
    $long = $driver === 'mysql' ? 'LONGTEXT' : 'TEXT';
    $id = $driver === 'mysql' ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $pdo->exec("CREATE TABLE IF NOT EXISTS remote_commands (id {$id}, device_id BIGINT NOT NULL, command_type {$text} NOT NULL, payload_json {$long} NULL, status {$text} NOT NULL DEFAULT 'pending', created_at {$text} NOT NULL, completed_at {$text} NULL, FOREIGN KEY(device_id) REFERENCES devices(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS device_events (id {$id}, device_id BIGINT NOT NULL, event_type {$text} NOT NULL, details_json {$long} NULL, created_at {$text} NOT NULL, FOREIGN KEY(device_id) REFERENCES devices(id) ON DELETE CASCADE)");
    try { $pdo->exec("CREATE UNIQUE INDEX rooms_property_number_unique ON rooms(property_id, room_number)"); } catch (Throwable $e) {}
};
