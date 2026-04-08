<?php
/**
 * Purpose: GPS cleanup job placeholder (disabled).
 */

require_once __DIR__ . '/cron_bootstrap.php';

$jobName = 'cron_cleanup_gps';
$args = array_slice($_SERVER['argv'] ?? [], 1);

if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
	echo 'Usage: php bin/cron_cleanup_gps.php' . PHP_EOL;
	echo 'GPS cleanup is currently disabled.' . PHP_EOL;
	exit(0);
}

try {
	$payload = [
		'ok' => true,
		'job' => $jobName,
		'disabled' => true,
		'message' => 'GPS cleanup has been disabled.',
	];

	ridex_cron_write_log($jobName, 'GPS cleanup skipped (disabled).', $payload);
	ridex_cron_print_json($payload);
	exit(0);
} catch (Throwable $exception) {
	$payload = [
		'ok' => false,
		'job' => $jobName,
		'error' => $exception->getMessage(),
	];

	ridex_cron_write_log($jobName, 'GPS cleanup failed.', $payload);
	ridex_cron_print_json($payload);
	exit(1);
}
