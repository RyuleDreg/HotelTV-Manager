<?php
declare(strict_types=1);
require dirname(__DIR__) . '/hoteltv/bootstrap/app.php';
hoteltv_require_admin();
$errors = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hoteltv_verify_csrf();
    $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($action === 'delete') {
        $counts = array();
        foreach (array('rooms','devices','iptv_accounts','playlist_profiles') as $table) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE property_id=?');
            $st->execute(array($id));
            $counts[$table] = (int)$st->fetchColumn();
        }
        if (array_sum($counts) > 0) {
            hoteltv_flash('error', 'This property is still in use. Remove or reassign its rooms, devices, IPTV accounts and profiles first.');
        } else {
            $pdo->prepare('DELETE FROM properties WHERE id=?')->execute(array($id));
            hoteltv_audit($pdo, 'delete', 'property', $id);
            hoteltv_flash('success', 'Property permanently removed.');
        }
        header('Location: /properties.php'); exit;
    }
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $timezone = trim(isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC');
    $country = trim(isset($_POST['country']) ? $_POST['country'] : '');
    $language = trim(isset($_POST['language']) ? $_POST['language'] : 'en');
    if ($name === '') $errors[] = 'Property name is required.';
    if ($timezone === '') $timezone = 'UTC';
    if ($language === '') $language = 'en';
    if (!$errors) {
        $now = gmdate('c');
        if ($id) {
            $st = $pdo->prepare('UPDATE properties SET name=?,timezone=?,country=?,language=?,updated_at=? WHERE id=?');
            $st->execute(array($name,$timezone,$country,$language,$now,$id));
            hoteltv_audit($pdo, 'update', 'property', $id, array('name'=>$name));
        } else {
            $st = $pdo->prepare('INSERT INTO properties(uuid,name,timezone,country,language,created_at,updated_at) VALUES(?,?,?,?,?,?,?)');
            $st->execute(array(bin2hex(random_bytes(16)),$name,$timezone,$country,$language,$now,$now));
            $id = (int)$pdo->lastInsertId();
            hoteltv_audit($pdo, 'create', 'property', $id, array('name'=>$name));
        }
        hoteltv_flash('success', isset($_POST['id']) && (int)$_POST['id'] ? 'Property updated.' : 'Property created.');
        header('Location: /properties.php'); exit;
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM properties WHERE id=?');
    $st->execute(array((int)$_GET['edit']));
    $edit = $st->fetch();
}
$properties = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM rooms r WHERE r.property_id=p.id) room_count, (SELECT COUNT(*) FROM devices d WHERE d.property_id=p.id) device_count, (SELECT COUNT(*) FROM iptv_accounts i WHERE i.property_id=p.id) iptv_count FROM properties p ORDER BY p.name')->fetchAll();
hoteltv_admin_header('Properties', 'properties');
foreach ($errors as $error) echo '<div class="alert error">'.h($error).'</div>';
?>
<div class="toolbar"><div><strong><?=count($properties)?> properties</strong><div class="help">Hotel locations managed by this panel.</div></div><a class="button primary" href="/properties.php#property-form">＋ Add property</a></div>
<div class="two-column"><section class="card"><div class="section-head"><div><h2>Hotel properties</h2><p>Edit a location or review what is assigned to it.</p></div></div>
<?php if (!$properties): ?><div class="empty"><strong>No properties found</strong><p>Add the first hotel location using the form.</p></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Property</th><th>Timezone</th><th>Rooms</th><th>Devices</th><th>IPTV</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($properties as $property): ?><tr><td><strong><?=h($property['name'])?></strong><small><?=h($property['country'])?></small></td><td><?=h($property['timezone'])?></td><td><?=$property['room_count']?></td><td><?=$property['device_count']?></td><td><?=$property['iptv_count']?></td><td><div class="actions-cell"><a class="button secondary small" href="?edit=<?=$property['id']?>#property-form">Edit</a><form class="inline-form" method="post"><input type="hidden" name="csrf_token" value="<?=h(hoteltv_csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$property['id']?>"><button class="button danger small" data-confirm="Remove <?=h($property['name'])?>? This only works when nothing is assigned to it.">Remove</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<aside class="card form-card" id="property-form"><h2><?=$edit ? 'Edit property' : 'Add property'?></h2><p class="muted"><?=$edit ? 'Update this hotel location.' : 'Create another hotel or managed site.'?></p><form method="post"><input type="hidden" name="csrf_token" value="<?=h(hoteltv_csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=h($edit ? $edit['id'] : 0)?>"><div class="field"><label>Property name</label><input name="name" required value="<?=h($edit ? $edit['name'] : '')?>"></div><div class="field"><label>Timezone</label><input name="timezone" value="<?=h($edit ? $edit['timezone'] : 'America/Vancouver')?>"></div><div class="field"><label>Country</label><input name="country" value="<?=h($edit ? $edit['country'] : 'Canada')?>"></div><div class="field"><label>Language</label><input name="language" value="<?=h($edit ? $edit['language'] : 'en')?>"></div><div class="button-row"><button class="button primary"><?=$edit ? 'Save changes' : 'Add property'?></button><?php if ($edit): ?><a class="button secondary" href="/properties.php">Cancel</a><?php endif; ?></div></form></aside></div>
<?php hoteltv_admin_footer(); ?>
