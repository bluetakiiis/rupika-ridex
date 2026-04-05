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

// maintenance edit/fillup form: ensure the maintenance records table exists before maintenance CRUD actions.
$ensureMaintenanceRecordsTable = static function (PDO $pdo): void {
	$pdo->exec(
		'CREATE TABLE IF NOT EXISTS vehicle_maintenance_records (
			id INT AUTO_INCREMENT PRIMARY KEY,
			vehicle_id INT NOT NULL,
			issue_description TEXT NOT NULL,
			workshop_name VARCHAR(150) NOT NULL,
			estimate_completion_date DATE NOT NULL,
			service_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
			status ENUM("open","completed") NOT NULL DEFAULT "open",
			completed_at TIMESTAMP NULL DEFAULT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_maintenance_records_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
			INDEX idx_maintenance_vehicle_status (vehicle_id, status),
			INDEX idx_maintenance_vehicle_created (vehicle_id, created_at)
		)'
	);
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

$isAdminStartMaintenancePost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-start-maintenance';

$isAdminUpdateMaintenancePost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-update-maintenance';

$isAdminCompleteMaintenancePost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-complete-maintenance';

$isAdminCreateVehiclePost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-create-vehicle';

$isAdminUpdateVehiclePost =
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& strtolower(trim((string) ($_POST['action'] ?? ''))) === 'admin-update-vehicle';

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

// create vehicle modal: persist a new vehicle from the 3-part create modal with full required-field validation.
if ($isAdminCreateVehiclePost) {
	$sessionUser = $_SESSION['auth_user'] ?? [];
	$isAdminSession = is_array($sessionUser) && (($sessionUser['role'] ?? '') === 'admin');

	if (!$isAdminSession) {
		header('Location: index.php', true, 302);
		exit;
	}

	$allowedFleetModes = ['type', 'status'];
	$allowedFleetTypes = ['cars', 'bikes', 'luxury'];
	$allowedDriverAges = [18, 21];
	$allowedVehicleStatuses = ['available', 'reserved', 'on_trip', 'overdue', 'maintenance'];
	$allowedFuelTypes = ['petrol', 'diesel', 'electric'];
	$transmissionMap = [
		'manual' => 'manual',
		'automatic' => 'automatic',
		'hybrid' => 'hybrid',
		'n/a' => 'N/A',
		'na' => 'N/A',
	];

	$fleetMode = strtolower(trim((string) ($_POST['fleet_mode'] ?? 'type')));
	if (!in_array($fleetMode, $allowedFleetModes, true)) {
		$fleetMode = 'type';
	}

	$fleetType = strtolower(trim((string) ($_POST['fleet_type'] ?? 'cars')));
	if (!in_array($fleetType, $allowedFleetTypes, true)) {
		$fleetType = 'cars';
	}

	$vehicleType = strtolower(trim((string) ($_POST['vehicle_type'] ?? $fleetType)));
	if (!in_array($vehicleType, $allowedFleetTypes, true)) {
		$vehicleType = $fleetType;
	}

	$fullName = trim((string) ($_POST['full_name'] ?? ''));
	$shortName = trim((string) ($_POST['short_name'] ?? ''));
	$pricePerDayRaw = (float) ($_POST['price_per_day'] ?? 0);
	$driverAgeRequirement = (int) ($_POST['driver_age_requirement'] ?? 0);
	$numberOfSeats = (int) ($_POST['number_of_seats'] ?? 0);
	$transmissionInput = strtolower(trim((string) ($_POST['transmission_type'] ?? 'manual')));
	$fuelType = strtolower(trim((string) ($_POST['fuel_type'] ?? 'petrol')));
	$licensePlate = strtoupper(trim((string) ($_POST['license_plate'] ?? '')));
	$gpsId = trim((string) ($_POST['gps_id'] ?? ''));
	$status = strtolower(trim((string) ($_POST['status'] ?? 'available')));
	$lastServiceDateRaw = trim((string) ($_POST['last_service_date'] ?? ''));
	$description = trim((string) ($_POST['description'] ?? ''));

	$redirectQuery = [
		'page' => 'admin-manage-fleet',
		'fleet_mode' => 'type',
		'fleet_type' => $fleetType,
	];
	$redirectUrl = 'index.php?' . http_build_query($redirectQuery);

	$parseServiceDate = static function (string $rawDate): ?string {
		$normalizedDate = trim($rawDate);
		if ($normalizedDate === '') {
			return null;
		}

		$formats = ['Y-m-d', 'd/m/Y', 'd M, Y'];
		foreach ($formats as $format) {
			$parsedDate = DateTimeImmutable::createFromFormat('!' . $format, $normalizedDate);
			$errors = DateTimeImmutable::getLastErrors();
			$hasDateErrors = is_array($errors)
				&& ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0);

			if ($parsedDate instanceof DateTimeImmutable && !$hasDateErrors) {
				return $parsedDate->format('Y-m-d');
			}
		}

		return null;
	};

	$lastServiceDate = $parseServiceDate($lastServiceDateRaw);
	$transmissionType = $transmissionMap[$transmissionInput] ?? '';
	$pricePerDay = is_finite($pricePerDayRaw) ? (int) round($pricePerDayRaw) : 0;

	$upload = $_FILES['image_file'] ?? null;
	$hasValidUploadArray = is_array($upload)
		&& array_key_exists('error', $upload)
		&& array_key_exists('tmp_name', $upload)
		&& array_key_exists('name', $upload);
	$uploadError = $hasValidUploadArray ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

	$hasMissingRequiredData =
		$vehicleType === ''
		|| $fullName === ''
		|| $shortName === ''
		|| $pricePerDay <= 0
		|| !in_array($driverAgeRequirement, $allowedDriverAges, true)
		|| $numberOfSeats <= 0
		|| $transmissionType === ''
		|| !in_array($fuelType, $allowedFuelTypes, true)
		|| $licensePlate === ''
		|| $gpsId === ''
		|| !in_array($status, $allowedVehicleStatuses, true)
		|| $lastServiceDate === null
		|| $description === ''
		|| $uploadError !== UPLOAD_ERR_OK;

	if ($hasMissingRequiredData) {
		header('Location: ' . $redirectUrl, true, 303);
		exit;
	}

	$categoryNameByType = [
		'cars' => 'Cars',
		'bikes' => 'Bikes',
		'luxury' => 'Luxury',
	];
	$categoryDescriptionByType = [
		'cars' => 'Passenger cars and sedans',
		'bikes' => 'Two-wheel vehicles',
		'luxury' => 'Premium and high-end vehicles',
	];

	$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
	$allowedImageMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
	$sourceImageName = (string) ($upload['name'] ?? '');
	$sourceImageTmpPath = (string) ($upload['tmp_name'] ?? '');
	$imageExtension = strtolower((string) pathinfo($sourceImageName, PATHINFO_EXTENSION));

	if ($sourceImageTmpPath === '' || !in_array($imageExtension, $allowedImageExtensions, true)) {
		header('Location: ' . $redirectUrl, true, 303);
		exit;
	}

	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$detectedMimeType = $finfo ? (string) finfo_file($finfo, $sourceImageTmpPath) : '';
	if ($finfo) {
		finfo_close($finfo);
	}

	if (!in_array($detectedMimeType, $allowedImageMimeTypes, true)) {
		header('Location: ' . $redirectUrl, true, 303);
		exit;
	}

	$uploadDirectory = APP_ROOT . '/public/uploads/vehicles';
	if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
		header('Location: ' . $redirectUrl, true, 303);
		exit;
	}

	$syncVehicleDataAfterCreate = static function (PDO $pdo) use (
		$mirrorVehicleJsonFiles,
		$legacyVehicleJsonSyncDir,
		$vehicleJsonSyncDir
	): void {
		try {
			$mirrorVehicleJsonFiles($legacyVehicleJsonSyncDir, $vehicleJsonSyncDir);
			sync_vehicles_json_bidirectional($pdo, $vehicleJsonSyncDir, [
				'prefer_json' => false,
				'prefer_db_timestamps' => true,
			]);
			$mirrorVehicleJsonFiles($vehicleJsonSyncDir, $legacyVehicleJsonSyncDir);
		} catch (Throwable $syncException) {
			error_log('Admin create vehicle sync failed: ' . $syncException->getMessage());
		}
	};

	$storedImageAbsolutePath = '';
	$createdVehicleId = 0;

	try {
		$pdo = db();
		$pdo->beginTransaction();

		$categoryName = $categoryNameByType[$vehicleType] ?? 'Cars';
		$categoryDescription = $categoryDescriptionByType[$vehicleType] ?? 'Vehicle category';

		$categoryLookupStmt = $pdo->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1 FOR UPDATE');
		$categoryLookupStmt->execute([
			'name' => $categoryName,
		]);
		$categoryId = (int) $categoryLookupStmt->fetchColumn();

		if ($categoryId <= 0) {
			$createCategoryStmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
			$createCategoryStmt->execute([
				'name' => $categoryName,
				'description' => $categoryDescription,
			]);
			$categoryId = (int) $pdo->lastInsertId();
		}

		if ($categoryId <= 0) {
			throw new RuntimeException('Unable to resolve category for vehicle creation.');
		}

		$duplicateLicenseStmt = $pdo->prepare('SELECT id FROM vehicles WHERE license_plate = :license_plate LIMIT 1 FOR UPDATE');
		$duplicateLicenseStmt->execute([
			'license_plate' => $licensePlate,
		]);
		if ((int) $duplicateLicenseStmt->fetchColumn() > 0) {
			throw new RuntimeException('Vehicle license plate already exists.');
		}

		try {
			$randomSuffix = bin2hex(random_bytes(5));
		} catch (Throwable $randomException) {
			$randomSuffix = (string) mt_rand(10000, 99999);
		}

		$storedImageName = 'vehicle-' . date('YmdHis') . '-' . $randomSuffix . '.' . $imageExtension;
		$storedImageAbsolutePath = rtrim($uploadDirectory, '\\/') . DIRECTORY_SEPARATOR . $storedImageName;
		$storedImageRelativePath = 'uploads/vehicles/' . $storedImageName;

		if (!move_uploaded_file($sourceImageTmpPath, $storedImageAbsolutePath)) {
			throw new RuntimeException('Vehicle image upload failed.');
		}

		// create vehicle modal: assign the next in-line vehicle ID (MAX(id)+1) instead of relying on auto-increment gaps.
		$nextVehicleIdStmt = $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 AS next_vehicle_id FROM vehicles FOR UPDATE');
		$nextVehicleId = (int) $nextVehicleIdStmt->fetchColumn();
		if ($nextVehicleId <= 0) {
			$nextVehicleId = 1;
		}

		$insertVehicleStmt = $pdo->prepare(
			'INSERT INTO vehicles (
				id,
				category_id,
				vehicle_type,
				short_name,
				full_name,
				price_per_day,
				driver_age_requirement,
				image_path,
				number_of_seats,
				transmission_type,
				fuel_type,
				license_plate,
				status,
				gps_id,
				last_service_date,
				description
			) VALUES (
				:id,
				:category_id,
				:vehicle_type,
				:short_name,
				:full_name,
				:price_per_day,
				:driver_age_requirement,
				:image_path,
				:number_of_seats,
				:transmission_type,
				:fuel_type,
				:license_plate,
				:status,
				:gps_id,
				:last_service_date,
				:description
			)'
		);
		$insertVehicleStmt->execute([
			'id' => $nextVehicleId,
			'category_id' => $categoryId,
			'vehicle_type' => $vehicleType,
			'short_name' => $shortName,
			'full_name' => $fullName,
			'price_per_day' => $pricePerDay,
			'driver_age_requirement' => $driverAgeRequirement,
			'image_path' => $storedImageRelativePath,
			'number_of_seats' => $numberOfSeats,
			'transmission_type' => $transmissionType,
			'fuel_type' => $fuelType,
			'license_plate' => $licensePlate,
			'status' => $status,
			'gps_id' => $gpsId,
			'last_service_date' => $lastServiceDate,
			'description' => $description,
		]);

		$createdVehicleId = $nextVehicleId;
		$pdo->commit();
		$syncVehicleDataAfterCreate($pdo);

		$successRedirectQuery = [
			'page' => 'admin-manage-fleet',
		];
		if ($status === 'available') {
			$successRedirectQuery['fleet_mode'] = 'type';
			$successRedirectQuery['fleet_type'] = $vehicleType;
		} else {
			$successRedirectQuery['fleet_mode'] = 'status';
			$successRedirectQuery['fleet_status'] = $status;
		}
		if ($createdVehicleId > 0) {
			$successRedirectQuery['open_read_vehicle_id'] = $createdVehicleId;
		}

		header('Location: index.php?' . http_build_query($successRedirectQuery), true, 303);
		exit;
	} catch (Throwable $exception) {
		if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
			$pdo->rollBack();
		}

		if ($storedImageAbsolutePath !== '' && is_file($storedImageAbsolutePath)) {
			@unlink($storedImageAbsolutePath);
		}

		error_log('Admin create vehicle failed: ' . $exception->getMessage());
	}

	header('Location: ' . $redirectUrl, true, 303);
	exit;
}

	// edit vehicle modal: persist vehicle updates from the 3-part edit modal with optional image replacement.
	if ($isAdminUpdateVehiclePost) {
		$sessionUser = $_SESSION['auth_user'] ?? [];
		$isAdminSession = is_array($sessionUser) && (($sessionUser['role'] ?? '') === 'admin');

		if (!$isAdminSession) {
			header('Location: index.php', true, 302);
			exit;
		}

		$allowedFleetModes = ['type', 'status'];
		$allowedFleetTypes = ['cars', 'bikes', 'luxury'];
		$allowedDriverAges = [18, 21];
		$allowedVehicleStatuses = ['available', 'reserved', 'on_trip', 'overdue', 'maintenance'];
		$allowedFuelTypes = ['petrol', 'diesel', 'electric'];
		$transmissionMap = [
			'manual' => 'manual',
			'automatic' => 'automatic',
			'hybrid' => 'hybrid',
			'n/a' => 'N/A',
			'na' => 'N/A',
		];

		$fleetMode = strtolower(trim((string) ($_POST['fleet_mode'] ?? 'type')));
		if (!in_array($fleetMode, $allowedFleetModes, true)) {
			$fleetMode = 'type';
		}

		$fleetType = strtolower(trim((string) ($_POST['fleet_type'] ?? 'cars')));
		if (!in_array($fleetType, $allowedFleetTypes, true)) {
			$fleetType = 'cars';
		}

		$fleetStatus = strtolower(trim((string) ($_POST['fleet_status'] ?? 'reserved')));
		if (!in_array($fleetStatus, $allowedVehicleStatuses, true)) {
			$fleetStatus = 'reserved';
		}

		$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
		$vehicleType = strtolower(trim((string) ($_POST['vehicle_type'] ?? $fleetType)));
		if (!in_array($vehicleType, $allowedFleetTypes, true)) {
			$vehicleType = $fleetType;
		}

		$fullName = trim((string) ($_POST['full_name'] ?? ''));
		$shortName = trim((string) ($_POST['short_name'] ?? ''));
		$pricePerDayRaw = (float) ($_POST['price_per_day'] ?? 0);
		$driverAgeRequirement = (int) ($_POST['driver_age_requirement'] ?? 0);
		$numberOfSeats = (int) ($_POST['number_of_seats'] ?? 0);
		$transmissionInput = strtolower(trim((string) ($_POST['transmission_type'] ?? 'manual')));
		$fuelType = strtolower(trim((string) ($_POST['fuel_type'] ?? 'petrol')));
		$licensePlate = strtoupper(trim((string) ($_POST['license_plate'] ?? '')));
		$gpsId = trim((string) ($_POST['gps_id'] ?? ''));
		$status = strtolower(trim((string) ($_POST['status'] ?? 'available')));
		$lastServiceDateRaw = trim((string) ($_POST['last_service_date'] ?? ''));
		$description = trim((string) ($_POST['description'] ?? ''));

		$redirectQuery = [
			'page' => 'admin-manage-fleet',
			'fleet_mode' => $fleetMode,
		];
		if ($fleetMode === 'status') {
			$redirectQuery['fleet_status'] = $fleetStatus;
		} else {
			$redirectQuery['fleet_type'] = $fleetType;
		}
		$redirectUrl = 'index.php?' . http_build_query($redirectQuery);

		if ($vehicleId <= 0) {
			header('Location: ' . $redirectUrl, true, 303);
			exit;
		}

		$parseServiceDate = static function (string $rawDate): ?string {
			$normalizedDate = trim($rawDate);
			if ($normalizedDate === '') {
				return null;
			}

			$formats = ['Y-m-d', 'd/m/Y', 'd M, Y'];
			foreach ($formats as $format) {
				$parsedDate = DateTimeImmutable::createFromFormat('!' . $format, $normalizedDate);
				$errors = DateTimeImmutable::getLastErrors();
				$hasDateErrors = is_array($errors)
					&& ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0);

				if ($parsedDate instanceof DateTimeImmutable && !$hasDateErrors) {
					return $parsedDate->format('Y-m-d');
				}
			}

			return null;
		};

		$lastServiceDate = $parseServiceDate($lastServiceDateRaw);
		$transmissionType = $transmissionMap[$transmissionInput] ?? '';
		$pricePerDay = is_finite($pricePerDayRaw) ? (int) round($pricePerDayRaw) : 0;

		$upload = $_FILES['image_file'] ?? null;
		$hasValidUploadArray = is_array($upload)
			&& array_key_exists('error', $upload)
			&& array_key_exists('tmp_name', $upload)
			&& array_key_exists('name', $upload);
		$uploadError = $hasValidUploadArray ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
		$isReplacingImage = $uploadError === UPLOAD_ERR_OK;

		if (!in_array($uploadError, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
			header('Location: ' . $redirectUrl, true, 303);
			exit;
		}

		$hasMissingRequiredData =
			$vehicleType === ''
			|| $fullName === ''
			|| $shortName === ''
			|| $pricePerDay <= 0
			|| !in_array($driverAgeRequirement, $allowedDriverAges, true)
			|| $numberOfSeats <= 0
			|| $transmissionType === ''
			|| !in_array($fuelType, $allowedFuelTypes, true)
			|| $licensePlate === ''
			|| $gpsId === ''
			|| !in_array($status, $allowedVehicleStatuses, true)
			|| $lastServiceDate === null
			|| $description === '';

		if ($hasMissingRequiredData) {
			header('Location: ' . $redirectUrl, true, 303);
			exit;
		}

		$uploadDirectory = APP_ROOT . '/public/uploads/vehicles';
		$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
		$allowedImageMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
		$sourceImageName = '';
		$sourceImageTmpPath = '';
		$imageExtension = '';

		if ($isReplacingImage) {
			$sourceImageName = (string) ($upload['name'] ?? '');
			$sourceImageTmpPath = (string) ($upload['tmp_name'] ?? '');
			$imageExtension = strtolower((string) pathinfo($sourceImageName, PATHINFO_EXTENSION));

			if ($sourceImageTmpPath === '' || !in_array($imageExtension, $allowedImageExtensions, true)) {
				header('Location: ' . $redirectUrl, true, 303);
				exit;
			}

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$detectedMimeType = $finfo ? (string) finfo_file($finfo, $sourceImageTmpPath) : '';
			if ($finfo) {
				finfo_close($finfo);
			}

			if (!in_array($detectedMimeType, $allowedImageMimeTypes, true)) {
				header('Location: ' . $redirectUrl, true, 303);
				exit;
			}

			if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
				header('Location: ' . $redirectUrl, true, 303);
				exit;
			}
		}

		$syncVehicleDataAfterUpdate = static function (PDO $pdo) use (
			$mirrorVehicleJsonFiles,
			$legacyVehicleJsonSyncDir,
			$vehicleJsonSyncDir
		): void {
			try {
				$mirrorVehicleJsonFiles($legacyVehicleJsonSyncDir, $vehicleJsonSyncDir);
				sync_vehicles_json_bidirectional($pdo, $vehicleJsonSyncDir, [
					'prefer_json' => false,
					'prefer_db_timestamps' => true,
				]);
				$mirrorVehicleJsonFiles($vehicleJsonSyncDir, $legacyVehicleJsonSyncDir);
			} catch (Throwable $syncException) {
				error_log('Admin update vehicle sync failed: ' . $syncException->getMessage());
			}
		};

		$newImageAbsolutePath = '';
		$previousImageRelativePath = '';

		try {
			$pdo = db();
			$pdo->beginTransaction();

			$existingVehicleStmt = $pdo->prepare(
				'SELECT id, image_path
				 FROM vehicles
				 WHERE id = :id
				 LIMIT 1
				 FOR UPDATE'
			);
			$existingVehicleStmt->execute(['id' => $vehicleId]);
			$existingVehicle = $existingVehicleStmt->fetch();

			if (!is_array($existingVehicle)) {
				$pdo->rollBack();
				header('Location: ' . $redirectUrl, true, 303);
				exit;
			}

			$previousImageRelativePath = trim((string) ($existingVehicle['image_path'] ?? ''));
			$updatedImageRelativePath = $previousImageRelativePath;

			$categoryNameByType = [
				'cars' => 'Cars',
				'bikes' => 'Bikes',
				'luxury' => 'Luxury',
			];
			$categoryDescriptionByType = [
				'cars' => 'Passenger cars and sedans',
				'bikes' => 'Two-wheel vehicles',
				'luxury' => 'Premium and high-end vehicles',
			];

			$categoryName = $categoryNameByType[$vehicleType] ?? 'Cars';
			$categoryDescription = $categoryDescriptionByType[$vehicleType] ?? 'Vehicle category';

			$categoryLookupStmt = $pdo->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1 FOR UPDATE');
			$categoryLookupStmt->execute([
				'name' => $categoryName,
			]);
			$categoryId = (int) $categoryLookupStmt->fetchColumn();

			if ($categoryId <= 0) {
				$createCategoryStmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
				$createCategoryStmt->execute([
					'name' => $categoryName,
					'description' => $categoryDescription,
				]);
				$categoryId = (int) $pdo->lastInsertId();
			}

			if ($categoryId <= 0) {
				throw new RuntimeException('Unable to resolve category for vehicle update.');
			}

			$duplicateLicenseStmt = $pdo->prepare('SELECT id FROM vehicles WHERE license_plate = :license_plate AND id <> :id LIMIT 1 FOR UPDATE');
			$duplicateLicenseStmt->execute([
				'license_plate' => $licensePlate,
				'id' => $vehicleId,
			]);
			if ((int) $duplicateLicenseStmt->fetchColumn() > 0) {
				throw new RuntimeException('Vehicle license plate already exists.');
			}

			if ($isReplacingImage) {
				try {
					$randomSuffix = bin2hex(random_bytes(5));
				} catch (Throwable $randomException) {
					$randomSuffix = (string) mt_rand(10000, 99999);
				}

				$newImageName = 'vehicle-' . date('YmdHis') . '-' . $randomSuffix . '.' . $imageExtension;
				$newImageAbsolutePath = rtrim($uploadDirectory, '\\/') . DIRECTORY_SEPARATOR . $newImageName;
				$updatedImageRelativePath = 'uploads/vehicles/' . $newImageName;

				if (!move_uploaded_file($sourceImageTmpPath, $newImageAbsolutePath)) {
					throw new RuntimeException('Vehicle image upload failed.');
				}
			}

			$updateVehicleStmt = $pdo->prepare(
				'UPDATE vehicles
				 SET category_id = :category_id,
					 vehicle_type = :vehicle_type,
					 short_name = :short_name,
					 full_name = :full_name,
					 price_per_day = :price_per_day,
					 driver_age_requirement = :driver_age_requirement,
					 image_path = :image_path,
					 number_of_seats = :number_of_seats,
					 transmission_type = :transmission_type,
					 fuel_type = :fuel_type,
					 license_plate = :license_plate,
					 status = :status,
					 gps_id = :gps_id,
					 last_service_date = :last_service_date,
					 description = :description,
					 updated_at = CURRENT_TIMESTAMP
				 WHERE id = :id'
			);
			$updateVehicleStmt->execute([
				'category_id' => $categoryId,
				'vehicle_type' => $vehicleType,
				'short_name' => $shortName,
				'full_name' => $fullName,
				'price_per_day' => $pricePerDay,
				'driver_age_requirement' => $driverAgeRequirement,
				'image_path' => $updatedImageRelativePath,
				'number_of_seats' => $numberOfSeats,
				'transmission_type' => $transmissionType,
				'fuel_type' => $fuelType,
				'license_plate' => $licensePlate,
				'status' => $status,
				'gps_id' => $gpsId,
				'last_service_date' => $lastServiceDate,
				'description' => $description,
				'id' => $vehicleId,
			]);

			$pdo->commit();
			$syncVehicleDataAfterUpdate($pdo);

			if (
				$isReplacingImage
				&& $previousImageRelativePath !== ''
				&& $previousImageRelativePath !== $updatedImageRelativePath
				&& strpos($previousImageRelativePath, 'uploads/vehicles/') === 0
			) {
				$previousImageAbsolutePath = APP_ROOT . '/public/' . ltrim($previousImageRelativePath, '\\/');
				if (is_file($previousImageAbsolutePath)) {
					@unlink($previousImageAbsolutePath);
				}
			}

			$successRedirectQuery = [
				'page' => 'admin-manage-fleet',
			];
			if ($status === 'available') {
				$successRedirectQuery['fleet_mode'] = 'type';
				$successRedirectQuery['fleet_type'] = $vehicleType;
			} else {
				$successRedirectQuery['fleet_mode'] = 'status';
				$successRedirectQuery['fleet_status'] = $status;
			}
			$successRedirectQuery['open_read_vehicle_id'] = $vehicleId;

			header('Location: index.php?' . http_build_query($successRedirectQuery), true, 303);
			exit;
		} catch (Throwable $exception) {
			if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
				$pdo->rollBack();
			}

			if ($newImageAbsolutePath !== '' && is_file($newImageAbsolutePath)) {
				@unlink($newImageAbsolutePath);
			}

			error_log('Admin update vehicle failed: ' . $exception->getMessage());
		}

		header('Location: ' . $redirectUrl, true, 303);
		exit;
	}

// maintenance edit/fillup form: process start/update/complete lifecycle from fleet maintenance modals.
if ($isAdminStartMaintenancePost || $isAdminUpdateMaintenancePost || $isAdminCompleteMaintenancePost) {
	$sessionUser = $_SESSION['auth_user'] ?? [];
	$isAdminSession = is_array($sessionUser) && (($sessionUser['role'] ?? '') === 'admin');

	if (!$isAdminSession) {
		header('Location: index.php', true, 302);
		exit;
	}

	$allowedFleetModes = ['type', 'status'];
	$allowedFleetTypes = ['cars', 'bikes', 'luxury'];
	$allowedFleetStatuses = ['reserved', 'on_trip', 'overdue', 'maintenance'];

	$fleetMode = strtolower(trim((string) ($_POST['fleet_mode'] ?? 'status')));
	if (!in_array($fleetMode, $allowedFleetModes, true)) {
		$fleetMode = 'status';
	}

	$fleetType = strtolower(trim((string) ($_POST['fleet_type'] ?? 'cars')));
	if (!in_array($fleetType, $allowedFleetTypes, true)) {
		$fleetType = 'cars';
	}

	$fleetStatus = strtolower(trim((string) ($_POST['fleet_status'] ?? 'maintenance')));
	if (!in_array($fleetStatus, $allowedFleetStatuses, true)) {
		$fleetStatus = 'maintenance';
	}

	$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
	$issueDescription = trim((string) ($_POST['issue_description'] ?? ''));
	$workshopName = trim((string) ($_POST['workshop_name'] ?? ''));
	$estimateCompletionDate = trim((string) ($_POST['estimate_completion_date'] ?? ''));
	$serviceCost = (float) ($_POST['service_cost'] ?? 0);

	$redirectQuery = [
		'page' => 'admin-manage-fleet',
		'fleet_mode' => $fleetMode,
	];
	if ($fleetMode === 'status') {
		$redirectQuery['fleet_status'] = $fleetStatus;
	} else {
		$redirectQuery['fleet_type'] = $fleetType;
	}

	$redirectWithVehicle = static function (array $baseQuery, int $id): string {
		if ($id > 0) {
			$baseQuery['open_read_vehicle_id'] = $id;
		}

		return 'index.php?' . http_build_query($baseQuery);
	};

	$syncVehicleDataAfterMaintenance = static function (PDO $pdo) use (
		$mirrorVehicleJsonFiles,
		$legacyVehicleJsonSyncDir,
		$vehicleJsonSyncDir
	): void {
		try {
			$mirrorVehicleJsonFiles($legacyVehicleJsonSyncDir, $vehicleJsonSyncDir);
			sync_vehicles_json_bidirectional($pdo, $vehicleJsonSyncDir, [
				'prefer_json' => false,
				'prefer_db_timestamps' => true,
			]);
			$mirrorVehicleJsonFiles($vehicleJsonSyncDir, $legacyVehicleJsonSyncDir);
		} catch (Throwable $syncException) {
			error_log('Admin maintenance sync failed: ' . $syncException->getMessage());
		}
	};

	if ($vehicleId <= 0) {
		header('Location: ' . $redirectWithVehicle($redirectQuery, 0), true, 303);
		exit;
	}

	try {
		$pdo = db();
		$ensureMaintenanceRecordsTable($pdo);
		$pdo->beginTransaction();

		$vehicleLookupStmt = $pdo->prepare(
			'SELECT id, vehicle_type, status
			 FROM vehicles
			 WHERE id = :id
			 LIMIT 1
			 FOR UPDATE'
		);
		$vehicleLookupStmt->execute(['id' => $vehicleId]);
		$vehicleRow = $vehicleLookupStmt->fetch();

		if (!is_array($vehicleRow)) {
			$pdo->rollBack();
			header('Location: ' . $redirectWithVehicle($redirectQuery, 0), true, 303);
			exit;
		}

		$vehicleTypeValue = strtolower(trim((string) ($vehicleRow['vehicle_type'] ?? 'cars')));
		if (!in_array($vehicleTypeValue, $allowedFleetTypes, true)) {
			$vehicleTypeValue = 'cars';
		}

		if ($isAdminStartMaintenancePost || $isAdminUpdateMaintenancePost) {
			if ($issueDescription === '' || $workshopName === '' || $estimateCompletionDate === '') {
				$pdo->rollBack();
				$redirectQuery['fleet_mode'] = 'status';
				$redirectQuery['fleet_status'] = 'maintenance';
				unset($redirectQuery['fleet_type']);
				header('Location: ' . $redirectWithVehicle($redirectQuery, $vehicleId), true, 303);
				exit;
			}

			$estimateDateTime = DateTimeImmutable::createFromFormat('Y-m-d', $estimateCompletionDate)
				?: DateTimeImmutable::createFromFormat('d M, Y', $estimateCompletionDate);
			if (!$estimateDateTime instanceof DateTimeImmutable) {
				$pdo->rollBack();
				$redirectQuery['fleet_mode'] = 'status';
				$redirectQuery['fleet_status'] = 'maintenance';
				unset($redirectQuery['fleet_type']);
				header('Location: ' . $redirectWithVehicle($redirectQuery, $vehicleId), true, 303);
				exit;
			}

			$estimateCompletionDate = $estimateDateTime->format('Y-m-d');
			if (!is_finite($serviceCost) || $serviceCost < 0) {
				$serviceCost = 0;
			}
		}

		if ($isAdminStartMaintenancePost) {
			$closeOpenRecordsStmt = $pdo->prepare(
				'UPDATE vehicle_maintenance_records
				 SET status = "completed",
					 completed_at = CURRENT_TIMESTAMP,
					 updated_at = CURRENT_TIMESTAMP
				 WHERE vehicle_id = :vehicle_id AND status = "open"'
			);
			$closeOpenRecordsStmt->execute(['vehicle_id' => $vehicleId]);

			$insertMaintenanceStmt = $pdo->prepare(
				'INSERT INTO vehicle_maintenance_records
					(vehicle_id, issue_description, workshop_name, estimate_completion_date, service_cost, status)
				 VALUES
					(:vehicle_id, :issue_description, :workshop_name, :estimate_completion_date, :service_cost, "open")'
			);
			$insertMaintenanceStmt->execute([
				'vehicle_id' => $vehicleId,
				'issue_description' => $issueDescription,
				'workshop_name' => $workshopName,
				'estimate_completion_date' => $estimateCompletionDate,
				'service_cost' => number_format($serviceCost, 2, '.', ''),
			]);

			$markVehicleMaintenanceStmt = $pdo->prepare(
				'UPDATE vehicles
				 SET status = "maintenance"
				 WHERE id = :id'
			);
			$markVehicleMaintenanceStmt->execute(['id' => $vehicleId]);

			$pdo->commit();
			$syncVehicleDataAfterMaintenance($pdo);

			$redirectQuery = [
				'page' => 'admin-manage-fleet',
				'fleet_mode' => 'status',
				'fleet_status' => 'maintenance',
			];
			header('Location: ' . $redirectWithVehicle($redirectQuery, $vehicleId), true, 303);
			exit;
		}

		if ($isAdminUpdateMaintenancePost) {
			$openRecordLookupStmt = $pdo->prepare(
				'SELECT id
				 FROM vehicle_maintenance_records
				 WHERE vehicle_id = :vehicle_id AND status = "open"
				 ORDER BY id DESC
				 LIMIT 1
				 FOR UPDATE'
			);
			$openRecordLookupStmt->execute(['vehicle_id' => $vehicleId]);
			$openRecordId = (int) $openRecordLookupStmt->fetchColumn();

			if ($openRecordId > 0) {
				$updateRecordStmt = $pdo->prepare(
					'UPDATE vehicle_maintenance_records
					 SET issue_description = :issue_description,
						 workshop_name = :workshop_name,
						 estimate_completion_date = :estimate_completion_date,
						 service_cost = :service_cost,
						 updated_at = CURRENT_TIMESTAMP
					 WHERE id = :id'
				);
				$updateRecordStmt->execute([
					'issue_description' => $issueDescription,
					'workshop_name' => $workshopName,
					'estimate_completion_date' => $estimateCompletionDate,
					'service_cost' => number_format($serviceCost, 2, '.', ''),
					'id' => $openRecordId,
				]);
			} else {
				$insertRecordStmt = $pdo->prepare(
					'INSERT INTO vehicle_maintenance_records
						(vehicle_id, issue_description, workshop_name, estimate_completion_date, service_cost, status)
					 VALUES
						(:vehicle_id, :issue_description, :workshop_name, :estimate_completion_date, :service_cost, "open")'
				);
				$insertRecordStmt->execute([
					'vehicle_id' => $vehicleId,
					'issue_description' => $issueDescription,
					'workshop_name' => $workshopName,
					'estimate_completion_date' => $estimateCompletionDate,
					'service_cost' => number_format($serviceCost, 2, '.', ''),
				]);
			}

			$ensureVehicleMaintenanceStmt = $pdo->prepare(
				'UPDATE vehicles
				 SET status = "maintenance"
				 WHERE id = :id'
			);
			$ensureVehicleMaintenanceStmt->execute(['id' => $vehicleId]);

			$pdo->commit();
			$syncVehicleDataAfterMaintenance($pdo);

			$redirectQuery = [
				'page' => 'admin-manage-fleet',
				'fleet_mode' => 'status',
				'fleet_status' => 'maintenance',
			];
			header('Location: ' . $redirectWithVehicle($redirectQuery, $vehicleId), true, 303);
			exit;
		}

		$completeOpenRecordsStmt = $pdo->prepare(
			'UPDATE vehicle_maintenance_records
			 SET status = "completed",
				 completed_at = CURRENT_TIMESTAMP,
				 updated_at = CURRENT_TIMESTAMP
			 WHERE vehicle_id = :vehicle_id AND status = "open"'
		);
		$completeOpenRecordsStmt->execute([
			'vehicle_id' => $vehicleId,
		]);

		$markVehicleAvailableStmt = $pdo->prepare(
			'UPDATE vehicles
			 SET status = "available",
				 last_service_date = CURRENT_DATE
			 WHERE id = :id'
		);
		$markVehicleAvailableStmt->execute([
			'id' => $vehicleId,
		]);

		$pdo->commit();
		$syncVehicleDataAfterMaintenance($pdo);

		$redirectQuery = [
			'page' => 'admin-manage-fleet',
			'fleet_mode' => 'type',
			'fleet_type' => $vehicleTypeValue,
		];
		header('Location: ' . $redirectWithVehicle($redirectQuery, $vehicleId), true, 303);
		exit;
	} catch (Throwable $exception) {
		if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
			$pdo->rollBack();
		}
		error_log('Admin maintenance action failed: ' . $exception->getMessage());
	}

	header('Location: ' . $redirectWithVehicle($redirectQuery, $vehicleId), true, 303);
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
	$openReadVehicleId = (int) ($_GET['open_read_vehicle_id'] ?? 0);

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
		$ensureMaintenanceRecordsTable($pdo);

		$fleetBaseSelectSql = "
			SELECT
				v.id,
				v.short_name,
				v.full_name,
				v.image_path,
				v.price_per_day,
				v.status,
				v.vehicle_type,
				v.number_of_seats,
				v.transmission_type,
				v.fuel_type,
				v.driver_age_requirement,
				v.license_plate,
				v.gps_id,
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
				COALESCE(vehicle_stats.total_earnings, 0) AS total_earnings,
				maintenance_open.issue_description AS maintenance_issue_description,
				maintenance_open.workshop_name AS maintenance_workshop_name,
				maintenance_open.estimate_completion_date AS maintenance_estimate_completion_date,
				maintenance_open.service_cost AS maintenance_service_cost
			FROM vehicles v
			LEFT JOIN bookings latest_booking ON latest_booking.id = (
				SELECT b1.id
				FROM bookings b1
				WHERE b1.vehicle_id = v.id
					AND b1.status IN ('reserved', 'on_trip', 'overdue')
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
					AND b2.status IN ('reserved')
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
			LEFT JOIN vehicle_maintenance_records maintenance_open ON maintenance_open.id = (
				SELECT vmr1.id
				FROM vehicle_maintenance_records vmr1
				WHERE vmr1.vehicle_id = v.id
				ORDER BY
					CASE WHEN vmr1.status = 'open' THEN 0 ELSE 1 END ASC,
					COALESCE(vmr1.updated_at, vmr1.created_at) DESC,
					vmr1.id DESC
				LIMIT 1
			)
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
		'openReadVehicleId' => $openReadVehicleId,
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
