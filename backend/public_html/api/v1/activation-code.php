<?php
declare(strict_types=1);
require dirname(dirname(dirname(__DIR__))) . '/hoteltv/bootstrap/app.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') hoteltv_json_response(array('error'=>'Method not allowed'),405);
$data = hoteltv_request_json();
$uuid = trim(isset($data['deviceUuid']) ? $data['deviceUuid'] : '');
if ($uuid === '') hoteltv_json_response(array('error'=>'deviceUuid is required'),422);
$now = gmdate('c');
$statement = $pdo->prepare('SELECT id FROM devices WHERE uuid=? LIMIT 1');
$statement->execute(array($uuid));
$deviceId = $statement->fetchColumn();
if (!$deviceId) {
    $statement = $pdo->prepare('INSERT INTO devices(uuid,device_model,android_version,app_version,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?)');
    $statement->execute(array($uuid,isset($data['deviceModel'])?$data['deviceModel']:null,isset($data['androidVersion'])?$data['androidVersion']:null,isset($data['appVersion'])?$data['appVersion']:null,'pending',$now,$now));
    $deviceId = $pdo->lastInsertId();
}
$code = hoteltv_random_code();
$expires = gmdate('c', time()+900);
$statement = $pdo->prepare('INSERT INTO activation_codes(device_id,code,expires_at,created_at) VALUES(?,?,?,?)');
$statement->execute(array($deviceId,$code,$expires,$now));
hoteltv_json_response(array('activationCode'=>$code,'expiresAt'=>$expires),201);
