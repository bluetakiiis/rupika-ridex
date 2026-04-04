<?php
/**
 * Purpose: Front controller for web requests; loads config, routes, and dispatches controllers.
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers/vehicle_json_sync.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

$vehicleJsonSyncDir = APP_ROOT . '/data/vehicles-json';
$defaultVehicleSyncStrategy = strtolower((string) env('APP_ENV', 'production')) === 'production'
	? 'db-first'
	: 'json-first';
$vehicleSyncStrategy = strtolower(trim((string) env('VEHICLE_SYNC_STRATEGY', $defaultVehicleSyncStrategy)));
if (!in_array($vehicleSyncStrategy, ['db-first', 'json-first'], true)) {
	$vehicleSyncStrategy = $defaultVehicleSyncStrategy;
}

$toBoolEnv = static function ($value): bool {
	$normalized = strtolower(trim((string) $value));
	return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
};

$defaultLegacyMirror = strtolower((string) env('APP_ENV', 'production')) === 'local' ? '1' : '0';
$enableLegacyVehicleJsonMirror = $toBoolEnv(env('ENABLE_LEGACY_JSON_MIRROR', $defaultLegacyMirror));

$legacyVehicleJsonSyncDir = APP_ROOT . '/var/cache/vehicles-json';
$mirrorVehicleJsonFiles = static function (string $sourceDir, string $targetDir) use ($enableLegacyVehicleJsonMirror): void {
	if (!$enableLegacyVehicleJsonMirror) {
		return;
	}

	$vehicleTypes = ['cars', 'bikes', 'luxury'];
	if (!is_dir($sourceDir)) {
		return;
	}

	if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
		error_log('Vehicle JSON mirror failed: unable to create directory ' . $targetDir);
		return;
	}

	foreach ($vehicleTypes as $vehicleType) {
		$sourceFile = rtrim($sourceDir, '\\/') . DIRECTORY_SEPARATOR . $vehicleType . '.json';
		$targetFile = rtrim($targetDir, '\\/') . DIRECTORY_SEPARATOR . $vehicleType . '.json';
		if (!is_file($sourceFile)) {
			continue;
		}

		$sourceMtime = (int) (@filemtime($sourceFile) ?: 0);
		$targetMtime = is_file($targetFile) ? (int) (@filemtime($targetFile) ?: 0) : 0;
		if ($sourceMtime <= $targetMtime) {
			continue;
		}

		if (!@copy($sourceFile, $targetFile)) {
			error_log('Vehicle JSON mirror failed while copying ' . $sourceFile . ' to ' . $targetFile);
		}
	}
};

// admin login: shared form state for login modal
$adminLoginError = '';
$adminLoginEmail = '';
$adminLoginEmailInvalid = false;
$adminLoginPasswordInvalid = false;

$isAdminLoginPost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-login';

$isAdminLogoutPost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-logout';

$isAdminDeleteVehiclePost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-delete-vehicle';

if ($isAdminLogoutPost) {
	unset($_SESSION['auth_user'], $_SESSION['admin_login_flash']);
	session_regenerate_id(true);
	header('Location: index.php', true, 303);
	exit;
}

// admin fleet delete: process delete requests from the Manage Fleet confirmation modal
// and persist the deletion directly in DB, then return to the same fleet filter context.
if ($isAdminDeleteVehiclePost) {
	$sessionUser = $_SESSION['auth_user'] ?? [];
	$isAdminSession = is_array($sessionUser) && (($sessionUser['role'] ?? '') === 'admin');

	if (!$isAdminSession) {
		header('Location: index.php', true, 302);
		exit;
	}

	$deleteAllowedModes = ['type', 'status'];
	$deleteAllowedTypes = ['cars', 'bikes', 'luxury'];
	$deleteAllowedStatuses = ['reserved', 'on_trip', 'overdue', 'maintenance'];

	$deleteFleetMode = strtolower(trim((string) ($_POST['fleet_mode'] ?? 'type')));
	if (!in_array($deleteFleetMode, $deleteAllowedModes, true)) {
		$deleteFleetMode = 'type';
	}

	$deleteFleetType = strtolower(trim((string) ($_POST['fleet_type'] ?? 'cars')));
	if (!in_array($deleteFleetType, $deleteAllowedTypes, true)) {
		$deleteFleetType = 'cars';
	}

	$deleteFleetStatus = strtolower(trim((string) ($_POST['fleet_status'] ?? 'reserved')));
	if (!in_array($deleteFleetStatus, $deleteAllowedStatuses, true)) {
		$deleteFleetStatus = 'reserved';
	}

	$deleteVehicleId = (int) ($_POST['vehicle_id'] ?? 0);

	$deleteRedirectQuery = [
		'page' => 'admin-manage-fleet',
		'fleet_mode' => $deleteFleetMode,
	];
	if ($deleteFleetMode === 'status') {
		$deleteRedirectQuery['fleet_status'] = $deleteFleetStatus;
	} else {
		$deleteRedirectQuery['fleet_type'] = $deleteFleetType;
	}
	$deleteRedirectUrl = 'index.php?' . http_build_query($deleteRedirectQuery);

	if ($deleteVehicleId > 0) {
		try {
			$pdo = db();
			$mirrorVehicleJsonFiles($legacyVehicleJsonSyncDir, $vehicleJsonSyncDir);

			// Ensure sync state exists before delete so DB-side deletion is detected as an intentional remove.
			sync_vehicles_json_bidirectional($pdo, $vehicleJsonSyncDir, [
				'prefer_json' => false,
				'prefer_db_timestamps' => true,
			]);

			$deleteVehicleStmt = $pdo->prepare('DELETE FROM vehicles WHERE id = :id LIMIT 1');
			$deleteVehicleStmt->execute([
				'id' => $deleteVehicleId,
			]);

			// Keep JSON source aligned with DB delete so sync does not restore removed rows.
			sync_vehicles_json_bidirectional($pdo, $vehicleJsonSyncDir, [
				'prefer_json' => false,
				'prefer_db_timestamps' => true,
			]);
			$mirrorVehicleJsonFiles($vehicleJsonSyncDir, $legacyVehicleJsonSyncDir);
		} catch (Throwable $exception) {
			error_log('Admin vehicle delete failed: ' . $exception->getMessage());
		}
	}

	header('Location: ' . $deleteRedirectUrl, true, 303);
	exit;
}

// admin login: keep validation state for one request only so refresh/close does not remember old errors.
$redirectWithAdminLoginFlash = static function (
	string $error,
	string $email,
	bool $emailInvalid,
	bool $passwordInvalid
): void {
	$_SESSION['admin_login_flash'] = [
		'error' => $error,
		'email' => $email,
		'email_invalid' => $emailInvalid,
		'password_invalid' => $passwordInvalid,
	];

	header('Location: index.php', true, 303);
	exit;
};

if (!$isAdminLoginPost && isset($_SESSION['admin_login_flash']) && is_array($_SESSION['admin_login_flash'])) {
	$adminFlash = $_SESSION['admin_login_flash'];
	unset($_SESSION['admin_login_flash']);

	$adminLoginError = trim((string) ($adminFlash['error'] ?? ''));
	$adminLoginEmail = trim((string) ($adminFlash['email'] ?? ''));
	$adminLoginEmailInvalid = (bool) ($adminFlash['email_invalid'] ?? false);
	$adminLoginPasswordInvalid = (bool) ($adminFlash['password_invalid'] ?? false);
}

// admin login: ensure the default admin credential exists with hashed password
$ensureDefaultAdminAccount = static function (PDO $pdo): void {
	$defaultAdminEmail = 'rupikadangol@gmail.com';
	$defaultAdminPassword = '12345678';
	$defaultAdminHash = password_hash($defaultAdminPassword, PASSWORD_DEFAULT);

	$canonicalUserStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
	$canonicalUserStmt->execute(['email' => $defaultAdminEmail]);
	$canonicalUserId = (int) $canonicalUserStmt->fetchColumn();

	if ($canonicalUserId <= 0) {
		$existingAdminStmt = $pdo->prepare('SELECT id FROM users WHERE role = :role ORDER BY id ASC LIMIT 1');
		$existingAdminStmt->execute(['role' => 'admin']);
		$existingAdminId = (int) $existingAdminStmt->fetchColumn();

		if ($existingAdminId > 0) {
			$reuseAdminStmt = $pdo->prepare(
				'UPDATE users
				 SET name = :name,
					 email = :email,
					 password_hash = :password_hash,
					 role = :role,
					 updated_at = CURRENT_TIMESTAMP
				 WHERE id = :id'
			);
			$reuseAdminStmt->execute([
				'name' => 'Ridex Admin',
				'email' => $defaultAdminEmail,
				'password_hash' => $defaultAdminHash,
				'role' => 'admin',
				'id' => $existingAdminId,
			]);
			$canonicalUserId = $existingAdminId;
		} else {
			$insertAdminStmt = $pdo->prepare(
				'INSERT INTO users (name, email, password_hash, role, created_at, updated_at)
				 VALUES (:name, :email, :password_hash, :role, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
			);
			$insertAdminStmt->execute([
				'name' => 'Ridex Admin',
				'email' => $defaultAdminEmail,
				'password_hash' => $defaultAdminHash,
				'role' => 'admin',
			]);
			$canonicalUserId = (int) $pdo->lastInsertId();
		}
	}

	if ($canonicalUserId > 0) {
		$normalizeCanonicalStmt = $pdo->prepare(
			'UPDATE users
			 SET name = :name,
				 email = :email,
				 password_hash = :password_hash,
				 role = :role,
				 updated_at = CURRENT_TIMESTAMP
			 WHERE id = :id'
		);
		$normalizeCanonicalStmt->execute([
			'name' => 'Ridex Admin',
			'email' => $defaultAdminEmail,
			'password_hash' => $defaultAdminHash,
			'role' => 'admin',
			'id' => $canonicalUserId,
		]);

		$demoteOtherAdminsStmt = $pdo->prepare(
			'UPDATE users
			 SET role = :user_role,
				 updated_at = CURRENT_TIMESTAMP
			 WHERE role = :admin_role AND id <> :id'
		);
		$demoteOtherAdminsStmt->execute([
			'user_role' => 'user',
			'admin_role' => 'admin',
			'id' => $canonicalUserId,
		]);
	}
};

// admin login: always ensure the default admin account exists in users(role=admin).
try {
	$ensureDefaultAdminAccount(db());
} catch (Throwable $exception) {
	error_log('Default admin setup failed: ' . $exception->getMessage());
}

// admin login: process modal login post and redirect to dashboard placeholder on success
if ($isAdminLoginPost) {
	$adminLoginEmail = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
	$adminLoginPassword = (string) ($_POST['admin_password'] ?? '');

	if ($adminLoginEmail === '' && $adminLoginPassword === '') {
		$redirectWithAdminLoginFlash(
			'Email and password are required.',
			'',
			true,
			true
		);
	} elseif ($adminLoginEmail === '') {
		$redirectWithAdminLoginFlash(
			'Email is required.',
			'',
			true,
			false
		);
	} elseif ($adminLoginPassword === '') {
		$redirectWithAdminLoginFlash(
			'Password is required.',
			$adminLoginEmail,
			false,
			true
		);
	} elseif (!filter_var($adminLoginEmail, FILTER_VALIDATE_EMAIL)) {
		$redirectWithAdminLoginFlash(
			'Please enter a valid email address.',
			$adminLoginEmail,
			true,
			false
		);
	} else {
		try {
			$pdo = db();

			$adminLookupStmt = $pdo->prepare(
				'SELECT id, name, email, phone, password_hash, role
				 FROM users
				 WHERE LOWER(email) = LOWER(:email) AND role = :role
				 LIMIT 1'
			);
			$adminLookupStmt->execute([
				'email' => $adminLoginEmail,
				'role' => 'admin',
			]);
			$adminUser = $adminLookupStmt->fetch();

			if (!is_array($adminUser)) {
				$redirectWithAdminLoginFlash(
					'Email is invalid. Please double check or try again.',
					$adminLoginEmail,
					true,
					false
				);
			} else {
				$passwordMatches = password_verify($adminLoginPassword, (string) ($adminUser['password_hash'] ?? ''));

				if (!$passwordMatches) {
					$redirectWithAdminLoginFlash(
						'Password is invalid. Please double check or try again.',
						$adminLoginEmail,
						false,
						true
					);
				} else {
					session_regenerate_id(true);
					$_SESSION['auth_user'] = [
						'id' => (int) ($adminUser['id'] ?? 0),
						'name' => (string) ($adminUser['name'] ?? 'Admin'),
						'email' => (string) ($adminUser['email'] ?? $adminLoginEmail),
						'phone' => trim((string) ($adminUser['phone'] ?? '')),
						'role' => 'admin',
					];

					header('Location: index.php?page=admin-dashboard', true, 302);
					exit;
				}
			}
		} catch (Throwable $exception) {
			error_log('Admin login failed: ' . $exception->getMessage());
			$redirectWithAdminLoginFlash(
				'Unable to complete admin login right now. Please try again.',
				$adminLoginEmail,
				true,
				true
			);
		}
	}
}

$runVehicleSync = static function (bool $preferDbTimestamps = false) use (
	$vehicleJsonSyncDir,
	$vehicleSyncStrategy,
	$legacyVehicleJsonSyncDir,
	$mirrorVehicleJsonFiles
): void {
	try {
		$mirrorVehicleJsonFiles($legacyVehicleJsonSyncDir, $vehicleJsonSyncDir);
		$useDbPriority = $preferDbTimestamps || $vehicleSyncStrategy === 'db-first';
		$syncOptions = $useDbPriority
			? [
				'prefer_json' => false,
				'prefer_db_timestamps' => true,
			]
			: [
				'prefer_json' => true,
			];

		sync_vehicles_json_bidirectional(db(), $vehicleJsonSyncDir, $syncOptions);
		$mirrorVehicleJsonFiles($vehicleJsonSyncDir, $legacyVehicleJsonSyncDir);
	} catch (Throwable $exception) {
		// Keep page rendering available even if JSON sync fails.
		error_log('Vehicle JSON sync failed: ' . $exception->getMessage());
	}
};

// Pull latest changes before read queries.
$runVehicleSync(false);
// Push website-originated DB changes (create/update/delete) after request handling.
register_shutdown_function(static function () use ($runVehicleSync): void {
	$runVehicleSync(true);
});

$allowedVehicleTypes = ['cars', 'bikes', 'luxury'];

$sanitizeVehicleType = static function ($rawType) use ($allowedVehicleTypes): string {
	$normalizedType = strtolower(trim((string) $rawType));

	return in_array($normalizedType, $allowedVehicleTypes, true)
		? $normalizedType
		: 'cars';
};

$page = strtolower(trim((string) ($_GET['page'] ?? 'home')));

$bookingSearchKeys = [
	'pickup-location',
	'return-location',
	'pickup-date',
	'return-date',
	'pickup-time',
	'return-time',
	'same-return',
];

$isBookingSearchAttempt = false;
foreach ($bookingSearchKeys as $bookingSearchKey) {
	if (array_key_exists($bookingSearchKey, $_GET)) {
		$isBookingSearchAttempt = true;
		break;
	}
}

if ($page === 'vehicles' && $isBookingSearchAttempt) {
	header('Location: index.php#home-vehicle-category', true, 302);
	exit;
}

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
} elseif ($page === 'admin-manage-fleet') {
	// manage fleet: admin fleet page with DB-driven type/status filtering.
	$sessionUser = $_SESSION['auth_user'] ?? [];
	$isAdminSession = is_array($sessionUser) && (($sessionUser['role'] ?? '') === 'admin');

	if (!$isAdminSession) {
		header('Location: index.php', true, 302);
		exit;
	}

	$allowedFleetModes = ['type', 'status'];
	$allowedFleetTypes = ['cars', 'bikes', 'luxury'];
	$allowedFleetStatuses = ['reserved', 'on_trip', 'overdue', 'maintenance'];

	$fleetMode = strtolower(trim((string) ($_GET['fleet_mode'] ?? 'type')));
	if (!in_array($fleetMode, $allowedFleetModes, true)) {
		$fleetMode = 'type';
	}

	$selectedFleetType = strtolower(trim((string) ($_GET['fleet_type'] ?? 'cars')));
	if (!in_array($selectedFleetType, $allowedFleetTypes, true)) {
		$selectedFleetType = 'cars';
	}

	$selectedFleetStatus = strtolower(trim((string) ($_GET['fleet_status'] ?? 'reserved')));
	if (!in_array($selectedFleetStatus, $allowedFleetStatuses, true)) {
		$selectedFleetStatus = 'reserved';
	}

	$fleetVehicles = [];
	try {
		$pdo = db();

		$fleetBaseSelectSql = "
			SELECT
				v.id,
				v.short_name,
				v.full_name,
				v.image_path,
				v.status,
				v.vehicle_type,
				v.number_of_seats,
				v.transmission_type,
				v.fuel_type,
				v.driver_age_requirement,
				v.license_plate,
				v.last_service_date,
				v.description,
				latest_booking.booking_number AS active_booking_number,
				latest_booking.pickup_datetime AS active_pickup_datetime,
				latest_booking.return_datetime AS active_return_datetime,
				latest_booking.payment_status AS active_payment_status,
				latest_booking.late_fee AS active_late_fee,
				booking_user.name AS active_booking_user_name,
				booking_user.phone AS active_booking_user_phone,
				latest_gps.latitude AS gps_latitude,
				latest_gps.longitude AS gps_longitude,
				upcoming_booking.pickup_datetime AS upcoming_pickup_datetime,
				COALESCE(vehicle_stats.total_reservations, 0) AS total_reservations,
				COALESCE(vehicle_stats.total_earnings, 0) AS total_earnings
			FROM vehicles v
			LEFT JOIN bookings latest_booking ON latest_booking.id = (
				SELECT b1.id
				FROM bookings b1
				WHERE b1.vehicle_id = v.id
					AND b1.status IN ('reserved', 'ready', 'on_trip', 'overdue', 'pending')
				ORDER BY COALESCE(b1.updated_at, b1.created_at) DESC, b1.id DESC
				LIMIT 1
			)
			LEFT JOIN users booking_user ON booking_user.id = latest_booking.user_id
			LEFT JOIN gps_logs latest_gps ON latest_gps.id = (
				SELECT g1.id
				FROM gps_logs g1
				WHERE g1.vehicle_id = v.id
				ORDER BY COALESCE(g1.timestamp, g1.created_at) DESC, g1.id DESC
				LIMIT 1
			)
			LEFT JOIN bookings upcoming_booking ON upcoming_booking.id = (
				SELECT b2.id
				FROM bookings b2
				WHERE b2.vehicle_id = v.id
					AND b2.status IN ('reserved', 'ready', 'pending')
					AND b2.pickup_datetime >= CURRENT_TIMESTAMP
				ORDER BY b2.pickup_datetime ASC, b2.id ASC
				LIMIT 1
			)
			LEFT JOIN (
				SELECT
					vehicle_id,
					COUNT(*) AS total_reservations,
					COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN paid_amount ELSE 0 END), 0) AS total_earnings
				FROM bookings
				GROUP BY vehicle_id
			) vehicle_stats ON vehicle_stats.vehicle_id = v.id
		";

		if ($fleetMode === 'status') {
			$fleetStmt = $pdo->prepare(
				$fleetBaseSelectSql . '
				WHERE v.status = :status
				ORDER BY v.id DESC'
			);
			$fleetStmt->execute([
				'status' => $selectedFleetStatus,
			]);
		} else {
			$fleetStmt = $pdo->prepare(
				$fleetBaseSelectSql . '
				WHERE v.vehicle_type = :vehicle_type AND v.status = :status
				ORDER BY v.id DESC'
			);
			$fleetStmt->execute([
				'vehicle_type' => $selectedFleetType,
				'status' => 'available',
			]);
		}

		$fleetVehicles = $fleetStmt->fetchAll() ?: [];
	} catch (Throwable $exception) {
		error_log('Admin manage fleet data failed: ' . $exception->getMessage());
		$fleetVehicles = [];
	}

	$title = 'Ridex | Manage Fleet';
	$view = 'admin/vehicles/list';
	$viewData = [
		'adminUserName' => (string) ($sessionUser['name'] ?? 'Admin'),
		'fleetMode' => $fleetMode,
		'selectedFleetType' => $selectedFleetType,
		'selectedFleetStatus' => $selectedFleetStatus,
		'fleetVehicles' => $fleetVehicles,
	];
} elseif ($page === 'admin-dashboard') {
	$sessionUser = $_SESSION['auth_user'] ?? [];
	$isAdminSession = is_array($sessionUser) && (($sessionUser['role'] ?? '') === 'admin');

	if (!$isAdminSession) {
		header('Location: index.php', true, 302);
		exit;
	}

	$calculatePercentChange = static function (?float $current, ?float $previous): ?float {
		if ($current === null || $previous === null) {
			return null;
		}

		if (abs($previous) < 0.00001) {
			return $current > 0 ? 100.0 : null;
		}

		return (($current - $previous) / abs($previous)) * 100;
	};

	$dashboardKpis = [
		'totalRevenue' => [
			'value' => null,
			'trend' => null,
		],
		'activeRentals' => [
			'value' => null,
			'trend' => null,
		],
		'totalFleet' => [
			'value' => null,
			'trend' => null,
		],
		'fleetAvailability' => [
			'value' => null,
			'trend' => null,
		],
	];

	$dashboardCharts = [
		'salesVehicleCategory' => [
			'labels' => [],
			'datasets' => [
				[
					'label' => 'Cars',
					'data' => [],
					'borderColor' => '#f75b7a',
				],
				[
					'label' => 'Bikes',
					'data' => [],
					'borderColor' => '#45aaf2',
				],
				[
					'label' => 'Luxury',
					'data' => [],
					'borderColor' => '#2db9b0',
				],
			],
		],
		'mostRentedVehicleCategory' => [
			'labels' => ['Cars', 'Bikes', 'Luxury'],
			'datasets' => [
				[
					'label' => 'Most Rented',
					'data' => [0, 0, 0],
					'backgroundColor' => ['#f75b7a', '#f6a340', '#f4ca55'],
				],
			],
		],
	];

	try {
		$pdo = db();

		$fleetStatsStmt = $pdo->query(
			'SELECT
				COUNT(*) AS total_fleet,
				SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) AS available_fleet
			 FROM vehicles'
		);
		$fleetStats = $fleetStatsStmt->fetch() ?: [];
		$totalFleet = (int) ($fleetStats['total_fleet'] ?? 0);
		$availableFleet = (int) ($fleetStats['available_fleet'] ?? 0);

		if ($totalFleet > 0) {
			$dashboardKpis['totalFleet']['value'] = $totalFleet;
			$dashboardKpis['fleetAvailability']['value'] = round(($availableFleet / $totalFleet) * 100, 1);
		}

		$bookingStatsStmt = $pdo->query(
			'SELECT
				COUNT(*) AS total_bookings,
				SUM(CASE WHEN status IN ("reserved", "on_trip", "overdue") THEN 1 ELSE 0 END) AS active_rentals,
				SUM(CASE WHEN payment_status = "paid" THEN paid_amount ELSE 0 END) AS paid_revenue
			 FROM bookings'
		);
		$bookingStats = $bookingStatsStmt->fetch() ?: [];
		$totalBookings = (int) ($bookingStats['total_bookings'] ?? 0);

		if ($totalBookings > 0) {
			$dashboardKpis['activeRentals']['value'] = (int) ($bookingStats['active_rentals'] ?? 0);
			$dashboardKpis['totalRevenue']['value'] = (float) ($bookingStats['paid_revenue'] ?? 0);
		}

		$today = new DateTimeImmutable('today');
		$currentStart = $today->sub(new DateInterval('P30D'));
		$previousStart = $today->sub(new DateInterval('P60D'));

		$periodParams = [
			'current_start' => $currentStart->format('Y-m-d H:i:s'),
			'current_end' => $today->format('Y-m-d H:i:s'),
			'previous_start' => $previousStart->format('Y-m-d H:i:s'),
			'previous_end' => $currentStart->format('Y-m-d H:i:s'),
		];

		$revenueTrendStmt = $pdo->prepare(
			'SELECT
				SUM(CASE WHEN created_at >= :current_start AND created_at < :current_end AND payment_status = "paid" THEN paid_amount ELSE 0 END) AS current_revenue,
				SUM(CASE WHEN created_at >= :previous_start AND created_at < :previous_end AND payment_status = "paid" THEN paid_amount ELSE 0 END) AS previous_revenue
			 FROM bookings'
		);
		$revenueTrendStmt->execute($periodParams);
		$revenueTrend = $revenueTrendStmt->fetch() ?: [];
		$dashboardKpis['totalRevenue']['trend'] = $calculatePercentChange(
			(float) ($revenueTrend['current_revenue'] ?? 0),
			(float) ($revenueTrend['previous_revenue'] ?? 0)
		);

		$activeTrendStmt = $pdo->prepare(
			'SELECT
				SUM(CASE WHEN created_at >= :current_start AND created_at < :current_end AND status IN ("reserved", "on_trip", "overdue") THEN 1 ELSE 0 END) AS current_active,
				SUM(CASE WHEN created_at >= :previous_start AND created_at < :previous_end AND status IN ("reserved", "on_trip", "overdue") THEN 1 ELSE 0 END) AS previous_active
			 FROM bookings'
		);
		$activeTrendStmt->execute($periodParams);
		$activeTrend = $activeTrendStmt->fetch() ?: [];
		$dashboardKpis['activeRentals']['trend'] = $calculatePercentChange(
			(float) ($activeTrend['current_active'] ?? 0),
			(float) ($activeTrend['previous_active'] ?? 0)
		);

		$fleetTrendStmt = $pdo->prepare(
			'SELECT
				SUM(CASE WHEN created_at >= :current_start AND created_at < :current_end THEN 1 ELSE 0 END) AS current_fleet,
				SUM(CASE WHEN created_at >= :previous_start AND created_at < :previous_end THEN 1 ELSE 0 END) AS previous_fleet,
				SUM(CASE WHEN created_at >= :current_start AND created_at < :current_end AND status = "available" THEN 1 ELSE 0 END) AS current_available,
				SUM(CASE WHEN created_at >= :previous_start AND created_at < :previous_end AND status = "available" THEN 1 ELSE 0 END) AS previous_available
			 FROM vehicles'
		);
		$fleetTrendStmt->execute($periodParams);
		$fleetTrend = $fleetTrendStmt->fetch() ?: [];

		$dashboardKpis['totalFleet']['trend'] = $calculatePercentChange(
			(float) ($fleetTrend['current_fleet'] ?? 0),
			(float) ($fleetTrend['previous_fleet'] ?? 0)
		);

		$currentFleetWindow = (float) ($fleetTrend['current_fleet'] ?? 0);
		$previousFleetWindow = (float) ($fleetTrend['previous_fleet'] ?? 0);
		$currentAvailabilityRatio = $currentFleetWindow > 0 ? ((float) ($fleetTrend['current_available'] ?? 0) / $currentFleetWindow) * 100 : null;
		$previousAvailabilityRatio = $previousFleetWindow > 0 ? ((float) ($fleetTrend['previous_available'] ?? 0) / $previousFleetWindow) * 100 : null;
		$dashboardKpis['fleetAvailability']['trend'] = $calculatePercentChange($currentAvailabilityRatio, $previousAvailabilityRatio);

		$lineDays = 15;
		$lineDateKeys = [];
		$lineLabels = [];
		$lineIndexByDate = [];

		for ($index = $lineDays - 1; $index >= 0; $index--) {
			$date = (new DateTimeImmutable('today'))->sub(new DateInterval('P' . $index . 'D'));
			$dateKey = $date->format('Y-m-d');
			$lineDateKeys[] = $dateKey;
			$lineLabels[] = $date->format('M j');
		}

		$lineIndexByDate = array_flip($lineDateKeys);
		$lineCars = array_fill(0, $lineDays, 0);
		$lineBikes = array_fill(0, $lineDays, 0);
		$lineLuxury = array_fill(0, $lineDays, 0);

		$lineChartStmt = $pdo->prepare(
			'SELECT
				DATE(b.pickup_datetime) AS booking_date,
				v.vehicle_type,
				COUNT(*) AS total
			 FROM bookings b
			 INNER JOIN vehicles v ON v.id = b.vehicle_id
			 WHERE b.pickup_datetime >= :line_start
			 GROUP BY DATE(b.pickup_datetime), v.vehicle_type
			 ORDER BY booking_date ASC'
		);
		$lineChartStmt->execute([
			'line_start' => $lineDateKeys[0] . ' 00:00:00',
		]);

		while ($lineRow = $lineChartStmt->fetch()) {
			$dateKey = (string) ($lineRow['booking_date'] ?? '');
			$vehicleType = strtolower(trim((string) ($lineRow['vehicle_type'] ?? '')));
			$total = (int) ($lineRow['total'] ?? 0);

			if (!array_key_exists($dateKey, $lineIndexByDate)) {
				continue;
			}

			$targetIndex = (int) $lineIndexByDate[$dateKey];
			if ($vehicleType === 'cars') {
				$lineCars[$targetIndex] = $total;
			} elseif ($vehicleType === 'bikes') {
				$lineBikes[$targetIndex] = $total;
			} elseif ($vehicleType === 'luxury') {
				$lineLuxury[$targetIndex] = $total;
			}
		}

		$dashboardCharts['salesVehicleCategory']['labels'] = $lineLabels;
		$dashboardCharts['salesVehicleCategory']['datasets'][0]['data'] = $lineCars;
		$dashboardCharts['salesVehicleCategory']['datasets'][1]['data'] = $lineBikes;
		$dashboardCharts['salesVehicleCategory']['datasets'][2]['data'] = $lineLuxury;

		$pieChartStmt = $pdo->query(
			'SELECT
				v.vehicle_type,
				COUNT(*) AS total
			 FROM bookings b
			 INNER JOIN vehicles v ON v.id = b.vehicle_id
			 GROUP BY v.vehicle_type'
		);

		$pieCounts = [
			'cars' => 0,
			'bikes' => 0,
			'luxury' => 0,
		];

		while ($pieRow = $pieChartStmt->fetch()) {
			$type = strtolower(trim((string) ($pieRow['vehicle_type'] ?? '')));
			if (array_key_exists($type, $pieCounts)) {
				$pieCounts[$type] = (int) ($pieRow['total'] ?? 0);
			}
		}

		$dashboardCharts['mostRentedVehicleCategory']['datasets'][0]['data'] = [
			$pieCounts['cars'],
			$pieCounts['bikes'],
			$pieCounts['luxury'],
		];
	} catch (Throwable $exception) {
		error_log('Admin dashboard data failed: ' . $exception->getMessage());
	}

	$title = 'Ridex | Admin Dashboard';
	$view = 'admin/dashboard';
	$viewData = [
		'adminUserName' => (string) ($sessionUser['name'] ?? 'Admin'),
		'dashboardKpis' => $dashboardKpis,
		'dashboardCharts' => $dashboardCharts,
	];
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
