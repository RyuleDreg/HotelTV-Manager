<?php
declare(strict_types=1);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function hoteltv_require_admin()
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /');
        exit;
    }
}

function hoteltv_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function hoteltv_verify_csrf()
{
    $given = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if ($given === '' || !hash_equals(hoteltv_csrf_token(), $given)) {
        http_response_code(419);
        exit('Your session token expired. Go back and try again.');
    }
}

function hoteltv_flash($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function hoteltv_render_flash()
{
    if (empty($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert ' . h($flash['type']) . '">' . h($flash['message']) . '</div>';
}

function hoteltv_audit(PDO $pdo, $action, $entityType, $entityId, array $details = array())
{
    $statement = $pdo->prepare('INSERT INTO audit_logs(administrator_id,action,entity_type,entity_id,details_json,created_at) VALUES(?,?,?,?,?,?)');
    $statement->execute(array(
        isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null,
        $action,
        $entityType,
        $entityId ?: null,
        json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        gmdate('c')
    ));
}

function hoteltv_encrypt($plainText, array $config)
{
    $key = hash('sha256', isset($config['app_key']) ? $config['app_key'] : 'hoteltv', true);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt((string)$plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) throw new RuntimeException('Encryption failed.');
    return base64_encode($iv . $cipher);
}

function hoteltv_decrypt($encoded, array $config)
{
    $raw = base64_decode((string)$encoded, true);
    if ($raw === false || strlen($raw) < 17) return '';
    $key = hash('sha256', isset($config['app_key']) ? $config['app_key'] : 'hoteltv', true);
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}


function hoteltv_bearer_token()
{
    $header = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim((string)$_SERVER['HTTP_AUTHORIZATION']) : '';
    if (stripos($header, 'Bearer ') !== 0) return '';
    return trim(substr($header, 7));
}

function hoteltv_authenticated_device(PDO $pdo)
{
    $token = hoteltv_bearer_token();
    if ($token === '') hoteltv_json_response(array('error'=>'Missing device token'),401);
    $hash = hash('sha256', $token);
    $statement = $pdo->prepare('SELECT d.* FROM device_tokens t JOIN devices d ON d.id=t.device_id WHERE t.token_hash=? AND t.revoked_at IS NULL LIMIT 1');
    $statement->execute(array($hash));
    $device = $statement->fetch();
    if (!$device) hoteltv_json_response(array('error'=>'Invalid device token'),401);
    return $device;
}

function hoteltv_admin_header($title, $active)
{
    $items = array(
        'dashboard' => array('Overview', '/dashboard.php', '⌂'),
        'properties' => array('Properties', '/properties.php', '◆'),
        'rooms' => array('Rooms & Floors', '/rooms.php', '▦'),
        'devices' => array('Devices', '/devices.php', '▣'),
        'iptv' => array('IPTV', '/iptv.php', '▶'),
        'activity' => array('Activity', '/activity.php', '≡'),
        'app_settings' => array('TV App Settings', '/app-settings.php', '▤'),
        'settings' => array('System', '/settings.php', '⚙')
    );
    ?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#101827"><title><?=h($title)?> · HotelTV Manager</title><link rel="stylesheet" href="/assets/app.css?v=5"></head><body class="admin-body"><div class="admin-shell"><aside class="sidebar" id="sidebar"><div class="sidebar-brand"><span class="brand-mark">HT</span><div><strong>HotelTV</strong><small>Manager</small></div></div><nav><?php foreach($items as $key=>$item): ?><a class="nav-link <?=$key===$active?'active':''?>" href="<?=h($item[1])?>"><span class="nav-icon"><?=h($item[2])?></span><span><?=h($item[0])?></span></a><?php endforeach; ?></nav><div class="sidebar-footer"><span>Developer Preview 6</span><a class="signout" href="/logout.php">Sign out</a></div></aside><main class="main"><header class="mobile-header"><button class="menu-button" type="button" aria-label="Open menu" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button><div><strong>HotelTV Manager</strong><small><?=h($title)?></small></div></header><div class="page"><div class="page-heading"><div><p class="eyebrow">Hotel Entertainment Control Center</p><h1><?=h($title)?></h1></div></div><?php hoteltv_render_flash(); ?><?php
}

function hoteltv_admin_footer()
{
    ?></div></main></div><div class="sidebar-scrim" id="sidebarScrim"></div><script>
(function(){
 var sidebar=document.getElementById('sidebar'),scrim=document.getElementById('sidebarScrim');
 function closeMenu(){if(sidebar)sidebar.classList.remove('open');if(scrim)scrim.classList.remove('show');}
 document.addEventListener('click',function(e){
   if(e.target.classList.contains('menu-button')){if(scrim)scrim.classList.toggle('show');return;}
   if(e.target===scrim)closeMenu();
   var btn=e.target.closest('[data-confirm]');
   if(btn&&!window.confirm(btn.getAttribute('data-confirm')))e.preventDefault();
 });
})();
</script></body></html><?php
}
