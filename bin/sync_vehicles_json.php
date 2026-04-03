<?php
/**
 * Purpose: CLI script to sync vehicles table bidirectionally with category JSON files.
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers/vehicle_json_sync.php';

try {
	$args = array_slice($_SERVER['argv'] ?? [], 1);
	if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
		echo "Usage: php bin/sync_vehicles_json.php [--prefer-json|--force-json|--prefer-db-timestamps|--prefer-db]" . PHP_EOL;
		echo "Default mode uses latest-update-wins across JSON and DB." . PHP_EOL;
		echo "  --prefer-json          Force JSON as conflict winner." . PHP_EOL;
		echo "  --force-json   Alias for --prefer-json." . PHP_EOL;
		echo "  --prefer-db-timestamps Force DB as conflict winner (legacy behavior)." . PHP_EOL;
		echo "  --prefer-db            Alias for --prefer-db-timestamps." . PHP_EOL;
		exit(0);
	}

	$preferJsonFlag = in_array('--prefer-json', $args, true) || in_array('--force-json', $args, true);
	$preferDbTimestamps = in_array('--prefer-db-timestamps', $args, true) || in_array('--prefer-db', $args, true);
	if ($preferJsonFlag && $preferDbTimestamps) {
		throw new InvalidArgumentException('Use either --prefer-json or --prefer-db-timestamps, not both.');
	}

	$options = [];
	if ($preferJsonFlag) {
		$options['prefer_json'] = true;
	}
	if ($preferDbTimestamps) {
		$options['prefer_db_timestamps'] = true;
	}

	$result = sync_vehicles_json_bidirectional(
		db(),
		APP_ROOT . '/var/cache/vehicles-json',
		$options
	);
	echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $exception) {
	fwrite(STDERR, 'Vehicle JSON sync failed: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
