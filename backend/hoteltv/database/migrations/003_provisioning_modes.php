<?php
declare(strict_types=1);
return function (PDO $pdo, $driver) {
    $now = gmdate('c');
    $defaults = array(
        'apk_provisioning_mode' => 'hybrid',
        'apk_backend_url' => 'https://hoteltv-manager.mywire.org/api/v1',
        'apk_allow_multiple_playlists' => '1',
        'apk_require_admin_pin' => '0'
    );
    foreach ($defaults as $key => $value) {
        $s=$pdo->prepare('SELECT COUNT(*) FROM system_settings WHERE setting_key=?');
        $s->execute(array($key));
        if ((int)$s->fetchColumn()===0) {
            $i=$pdo->prepare('INSERT INTO system_settings(setting_key,setting_value,updated_at) VALUES(?,?,?)');
            $i->execute(array($key,$value,$now));
        }
    }
};
