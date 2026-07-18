<?php
declare(strict_types=1);

$hotelTvRoot = dirname(__DIR__);
$configPath = $hotelTvRoot . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(503);
    exit('HotelTV Manager is not installed. Open /install.php.');
}
$config = require $configPath;
require_once $hotelTvRoot . '/app/Database.php';
require_once $hotelTvRoot . '/app/helpers.php';
require_once $hotelTvRoot . '/database/migrate.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$db = new Database($config['database']);
$pdo = $db->pdo();
hoteltv_run_migrations($pdo, $config['database']['driver'], $hotelTvRoot . '/database/migrations');

function hoteltv_json_response(array $data, $status)
{
    http_response_code((int)$status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function hoteltv_request_json()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return array();
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        hoteltv_json_response(array('error' => 'Invalid JSON body'), 400);
    }
    return $data;
}

function hoteltv_random_code()
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $raw = '';
    for ($i = 0; $i < 8; $i++) {
        $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
}
