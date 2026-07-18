<?php
declare(strict_types=1);
require dirname(__DIR__) . '/hoteltv/bootstrap/app.php';
hoteltv_require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hoteltv_verify_csrf();
    $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
    $id = (int)$_POST['device_id'];
    $now = gmdate('c');
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM devices WHERE id=?')->execute(array($id));
        hoteltv_audit($pdo, 'delete', 'device', $id);
        hoteltv_flash('success', 'Device, activation codes and tokens were permanently removed.');
    } elseif ($action === 'deactivate') {
        $pdo->prepare("UPDATE devices SET property_id=NULL,room_id=NULL,status='pending',activated_at=NULL,updated_at=? WHERE id=?")->execute(array($now,$id));
        $pdo->prepare('UPDATE device_tokens SET revoked_at=? WHERE device_id=? AND revoked_at IS NULL')->execute(array($now,$id));
        hoteltv_audit($pdo, 'deactivate', 'device', $id);
        hoteltv_flash('success', 'Device deactivated and unassigned.');
    } else {
        $property=(int)$_POST['property_id'];$room=(int)$_POST['room_id'];$name=trim($_POST['display_name']);
        $st=$pdo->prepare("UPDATE devices SET property_id=?,room_id=?,display_name=?,status='offline',activated_at=COALESCE(activated_at,?),updated_at=? WHERE id=?");
        $st->execute(array($property?:null,$room?:null,$name,$now,$now,$id));
        $pdo->prepare('UPDATE activation_codes SET claimed_at=? WHERE device_id=? AND claimed_at IS NULL')->execute(array($now,$id));
        hoteltv_audit($pdo, 'assign', 'device', $id, array('property_id'=>$property,'room_id'=>$room));
        hoteltv_flash('success','Television saved and activated.');
    }
    header('Location:/devices.php');exit;
}
$status=isset($_GET['status'])?(string)$_GET['status']:'';
$allowed=array('pending','online','offline');
$where=in_array($status,$allowed,true)?' WHERE d.status='.$pdo->quote($status):'';
$devices=$pdo->query("SELECT d.*,p.name property_name,r.room_number,ac.code activation_code,ac.expires_at FROM devices d LEFT JOIN properties p ON p.id=d.property_id LEFT JOIN rooms r ON r.id=d.room_id LEFT JOIN activation_codes ac ON ac.device_id=d.id AND ac.claimed_at IS NULL {$where} ORDER BY CASE d.status WHEN 'pending' THEN 0 WHEN 'online' THEN 1 ELSE 2 END,d.id DESC")->fetchAll();
$properties=$pdo->query('SELECT * FROM properties ORDER BY name')->fetchAll();
$rooms=$pdo->query('SELECT r.*,p.name property_name FROM rooms r JOIN properties p ON p.id=r.property_id ORDER BY p.name,r.room_number')->fetchAll();
hoteltv_admin_header('Devices','devices');
?>
<div class="toolbar"><div class="filter-links"><a class="<?=!$status?'active':''?>" href="/devices.php">All</a><a class="<?=$status==='pending'?'active':''?>" href="?status=pending">Pending</a><a class="<?=$status==='online'?'active':''?>" href="?status=online">Online</a><a class="<?=$status==='offline'?'active':''?>" href="?status=offline">Offline</a></div><span class="badge"><?=count($devices)?> shown</span></div>
<section class="card"><div class="section-head"><div><h2>Televisions</h2><p>Activation, room assignment, health and device removal.</p></div></div>
<?php if(!$devices):?><div class="empty"><strong>No matching devices</strong><p>Install the HotelTV app on a television to generate an activation code.</p></div><?php else:?><div class="device-list">
<?php foreach($devices as $d):?><article class="device-row"><div class="device-icon">TV</div><div class="device-main"><div><strong><?=h($d['display_name']?:$d['device_model']?:'New television')?></strong> <span class="status status-<?=h($d['status'])?>"><?=h(ucfirst($d['status']))?></span></div><p><?=h($d['property_name']?:'Unassigned')?><?=!empty($d['room_number'])?' · Room '.h($d['room_number']):''?> · <?=h($d['android_version']?:'Android unknown')?> · App <?=h($d['app_version']?:'unknown')?></p><small>Last seen: <?=h($d['last_seen_at']?:'Never')?><?php if($d['activation_code']):?> · Code: <code><?=h($d['activation_code'])?></code><?php endif;?></small></div><details class="device-actions"><summary class="button secondary">Manage</summary><form method="post"><input type="hidden" name="csrf_token" value="<?=h(hoteltv_csrf_token())?>"><input type="hidden" name="device_id" value="<?=$d['id']?>"><div class="field"><label>Name</label><input name="display_name" value="<?=h($d['display_name']?:$d['device_model']?:'Room TV')?>"></div><div class="field"><label>Property</label><select name="property_id"><option value="0">Unassigned</option><?php foreach($properties as $p):?><option value="<?=$p['id']?>" <?=$d['property_id']==$p['id']?'selected':''?>><?=h($p['name'])?></option><?php endforeach;?></select></div><div class="field"><label>Room</label><select name="room_id"><option value="0">Unassigned</option><?php foreach($rooms as $r):?><option value="<?=$r['id']?>" <?=$d['room_id']==$r['id']?'selected':''?>><?=h($r['property_name'].' — '.$r['room_number'])?></option><?php endforeach;?></select></div><div class="button-row"><button class="button primary" name="action" value="save">Save & activate</button><button class="button secondary" name="action" value="deactivate" data-confirm="Deactivate this TV and revoke its token?">Deactivate</button><button class="button danger" name="action" value="delete" data-confirm="Permanently remove this TV and all of its activation data?">Remove</button></div></form></details></article><?php endforeach;?></div><?php endif;?></section>
<?php hoteltv_admin_footer(); ?>
