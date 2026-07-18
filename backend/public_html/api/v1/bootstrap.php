<?php
declare(strict_types=1);
require dirname(dirname(dirname(__DIR__))) . '/hoteltv/bootstrap/app.php';
$rows=$pdo->query("SELECT setting_key,setting_value FROM system_settings WHERE setting_key LIKE 'apk_%'")->fetchAll();$s=array();foreach($rows as $r){$s[$r['setting_key']]=$r['setting_value'];}
hoteltv_json_response(array('apiVersion'=>1,'provisioningMode'=>isset($s['apk_provisioning_mode'])?$s['apk_provisioning_mode']:'hybrid','allowMultiplePlaylists'=>isset($s['apk_allow_multiple_playlists'])&&$s['apk_allow_multiple_playlists']==='1','requireAdminPin'=>isset($s['apk_require_admin_pin'])&&$s['apk_require_admin_pin']==='1'),200);
