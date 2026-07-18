<?php
declare(strict_types=1);
require dirname(dirname(dirname(__DIR__))) . '/hoteltv/bootstrap/app.php';
$device = hoteltv_authenticated_device($pdo);
$statement=$pdo->prepare('SELECT d.*,r.room_number,r.display_name room_name,p.name property_name,p.timezone,p.language FROM devices d LEFT JOIN rooms r ON r.id=d.room_id LEFT JOIN properties p ON p.id=d.property_id WHERE d.id=?');
$statement->execute(array($device['id']));
$row=$statement->fetch();
hoteltv_json_response(array('configurationVersion'=>(int)$row['configuration_version'],'device'=>array('id'=>(int)$row['id'],'name'=>$row['display_name'],'status'=>$row['status']),'property'=>array('name'=>$row['property_name'],'timezone'=>$row['timezone'],'language'=>$row['language']),'room'=>array('number'=>$row['room_number'],'name'=>$row['room_name']),'application'=>array('guestMode'=>true,'heartbeatIntervalSeconds'=>300)),200);
