<?php
declare(strict_types=1);

return function (PDO $pdo, $driver) {
    $id = $driver === 'mysql' ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $text = $driver === 'mysql' ? 'VARCHAR(255)' : 'TEXT';
    $long = $driver === 'mysql' ? 'LONGTEXT' : 'TEXT';

    $pdo->exec("CREATE TABLE IF NOT EXISTS administrators (id {$id}, email {$text} NOT NULL UNIQUE, password_hash {$text} NOT NULL, role {$text} NOT NULL DEFAULT 'system_admin', created_at {$text} NOT NULL, updated_at {$text} NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS properties (id {$id}, uuid {$text} NOT NULL UNIQUE, name {$text} NOT NULL, timezone {$text} NOT NULL DEFAULT 'UTC', country {$text} NULL, language {$text} NOT NULL DEFAULT 'en', created_at {$text} NOT NULL, updated_at {$text} NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS floors (id {$id}, property_id BIGINT NOT NULL, name {$text} NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0, created_at {$text} NOT NULL, updated_at {$text} NOT NULL, FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (id {$id}, property_id BIGINT NOT NULL, floor_id BIGINT NULL, room_number {$text} NOT NULL, display_name {$text} NULL, created_at {$text} NOT NULL, updated_at {$text} NOT NULL, FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE, FOREIGN KEY(floor_id) REFERENCES floors(id) ON DELETE SET NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS devices (id {$id}, uuid {$text} NOT NULL UNIQUE, property_id BIGINT NULL, room_id BIGINT NULL, display_name {$text} NULL, device_model {$text} NULL, android_version {$text} NULL, app_version {$text} NULL, status {$text} NOT NULL DEFAULT 'pending', last_seen_at {$text} NULL, configuration_version INTEGER NOT NULL DEFAULT 1, activated_at {$text} NULL, created_at {$text} NOT NULL, updated_at {$text} NOT NULL, FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE SET NULL, FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE SET NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS activation_codes (id {$id}, device_id BIGINT NOT NULL, code {$text} NOT NULL UNIQUE, expires_at {$text} NOT NULL, claimed_at {$text} NULL, created_at {$text} NOT NULL, FOREIGN KEY(device_id) REFERENCES devices(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS device_tokens (id {$id}, device_id BIGINT NOT NULL UNIQUE, token_hash {$text} NOT NULL UNIQUE, created_at {$text} NOT NULL, revoked_at {$text} NULL, FOREIGN KEY(device_id) REFERENCES devices(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS iptv_accounts (id {$id}, property_id BIGINT NOT NULL, name {$text} NOT NULL, server_url {$text} NOT NULL, username_encrypted {$long} NOT NULL, password_encrypted {$long} NOT NULL, active INTEGER NOT NULL DEFAULT 1, created_at {$text} NOT NULL, updated_at {$text} NOT NULL, FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS playlist_profiles (id {$id}, property_id BIGINT NOT NULL, iptv_account_id BIGINT NULL, name {$text} NOT NULL, settings_json {$long} NOT NULL, created_at {$text} NOT NULL, updated_at {$text} NOT NULL, FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE, FOREIGN KEY(iptv_account_id) REFERENCES iptv_accounts(id) ON DELETE SET NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (id {$id}, setting_key {$text} NOT NULL UNIQUE, setting_value {$long} NULL, updated_at {$text} NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (id {$id}, administrator_id BIGINT NULL, action {$text} NOT NULL, entity_type {$text} NULL, entity_id BIGINT NULL, details_json {$long} NULL, created_at {$text} NOT NULL, FOREIGN KEY(administrator_id) REFERENCES administrators(id) ON DELETE SET NULL)");
};
