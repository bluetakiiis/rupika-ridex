<?php
/**
 * Purpose: Aggregate daily booking/fleet/payment statistics into a cache file.
 */

require_once __DIR__ . '/cron_bootstrap.php';

$jobName = 'cron_generate_stats';
$args = array_slice($_SERVER['argv'] ?? [], 1);

if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
	echo 'Usage: php bin/cron_generate_stats.php [--days=<lookback-days>] [--output=<path>]' . PHP_EOL;
	echo 'Defaults: CRON_STATS_DAYS env or 30 days, output var/cache/stats/daily_stats.json.' . PHP_EOL;
	exit(0);
}

$lookbackDays = ridex_cron_env_int('CRON_STATS_DAYS', 30);
$outputPath = APP_ROOT . '/var/cache/stats/daily_stats.json';

foreach ($args as $arg) {
	if (str_starts_with($arg, '--days=')) {
		$candidate = (int) trim(substr($arg, 7));
		if ($candidate > 0) {
			$lookbackDays = $candidate;
		}
		continue;
	}

	if (str_starts_with($arg, '--output=')) {
		$candidatePath = trim(substr($arg, 9));
		if ($candidatePath !== '') {
			if (preg_match('/^[A-Za-z]:[\\\\\/]/', $candidatePath) === 1 || str_starts_with($candidatePath, '/') || str_starts_with($candidatePath, '\\')) {
				$outputPath = $candidatePath;
			} else {
				$outputPath = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $candidatePath), '/');
			}
		}
	}
}

$lookbackDays = max(1, $lookbackDays);

try {
	$pdo = db();
	$now = new DateTimeImmutable('now');
	$cutoff = (new DateTimeImmutable('today'))->sub(new DateInterval('P' . $lookbackDays . 'D'))->format('Y-m-d H:i:s');

	$dailyStmt = $pdo->prepare(
		'SELECT
			DATE(b.created_at) AS stat_date,
			COUNT(*) AS total_bookings,
			SUM(CASE WHEN b.status = "completed" THEN 1 ELSE 0 END) AS completed_bookings,
			SUM(CASE WHEN b.status = "cancelled" THEN 1 ELSE 0 END) AS cancelled_bookings,
			SUM(CASE WHEN b.payment_status = "paid" THEN b.total_amount ELSE 0 END) AS recognized_revenue
		 FROM bookings b
		 WHERE b.created_at >= :cutoff
		 GROUP BY DATE(b.created_at)
		 ORDER BY stat_date ASC'
	);
	$dailyStmt->execute(['cutoff' => $cutoff]);
	$dailyRows = $dailyStmt->fetchAll() ?: [];

	$fleetRows = $pdo->query(
		'SELECT v.status, COUNT(*) AS total
		 FROM vehicles v
		 WHERE v.deleted_at IS NULL
		 GROUP BY v.status
		 ORDER BY v.status ASC'
	)->fetchAll() ?: [];

	$paymentRows = $pdo->query(
		'SELECT p.status, COUNT(*) AS total
		 FROM payments p
		 GROUP BY p.status
		 ORDER BY p.status ASC'
	)->fetchAll() ?: [];

	$fleetCounts = [];
	foreach ($fleetRows as $fleetRow) {
		$fleetKey = strtolower(trim((string) ($fleetRow['status'] ?? 'unknown')));
		$fleetCounts[$fleetKey] = (int) ($fleetRow['total'] ?? 0);
	}

	$paymentCounts = [];
	foreach ($paymentRows as $paymentRow) {
		$paymentKey = strtolower(trim((string) ($paymentRow['status'] ?? 'unknown')));
		$paymentCounts[$paymentKey] = (int) ($paymentRow['total'] ?? 0);
	}

	$payload = [
		'generated_at' => $now->format(DATE_ATOM),
		'window_days' => $lookbackDays,
		'window_start' => $cutoff,
		'daily_booking_stats' => $dailyRows,
		'fleet_status_counts' => $fleetCounts,
		'payment_status_counts' => $paymentCounts,
	];

	$outputDir = dirname($outputPath);
	if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
		throw new RuntimeException('Unable to create stats output directory: ' . $outputDir);
	}

	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json) || $json === '') {
		throw new RuntimeException('Unable to encode stats payload as JSON.');
	}

	file_put_contents($outputPath, $json . PHP_EOL, LOCK_EX);

	$response = [
		'ok' => true,
		'job' => $jobName,
		'output_file' => $outputPath,
		'daily_rows' => count($dailyRows),
		'fleet_status_keys' => array_keys($fleetCounts),
		'payment_status_keys' => array_keys($paymentCounts),
	];

	ridex_cron_write_log($jobName, 'Stats generation completed.', $response);
	ridex_cron_print_json($response);
	exit(0);
} catch (Throwable $exception) {
	$payload = [
		'ok' => false,
		'job' => $jobName,
		'error' => $exception->getMessage(),
	];

	ridex_cron_write_log($jobName, 'Stats generation failed.', $payload);
	ridex_cron_print_json($payload);
	exit(1);
}
