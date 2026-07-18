<?php
declare(strict_types=1);

$root = dirname(__DIR__) . '/hoteltv';
$lockFile = $root . '/storage/installed.lock';
if (is_file($lockFile)) {
    http_response_code(403);
    exit('Installer is locked. Delete hoteltv/storage/installed.lock only when intentionally reinstalling.');
}

$step = isset($_GET['step']) ? max(1, min(5, (int)$_GET['step'])) : 1;
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$errors = array();

$directories = array(
    $root . '/storage',
    $root . '/storage/database',
    $root . '/storage/logs',
    $root . '/storage/backups',
    $root . '/storage/cache',
    $root . '/config'
);
$storageOk = true;
foreach ($directories as $directory) {
    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
        $storageOk = false;
        $errors[] = 'Could not create ' . $directory;
        continue;
    }
    $test = $directory . '/.write-' . bin2hex(random_bytes(4));
    if (@file_put_contents($test, 'ok') === false) {
        $storageOk = false;
        $errors[] = 'PHP cannot write to ' . $directory;
    } else {
        @unlink($test);
    }
}
$checks = array(
    'PHP 7.2 or newer' => version_compare(PHP_VERSION, '7.2.0', '>='),
    'PDO extension' => extension_loaded('pdo'),
    'OpenSSL extension' => extension_loaded('openssl'),
    'SQLite driver available' => extension_loaded('pdo_sqlite'),
    'MySQL/MariaDB driver available' => extension_loaded('pdo_mysql'),
    'Private storage writable' => $storageOk
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $driver = isset($_POST['driver']) ? $_POST['driver'] : 'local_file';
        if (!in_array($driver, array('local_file', 'sqlite', 'mysql'), true)) {
            $errors[] = 'Invalid database type.';
        } elseif (($driver === 'local_file' || $driver === 'sqlite') && !extension_loaded('pdo_sqlite')) {
            $errors[] = 'pdo_sqlite is not enabled.';
        } elseif ($driver === 'mysql' && !extension_loaded('pdo_mysql')) {
            $errors[] = 'pdo_mysql is not enabled.';
        } else {
            $_SESSION['install_database'] = array(
                'driver' => $driver,
                'host' => trim(isset($_POST['host']) ? $_POST['host'] : '127.0.0.1'),
                'port' => (int)(isset($_POST['port']) ? $_POST['port'] : 3306),
                'database' => trim(isset($_POST['database']) ? $_POST['database'] : ''),
                'username' => trim(isset($_POST['username']) ? $_POST['username'] : ''),
                'password' => isset($_POST['db_password']) ? $_POST['db_password'] : ''
            );
            header('Location: /install.php?step=3'); exit;
        }
    } elseif ($step === 3) {
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid administrator email.';
        if (strlen($password) < 10) $errors[] = 'Password must be at least 10 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (!$errors) {
            $_SESSION['install_admin'] = array('email' => $email, 'password' => $password);
            header('Location: /install.php?step=4'); exit;
        }
    } elseif ($step === 4) {
        $hotel = trim(isset($_POST['hotel_name']) ? $_POST['hotel_name'] : '');
        if ($hotel === '') $errors[] = 'Hotel name is required.';
        if (!$errors) {
            $_SESSION['install_hotel'] = array(
                'name' => $hotel,
                'timezone' => trim(isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC'),
                'country' => trim(isset($_POST['country']) ? $_POST['country'] : ''),
                'language' => trim(isset($_POST['language']) ? $_POST['language'] : 'en')
            );
            header('Location: /install.php?step=5'); exit;
        }
    } elseif ($step === 5) {
        if (empty($_SESSION['install_database']) || empty($_SESSION['install_admin']) || empty($_SESSION['install_hotel'])) {
            $errors[] = 'Installer session is incomplete. Start again.';
        } else {
            try {
                $db = $_SESSION['install_database'];
                $appKey = bin2hex(random_bytes(32));
                $deviceSecret = bin2hex(random_bytes(32));
                if ($db['driver'] === 'local_file') {
                    $databaseConfig = array(
                        'driver' => 'sqlite',
                        'database_mode' => 'local_file',
                        'sqlite_path' => $root . '/storage/database/.hoteltv-manager.db',
                        'mysql' => array()
                    );
                } elseif ($db['driver'] === 'sqlite') {
                    $databaseConfig = array(
                        'driver' => 'sqlite',
                        'database_mode' => 'sqlite',
                        'sqlite_path' => $root . '/storage/database/hoteltv.sqlite',
                        'mysql' => array()
                    );
                } else {
                    $databaseConfig = array(
                        'driver' => 'mysql',
                        'database_mode' => 'mysql',
                        'sqlite_path' => $root . '/storage/database/.hoteltv-manager.db',
                        'mysql' => array(
                            'host' => $db['host'],
                            'port' => $db['port'],
                            'database' => $db['database'],
                            'username' => $db['username'],
                            'password' => $db['password'],
                            'charset' => 'utf8mb4'
                        )
                    );
                }
                $config = array('app_name'=>'HotelTV Manager','version'=>'Developer Preview 6','app_url'=>'https://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),'app_key'=>$appKey,'device_secret'=>$deviceSecret,'database'=>$databaseConfig);
                $php = "<?php\nreturn " . var_export($config, true) . ";\n";
                if (@file_put_contents($root . '/config/config.php', $php, LOCK_EX) === false) throw new RuntimeException('Could not write config/config.php');
                require_once $root . '/app/Database.php';
                require_once $root . '/database/migrate.php';
                $database = new Database($databaseConfig);
                $pdo = $database->pdo();
                hoteltv_run_migrations($pdo, $db['driver'], $root . '/database/migrations');
                $now = gmdate('c');
                $admin = $_SESSION['install_admin'];
                $statement = $pdo->prepare('INSERT INTO administrators(email,password_hash,role,created_at,updated_at) VALUES(?,?,?,?,?)');
                $statement->execute(array($admin['email'], password_hash($admin['password'], PASSWORD_DEFAULT), 'system_admin', $now, $now));
                $hotel = $_SESSION['install_hotel'];
                $uuid = bin2hex(random_bytes(16));
                $statement = $pdo->prepare('INSERT INTO properties(uuid,name,timezone,country,language,created_at,updated_at) VALUES(?,?,?,?,?,?,?)');
                $statement->execute(array($uuid,$hotel['name'],$hotel['timezone'],$hotel['country'],$hotel['language'],$now,$now));
                if (@file_put_contents($lockFile, 'Installed ' . $now . PHP_EOL, LOCK_EX) === false) throw new RuntimeException('Could not create installer lock.');
                session_destroy();
                header('Location: /?installed=1'); exit;
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
    }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HotelTV Installer</title><link rel="stylesheet" href="/assets/app.css"></head><body><div class="shell"><div class="panel"><div class="brand">HotelTV Manager</div><p class="muted">Developer Preview 6</p><div class="steps"><?php for($i=1;$i<=5;$i++): ?><span class="step <?=$i===$step?'active':''?>"><?=$i?></span><?php endfor; ?></div><?php foreach($errors as $error): ?><div class="alert error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endforeach; ?>
<?php if($step===1): ?><h1>Server check</h1><?php foreach($checks as $name=>$ok): ?><div class="check <?=$ok?'pass':'fail'?>"><?=$ok?'✓':'✗'?> <?=htmlspecialchars($name,ENT_QUOTES,'UTF-8')?></div><?php endforeach; ?><p class="muted">Private application path: <code><?=htmlspecialchars($root,ENT_QUOTES,'UTF-8')?></code></p><?php $essential=$checks['PHP 7.2 or newer']&&$checks['PDO extension']&&$checks['OpenSSL extension']&&$checks['Private storage writable']&&($checks['SQLite driver available']||$checks['MySQL/MariaDB driver available']); ?><a class="button" href="/install.php?step=2" <?=$essential?'':'style="pointer-events:none;opacity:.45"'?>>Continue</a>
<?php elseif($step===2): ?><h1>Choose database</h1><form method="post"><div class="field"><label>Database type</label><select name="driver" id="database-driver"><option value="local_file">Local file (.hoteltv-manager.db) — recommended</option><option value="sqlite">SQLite (hoteltv.sqlite)</option><option value="mysql">MySQL / MariaDB</option></select></div><div class="alert" id="local-file-note">The database will be stored privately at <code><?=htmlspecialchars($root . '/storage/database/.hoteltv-manager.db',ENT_QUOTES,'UTF-8')?></code>. It is not inside <code>public_html</code>.</div><div class="grid" id="mysql-fields" style="display:none"><div class="field"><label>MySQL host</label><input name="host" value="127.0.0.1"></div><div class="field"><label>Port</label><input name="port" value="3306"></div><div class="field"><label>Database name</label><input name="database"></div><div class="field"><label>Username</label><input name="username"></div><div class="field"><label>Password</label><input type="password" name="db_password"></div></div><button class="button">Continue</button></form><script>(function(){var d=document.getElementById('database-driver'),m=document.getElementById('mysql-fields'),n=document.getElementById('local-file-note');function update(){m.style.display=d.value==='mysql'?'grid':'none';n.style.display=d.value==='local_file'?'block':'none';}d.addEventListener('change',update);update();}());</script>
<?php elseif($step===3): ?><h1>Create administrator</h1><form method="post"><div class="field"><label>Email</label><input type="email" name="email" required></div><div class="field"><label>Password</label><input type="password" name="password" required></div><div class="field"><label>Confirm password</label><input type="password" name="confirm_password" required></div><button class="button">Continue</button></form>
<?php elseif($step===4): ?><h1>Hotel setup</h1><form method="post"><div class="field"><label>Hotel name</label><input name="hotel_name" required></div><div class="grid"><div class="field"><label>Timezone</label><input name="timezone" value="America/Vancouver"></div><div class="field"><label>Country</label><input name="country" value="Canada"></div><div class="field"><label>Language</label><input name="language" value="en"></div></div><button class="button">Continue</button></form>
<?php else: ?><h1>Ready to install</h1><p>The installer will create the database, first property, administrator, encryption keys, device secret, and installer lock.</p><form method="post"><button class="button">Install HotelTV Manager</button></form><?php endif; ?></div></div></body></html>
