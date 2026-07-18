<?php
declare(strict_types=1);
require dirname(dirname(dirname(__DIR__))) . '/hoteltv/bootstrap/app.php';
$code = strtoupper(trim(isset($_GET['code']) ? (string)$_GET['code'] : ''));
if ($code === '') hoteltv_json_response(array('error'=>'code is required'),422);
$statement = $pdo->prepare('SELECT ac.*,d.status,d.display_name,d.room_id,d.property_id FROM activation_codes ac JOIN devices d ON d.id=ac.device_id WHERE ac.code=? ORDER BY ac.id DESC LIMIT 1');
$statement->execute(array($code));
$row = $statement->fetch();
if (!$row) hoteltv_json_response(array('status'=>'not_found'),404);
if ($row['claimed_at']) {
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256',$token);
    $pdo->prepare('DELETE FROM device_tokens WHERE device_id=?')->execute(array($row['device_id']));
    $pdo->prepare('INSERT INTO device_tokens(device_id,token_hash,created_at,revoked_at) VALUES(?,?,?,NULL)')->execute(array($row['device_id'],$hash,gmdate('c')));
    $pdo->prepare('UPDATE activation_codes SET code=? WHERE id=?')->execute(array('USED-'.bin2hex(random_bytes(8)),$row['id']));
    hoteltv_json_response(array('status'=>'activated','deviceToken'=>$token,'device'=>array('id'=>(int)$row['device_id'],'name'=>$row['display_name'])),200);
}
if (strtotime($row['expires_at']) < time()) hoteltv_json_response(array('status'=>'expired'),410);
hoteltv_json_response(array('status'=>'pending','expiresAt'=>$row['expires_at']),200);
