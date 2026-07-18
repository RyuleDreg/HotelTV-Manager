<?php
declare(strict_types=1);
require dirname(__DIR__) . '/hoteltv/bootstrap/app.php';
hoteltv_require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
 hoteltv_verify_csrf();
 $mode=isset($_POST['apk_provisioning_mode'])?(string)$_POST['apk_provisioning_mode']:'hybrid';
 if(!in_array($mode,array('hybrid','hotel_only','direct_only'),true))$mode='hybrid';
 $values=array(
  'apk_provisioning_mode'=>$mode,
  'apk_backend_url'=>rtrim(trim(isset($_POST['apk_backend_url'])?(string)$_POST['apk_backend_url']:''),'/'),
  'apk_allow_multiple_playlists'=>isset($_POST['apk_allow_multiple_playlists'])?'1':'0',
  'apk_require_admin_pin'=>isset($_POST['apk_require_admin_pin'])?'1':'0'
 );
 foreach($values as $k=>$v){$s=$pdo->prepare('UPDATE system_settings SET setting_value=?,updated_at=? WHERE setting_key=?');$s->execute(array($v,gmdate('c'),$k));}
 hoteltv_audit($pdo,'update','app_settings',null,$values); hoteltv_flash('success','TV app settings saved.'); header('Location: /app-settings.php');exit;
}
$settings=array();$q=$pdo->query("SELECT setting_key,setting_value FROM system_settings WHERE setting_key LIKE 'apk_%'");foreach($q->fetchAll() as $r){$settings[$r['setting_key']]=$r['setting_value'];}
hoteltv_admin_header('TV App Settings','app_settings');
?>
<section class="card"><div class="section-head"><div><h2>Provisioning and login</h2><p>Choose which setup methods are shown by the Android TV application.</p></div></div>
<form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=h(hoteltv_csrf_token())?>">
<label>Provisioning mode<select name="apk_provisioning_mode"><option value="hybrid" <?=isset($settings['apk_provisioning_mode'])&&$settings['apk_provisioning_mode']==='hybrid'?'selected':''?>>Hybrid — hotel activation or Xtream login</option><option value="hotel_only" <?=isset($settings['apk_provisioning_mode'])&&$settings['apk_provisioning_mode']==='hotel_only'?'selected':''?>>Hotel activation only</option><option value="direct_only" <?=isset($settings['apk_provisioning_mode'])&&$settings['apk_provisioning_mode']==='direct_only'?'selected':''?>>Xtream login only</option></select></label>
<label>Backend API URL<input name="apk_backend_url" value="<?=h(isset($settings['apk_backend_url'])?$settings['apk_backend_url']:'')?>" required></label>
<label><input type="checkbox" name="apk_allow_multiple_playlists" <?=isset($settings['apk_allow_multiple_playlists'])&&$settings['apk_allow_multiple_playlists']==='1'?'checked':''?>> Allow multiple direct-login playlists</label>
<label><input type="checkbox" name="apk_require_admin_pin" <?=isset($settings['apk_require_admin_pin'])&&$settings['apk_require_admin_pin']==='1'?'checked':''?>> Require administrator PIN for playlist management</label>
<div><button class="button primary" type="submit">Save TV App Settings</button></div></form></section>
<?php hoteltv_admin_footer(); ?>
