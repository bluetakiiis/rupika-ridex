<?php
/**
 * Purpose: Identify upcoming pickups/returns and emit reminder events.
 */

require_once __DIR__ . '/cron_bootstrap.php';

$jobName = 'cron_send_reminders';
$args = array_slice($_SERVER['argv'] ?? [], 1);

if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
	echo 'Usage: php bin/cron_send_reminders.php [--pickup-hours=<hours>] [--return-hours=<hours>] [--force]' . PHP_EOL;
	echo 'Defaults: REMINDER_PICKUP_HOURS=24 and REMINDER_RETURN_HOURS=6.' . PHP_EOL;
	exit(0);
}

$pickupHours = max(1, ridex_cron_env_int('REMINDER_PICKUP_HOURS', 24));
$returnHours = max(1, ridex_cron_env_int('REMINDER_RETURN_HOURS', 6));
$forceSend = false;

foreach ($args as $arg) {
	if ($arg === '--force') {
		$forceSend = true;
		continue;
	}

	if (str_starts_with($arg, '--pickup-hours=')) {
		$candidate = (int) trim(substr($arg, 15));
		if ($candidate > 0) {
			$pickupHours = $candidate;
		}
		continue;
	}

	if (str_starts_with($arg, '--return-hours=')) {
		$candidate = (int) trim(substr($arg, 15));
		if ($candidate > 0) {
			$returnHours = $candidate;
		}
	}
}

try {
	$stateDir = ridex_cron_ensure_cache_dir('cron');
	$statePath = $stateDir . '/reminder_state.json';
	$state = [
		'version' => 1,
		'sent_keys' => [],
	];

	if (is_file($statePath)) {
		$rawState = file_get_contents($statePath);
		if (is_string($rawState) && trim($rawState) !== '') {
			$decodedState = json_decode($rawState, true);
			if (is_array($decodedState)) {
				$state = array_merge($state, $decodedState);
			}
		}
	}

	if (!isset($state['sent_keys']) || !is_array($state['sent_keys'])) {
		$state['sent_keys'] = [];
	}

	$now = new DateTimeImmutable('now');
	$pickupWindowEnd = $now->add(new DateInterval('PT' . $pickupHours . 'H'));
	$returnWindowEnd = $now->add(new DateInterval('PT' . $returnHours . 'H'));

	$pdo = db();
	$reminderStmt = $pdo->prepare(
		'SELECT
			b.id,
			b.booking_number,
			b.status,
			b.pickup_datetime,
			b.return_datetime,
			u.email,
			u.name AS user_name,
			v.full_name AS vehicle_name
		 FROM bookings b
		 INNER JOIN users u ON u.id = b.user_id
		 INNER JOIN vehicles v ON v.id = b.vehicle_id
		 WHERE b.status IN ("reserved", "on_trip", "overdue")
			AND (
				(b.status = "reserved" AND b.pickup_datetime BETWEEN :now_pickup AND :pickup_end)
				OR
				(b.status IN ("on_trip", "overdue") AND b.return_datetime BETWEEN :now_return AND :return_end)
			)
		 ORDER BY b.pickup_datetime ASC, b.id ASC'
	);
	$reminderStmt->execute([
		'now_pickup' => $now->format('Y-m-d H:i:s'),
		'pickup_end' => $pickupWindowEnd->format('Y-m-d H:i:s'),
		'now_return' => $now->format('Y-m-d H:i:s'),
		'return_end' => $returnWindowEnd->format('Y-m-d H:i:s'),
	]);
	$candidateRows = $reminderStmt->fetchAll() ?: [];

	$sentCount = 0;
	$skippedDuplicate = 0;
	$skippedNoEmail = 0;

	foreach ($candidateRows as $candidateRow) {
		$bookingId = (int) ($candidateRow['id'] ?? 0);
		$bookingStatus = strtolower(trim((string) ($candidateRow['status'] ?? 'reserved')));
		$bookingNumber = trim((string) ($candidateRow['booking_number'] ?? ''));
		$userName = trim((string) ($candidateRow['user_name'] ?? 'Ridex User'));
		$userEmail = strtolower(trim((string) ($candidateRow['email'] ?? '')));
		$vehicleName = trim((string) ($candidateRow['vehicle_name'] ?? 'Vehicle'));

		$isPickupReminder = $bookingStatus === 'reserved';
		$targetDateTimeRaw = $isPickupReminder
			? trim((string) ($candidateRow['pickup_datetime'] ?? ''))
			: trim((string) ($candidateRow['return_datetime'] ?? ''));

		$reminderType = $isPickupReminder ? 'pickup' : 'return';
		$reminderKey = $bookingId . ':' . $reminderType . ':' . $targetDateTimeRaw;

		if (!$forceSend && isset($state['sent_keys'][$reminderKey])) {
			$skippedDuplicate += 1;
			continue;
		}

		if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
			$skippedNoEmail += 1;
			continue;
		}

		$eventContext = [
			'booking_id' => $bookingId,
			'booking_number' => $bookingNumber,
			'user_name' => $userName,
			'user_email' => $userEmail,
			'vehicle_name' => $vehicleName,
			'reminder_type' => $reminderType,
			'target_datetime' => $targetDateTimeRaw,
		];

		// Transport is intentionally log-based for now; wire SMTP/SMS here when providers are configured.
		ridex_cron_write_log($jobName, 'Reminder event queued.', $eventContext);

		$state['sent_keys'][$reminderKey] = $now->format(DATE_ATOM);
		$sentCount += 1;
	}

	$staleCutoff = $now->sub(new DateInterval('P7D'))->getTimestamp();
	foreach ($state['sent_keys'] as $sentKey => $sentAt) {
		$sentAtTimestamp = strtotime((string) $sentAt);
		if ($sentAtTimestamp === false || $sentAtTimestamp < $staleCutoff) {
			unset($state['sent_keys'][$sentKey]);
		}
	}

	$stateJson = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($stateJson) || $stateJson === '') {
		throw new RuntimeException('Unable to encode reminder state JSON.');
	}
	file_put_contents($statePath, $stateJson . PHP_EOL, LOCK_EX);

	$payload = [
		'ok' => true,
		'job' => $jobName,
		'pickup_window_hours' => $pickupHours,
		'return_window_hours' => $returnHours,
		'force' => $forceSend,
		'candidates' => count($candidateRows),
		'sent' => $sentCount,
		'skipped_duplicate' => $skippedDuplicate,
		'skipped_missing_email' => $skippedNoEmail,
		'state_file' => $statePath,
	];

	ridex_cron_write_log($jobName, 'Reminder run completed.', $payload);
	ridex_cron_print_json($payload);
	exit(0);
} catch (Throwable $exception) {
	$payload = [
		'ok' => false,
		'job' => $jobName,
		'error' => $exception->getMessage(),
	];

	ridex_cron_write_log($jobName, 'Reminder run failed.', $payload);
	ridex_cron_print_json($payload);
	exit(1);
}
