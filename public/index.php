<?php
/**
 * Purpose: Front controller for web requests; loads config, routes, and dispatches controllers.
 * Website Section: Global Entry Point (public web).
 * Developer Notes: Bootstrap environment, register error handling, route incoming requests to controllers/views.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers/vehicle_json_sync.php';

$runVehicleSync = static function (): void {
	try {
		sync_vehicles_json_bidirectional(db(), APP_ROOT . '/var/cache/vehicles-json');
	} catch (Throwable $exception) {
		// Keep page rendering available even if JSON sync fails.
		error_log('Vehicle JSON sync failed: ' . $exception->getMessage());
	}
};

// Pull latest changes before read queries.
$runVehicleSync();
// Push website-originated DB changes (create/update/delete) after request handling.
register_shutdown_function($runVehicleSync);

$allowedVehicleTypes = ['cars', 'bikes', 'luxury'];

$sanitizeVehicleType = static function ($rawType) use ($allowedVehicleTypes): string {
	$normalizedType = strtolower(trim((string) $rawType));

	return in_array($normalizedType, $allowedVehicleTypes, true)
		? $normalizedType
		: 'cars';
};

$page = strtolower(trim((string) ($_GET['page'] ?? 'home')));

if ($page === 'vehicles') {
	$selectedVehicleType = $sanitizeVehicleType($_GET['vehicle_type'] ?? 'cars');
	$pickupLocation = trim((string) ($_GET['pickup-location'] ?? ''));
	$returnLocation = trim((string) ($_GET['return-location'] ?? ''));
	$pickupDate = trim((string) ($_GET['pickup-date'] ?? ''));
	$returnDate = trim((string) ($_GET['return-date'] ?? ''));
	$pickupTime = trim((string) ($_GET['pickup-time'] ?? ''));
	$returnTime = trim((string) ($_GET['return-time'] ?? ''));

	$vehicles = [];

	try {
		$statement = db()->prepare(
			'SELECT v.*, c.name AS category_name
			FROM vehicles v
			INNER JOIN categories c ON c.id = v.category_id
			WHERE v.vehicle_type = :vehicle_type AND v.status = :status
			ORDER BY v.created_at DESC'
		);
		$statement->execute([
			'vehicle_type' => $selectedVehicleType,
			'status' => 'available',
		]);
		$vehicles = $statement->fetchAll() ?: [];
	} catch (Throwable $exception) {
		error_log('Vehicle listing query failed: ' . $exception->getMessage());
		$vehicles = [];
	}

	$title = 'Ridex | ' . ucfirst($selectedVehicleType) . ' Listings';
	$view = 'vehicle/list';
	$viewData = [
		'vehicles' => $vehicles,
		'selectedVehicleType' => $selectedVehicleType,
		'pickupLocation' => $pickupLocation,
		'returnLocation' => $returnLocation,
		'pickupDate' => $pickupDate,
		'returnDate' => $returnDate,
		'pickupTime' => $pickupTime,
		'returnTime' => $returnTime,
	];
} elseif ($page === 'vehicle-detail') {
	$vehicleId = (int) ($_GET['id'] ?? 0);
	$requestedVehicleType = $sanitizeVehicleType($_GET['vehicle_type'] ?? 'cars');
	$pickupLocation = trim((string) ($_GET['pickup-location'] ?? ''));
	$returnLocation = trim((string) ($_GET['return-location'] ?? ''));
	$pickupDate = trim((string) ($_GET['pickup-date'] ?? ''));
	$returnDate = trim((string) ($_GET['return-date'] ?? ''));
	$pickupTime = trim((string) ($_GET['pickup-time'] ?? ''));
	$returnTime = trim((string) ($_GET['return-time'] ?? ''));

	$vehicle = null;

	if ($vehicleId > 0) {
		try {
			$statement = db()->prepare(
				'SELECT v.*, c.name AS category_name
				FROM vehicles v
				INNER JOIN categories c ON c.id = v.category_id
				WHERE v.id = :id
				LIMIT 1'
			);
			$statement->execute([
				'id' => $vehicleId,
			]);
			$vehicle = $statement->fetch() ?: null;
		} catch (Throwable $exception) {
			error_log('Vehicle detail query failed: ' . $exception->getMessage());
			$vehicle = null;
		}
	}

	$backVehicleType = $requestedVehicleType;
	if (is_array($vehicle)) {
		$backVehicleType = $sanitizeVehicleType($vehicle['vehicle_type'] ?? $requestedVehicleType);
	}

	$backQuery = [
		'page' => 'vehicles',
		'vehicle_type' => $backVehicleType,
	];

	if ($pickupLocation !== '') {
		$backQuery['pickup-location'] = $pickupLocation;
	}

	if ($returnLocation !== '') {
		$backQuery['return-location'] = $returnLocation;
	}

	if ($pickupDate !== '') {
		$backQuery['pickup-date'] = $pickupDate;
	}

	if ($returnDate !== '') {
		$backQuery['return-date'] = $returnDate;
	}

	if ($pickupTime !== '') {
		$backQuery['pickup-time'] = $pickupTime;
	}

	if ($returnTime !== '') {
		$backQuery['return-time'] = $returnTime;
	}

	$backUrl = 'index.php?' . http_build_query($backQuery);

	$vehicleTitle = 'Vehicle Details';
	if (is_array($vehicle)) {
		$vehicleTitleRaw = trim((string) ($vehicle['full_name'] ?? ''));
		$vehicleTitle = $vehicleTitleRaw !== '' ? $vehicleTitleRaw : trim((string) ($vehicle['short_name'] ?? 'Vehicle Details'));
	}

	$title = 'Ridex | ' . $vehicleTitle;
	$view = 'vehicle/detail';
	$viewData = [
		'vehicle' => $vehicle,
		'backUrl' => $backUrl,
	];
} elseif (in_array($page, ['terms-conditions', 'privacy-policy', 'deposit-policy', 'damage-management-policy'], true)) {
	$policyPages = [
		'terms-conditions' => [
			'title' => 'Terms & Conditions',
			'view' => 'policy/terms-conditions',
		],
		'privacy-policy' => [
			'title' => 'Privacy Policy',
			'view' => 'policy/privacy-policy',
		],
		'deposit-policy' => [
			'title' => 'Deposit Policy',
			'view' => 'policy/deposit-policy',
		],
		'damage-management-policy' => [
			'title' => 'Damage Management Policy',
			'view' => 'policy/damage-management-policy',
		],
	];

	$policy = $policyPages[$page];
	$title = 'Ridex | ' . $policy['title'];
	$view = $policy['view'];
	$viewData = [];
} else {
	$selectedHomeVehicleType = $sanitizeVehicleType($_GET['featured_type'] ?? 'cars');
	$featuredVehicles = [];

	try {
		$statement = db()->prepare(
			'SELECT v.*, c.name AS category_name
			FROM vehicles v
			INNER JOIN categories c ON c.id = v.category_id
			WHERE v.vehicle_type = :vehicle_type AND v.status = :status
			ORDER BY v.created_at DESC
			LIMIT 3'
		);
		$statement->execute([
			'vehicle_type' => $selectedHomeVehicleType,
			'status' => 'available',
		]);
		$featuredVehicles = $statement->fetchAll() ?: [];
	} catch (Throwable $exception) {
		error_log('Featured vehicle query failed: ' . $exception->getMessage());
		$featuredVehicles = [];
	}

	$title = 'Ridex | Drive Your Way';
	$view = 'home/index';
	$viewData = [
		'featuredVehicles' => $featuredVehicles,
		'selectedHomeVehicleType' => $selectedHomeVehicleType,
	];
}

require __DIR__ . '/../src/Templates/layout.php';
