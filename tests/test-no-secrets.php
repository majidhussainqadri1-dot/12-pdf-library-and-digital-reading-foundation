<?php
$root = dirname(__DIR__);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$patterns = array('/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/', '/sk-[A-Za-z0-9_-]{20,}/', '/AIza[0-9A-Za-z\-_]{30,}/');
foreach ($it as $f) {
    if (!$f->isFile() || strpos($f->getPathname(), '/dist/') !== false) continue;
    $data = @file_get_contents($f->getPathname()); if (!is_string($data)) continue;
    foreach ($patterns as $pattern) if (preg_match($pattern, $data)) { fwrite(STDERR, "Potential secret in {$f->getPathname()}\n"); exit(1); }
}
echo "Secret scan: PASS\n";
