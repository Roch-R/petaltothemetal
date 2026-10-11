<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__); $failed = false; $count = 0;
foreach (['app','config','public','templates','scripts','tests'] as $directory) {
    if (!is_dir($root . '/' . $directory)) { continue; }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') { continue; }
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname());
        exec($command, $output, $status); $count++;
        if ($status !== 0) { $failed=true; echo implode(PHP_EOL,$output) . PHP_EOL; }
        $output=[];
    }
}
echo ($failed ? 'FAIL' : 'PASS') . ' PHP syntax: ' . $count . ' files checked.' . PHP_EOL;
exit($failed ? 1 : 0);
