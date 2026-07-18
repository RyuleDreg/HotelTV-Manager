<?php
declare(strict_types=1);
$root = realpath(__DIR__ . '/../..');
$patterns = array('/\bnever\s+\{/' => 'never return type','/:\s*mixed\b/' => 'mixed type','/\bmatch\s*\(/' => 'match expression','/\?->/' => 'nullsafe operator','/\bfn\s*\(/' => 'arrow function','/#\[/' => 'attributes','/\breadonly\s+/' => 'readonly','/\benum\s+[A-Za-z_]/' => 'enum','/\b(public|protected|private)\s+(static\s+)?[A-Za-z_\\\\][A-Za-z0-9_\\\\]*\s+\$[A-Za-z_]/' => 'typed property');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$failed = false;
foreach ($iterator as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php' || $file->getPathname() === __FILE__) continue;
    $source = file_get_contents($file->getPathname());
    foreach ($patterns as $pattern => $label) if (preg_match($pattern, $source)) { fwrite(STDERR, $label . ' in ' . $file->getPathname() . PHP_EOL); $failed=true; }
}
if ($failed) exit(1);
echo "PHP 7.2 compatibility audit passed\n";
