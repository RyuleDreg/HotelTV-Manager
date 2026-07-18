<?php
declare(strict_types=1);
require dirname(dirname(dirname(__DIR__))) . '/hoteltv/bootstrap/app.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') hoteltv_json_response(array('error'=>'Method not allowed'),405);
$device = hoteltv_authenticated_device($pdo);
$data = hoteltv_request_json();
$now = gmdate('c');
$statement=$pdo->prepare('UPDATE devices SET status=?,last_seen_at=?,app_version=?,android_version=?,device_model=?,updated_at=? WHERE id=?');
$statement->execute(array('online',$now,isset($data['appVersion'])?$data['appVersion']:$device['app_version'],isset($data['androidVersion'])?$data['androidVersion']:$device['android_version'],isset($data['deviceModel'])?$data['deviceModel']:$device['device_model'],$now,$device['id']));
$commands=$pdo->prepare("SELECT id,command_type,payload_json FROM remote_commands WHERE device_id=? AND status='pending' ORDER BY id LIMIT 20");
$commands->execute(array($device['id']));
$list=array();
foreach($commands->fetchAll() as $command){$list[]=array('id'=>(int)$command['id'],'type'=>$command['command_type'],'payload'=>$command['payload_json']?json_decode($command['payload_json'],true):new stdClass());}
hoteltv_json_response(array('status'=>'ok','serverTime'=>$now,'configurationVersion'=>(int)$device['configuration_version'],'commands'=>$list),200);
