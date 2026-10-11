<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/init.php';
require_once __DIR__ . '/support/assertions.php';
require_once __DIR__ . '/support/database.php';
require_once __DIR__ . '/support/fixtures.php';
$options = getopt('', ['suite:','group:']);
$suite = $options['suite'] ?? 'all';
if (!in_array($suite, ['all','unit','integration'],true)) { fwrite(STDERR,"Unknown suite.\n"); exit(1); }
$group = $options['group'] ?? null;
$aliases = ['checkout'=>'checkout_validation','order-policy'=>'order_policy','orders'=>'order_service','admin-catalog'=>'admin_catalog'];
$group = $group !== null ? ($aliases[$group] ?? $group) : null;
$passed = 0; $failed = 0;
foreach ($suite === 'all' ? ['unit','integration'] : [$suite] as $directory) {
    foreach (glob(__DIR__ . '/' . $directory . '/*.php') ?: [] as $file) {
        if ($group !== null && basename($file,'.php') !== $group) { continue; }
        $tests = require $file;
        if (!is_array($tests)) { fwrite(STDERR,'Invalid test file: ' . basename($file) . PHP_EOL); exit(1); }
        foreach ($tests as $name=>$test) {
            try { $test(); echo 'PASS ' . $name . PHP_EOL; $passed++; }
            catch (Throwable $error) { echo 'FAIL ' . $name . ': ' . $error->getMessage() . PHP_EOL; $failed++; }
            finally { if ($directory === 'integration') { try { cleanup_fixtures(test_db()); } catch(Throwable $error) { echo 'FAIL fixture cleanup: ' . $error->getMessage() . PHP_EOL; $failed++; } } }
        }
    }
}
if ($passed + $failed === 0) { fwrite(STDERR,"No tests discovered.\n"); exit(1); }
echo $passed . ' passed, ' . $failed . ' failed.' . PHP_EOL;
exit($failed ? 1 : 0);
