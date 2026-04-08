<?php
/**
 * Purpose: Synchronize booking lifecycle state and related runtime data.
 */

require_once __DIR__ . '/cron_bootstrap.php';

$jobName = 'cron_expire_bookings';
$args = array_slice($_SERVER['argv'] ?? [], 1);

if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
	echo 'Usage: php bin/cron_expire_bookings.php' . PHP_EOL;
	echo 'Runs booking lifecycle sync and aligns vehicle statuses with active bookings.' . PHP_EOL;
	exit(0);
}

try {
	$pdo = db();

	ridex_sync_booking_lifecycle_statuses($pdo);
	ridex_sync_booking_users_and_payments($pdo, 'ridex_cron_ensure_payments_table');
	ridex_sync_sequential_auto_increment($pdo);

	$vehicleStatusCaseSql = 'CASE
		WHEN v.status = "maintenance" THEN "maintenance"
		WHEN active_booking.max_priority = 3 THEN "overdue"
		WHEN active_booking.max_priority = 2 THEN "on_trip"
		WHEN active_booking.max_priority = 1 THEN "reserved"
		ELSE "available"
	END';

	$vehicleSyncSql = 'UPDATE vehicles v
		LEFT JOIN (
			SELECT
				b.vehicle_id,
				MAX(
					CASE
						WHEN b.status = "overdue" THEN 3
						WHEN b.status = "on_trip" THEN 2
						WHEN b.status = "reserved" THEN 1
						ELSE 0
					END
				) AS max_priority
			FROM bookings b
			WHERE b.status IN ("reserved", "on_trip", "overdue")
			GROUP BY b.vehicle_id
		) active_booking ON active_booking.vehicle_id = v.id
		SET
			v.status = ' . $vehicleStatusCaseSql . ',
			v.updated_at = CURRENT_TIMESTAMP
		WHERE v.deleted_at IS NULL
			AND v.status <> ' . $vehicleStatusCaseSql;

	$vehicleRowsUpdated = (int) $pdo->exec($vehicleSyncSql);

	$bookingStatusRows = $pdo->query(
		'SELECT status, COUNT(*) AS total
		 FROM bookings
		 GROUP BY status
		 ORDER BY status ASC'
	)->fetchAll() ?: [];

	$bookingStatusCounts = [];
	foreach ($bookingStatusRows as $bookingStatusRow) {
		$statusKey = strtolower(trim((string) ($bookingStatusRow['status'] ?? 'unknown')));
		$bookingStatusCounts[$statusKey] = (int) ($bookingStatusRow['total'] ?? 0);
	}

	$payload = [
		'ok' => true,
		'job' => $jobName,
		'vehicles_updated' => $vehicleRowsUpdated,
		'booking_status_counts' => $bookingStatusCounts,
		'generated_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
	];

	ridex_cron_write_log($jobName, 'Booking lifecycle sync completed.', $payload);
	ridex_cron_print_json($payload);
	exit(0);
} catch (Throwable $exception) {
	$payload = [
		'ok' => false,
		'job' => $jobName,
		'error' => $exception->getMessage(),
	];

	ridex_cron_write_log($jobName, 'Booking lifecycle sync failed.', $payload);
	ridex_cron_print_json($payload);
	exit(1);
}
