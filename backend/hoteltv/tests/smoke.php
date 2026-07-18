<?php
declare(strict_types=1);
$root = dirname(__DIR__,2);
$required = array('public_html/index.php','public_html/install.php','public_html/dashboard.php','public_html/api/v1/activation-code.php','hoteltv/app/Database.php','hoteltv/database/migrations/001_initial.php');
foreach ($required as $file) if (!is_file($root . '/' . $file)) { fwrite(STDERR,'Missing ' . $file . PHP_EOL); exit(1); }
echo "Static smoke test passed\n";
