<?php
declare(strict_types=1);
require dirname(__DIR__) . '/hoteltv/bootstrap/app.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $statement = $pdo->prepare('SELECT * FROM administrators WHERE email = ? LIMIT 1');
    $statement->execute(array($email));
    $administrator = $statement->fetch();
    if ($administrator && password_verify($password, $administrator['password_hash'])) {
        $_SESSION['admin_id'] = (int)$administrator['id'];
        header('Location: /dashboard.php'); exit;
    }
    $error = 'Invalid email or password.';
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HotelTV Manager</title><link rel="stylesheet" href="/assets/app.css"></head><body><div class="shell"><div class="panel" style="max-width:480px;margin:auto"><h1>HotelTV Manager</h1><p class="muted">Administrator sign in</p><?php if(isset($_GET['installed'])):?><div class="alert success">Installation completed successfully.</div><?php endif;?><?php if($error):?><div class="alert error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif;?><form method="post"><div class="field"><label>Email</label><input type="email" name="email" required></div><div class="field"><label>Password</label><input type="password" name="password" required></div><button class="button">Sign in</button></form></div></div></body></html>
