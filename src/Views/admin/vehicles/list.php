<?php
/**
 * Purpose: Admin fleet list view with filters and bulk actions.
 * Website Section: Admin Fleet Management.
 * Developer Notes: Display table of vehicles with status, category, price, actions to edit/detail, and filters for status/category.
 */

// manage fleet: expected page state from route-level DB query.

$fleetMode = isset($fleetMode) ? trim((string) $fleetMode) : 'type';
$selectedFleetType = isset($selectedFleetType) ? trim((string) $selectedFleetType) : 'cars';
$selectedFleetStatus = isset($selectedFleetStatus) ? trim((string) $selectedFleetStatus) : 'reserved';
$fleetVehicles = isset($fleetVehicles) && is_array($fleetVehicles) ? $fleetVehicles : [];

$typeTabs = [
	'cars' => 'Cars',
	'bikes' => 'Bikes',
	'luxury' => 'Luxury',
];

$statusTabs = [
	'reserved' => 'Reserved',
	'on_trip' => 'On Trip',
	'overdue' => 'Overdue',
	'maintenance' => 'Maintenance',
];

$buildFleetUrl = static function (string $mode, string $selected): string {
	$query = ['page' => 'admin-manage-fleet', 'fleet_mode' => $mode];
	if ($mode === 'status') {
		$query['fleet_status'] = $selected;
	} else {
		$query['fleet_type'] = $selected;
	}

	return 'index.php?' . http_build_query($query);
};

$resolveVehicleName = static function (array $vehicle): string {
	$shortName = trim((string) ($vehicle['short_name'] ?? ''));
	if ($shortName !== '') {
		return $shortName;
	}

	$fullName = trim((string) ($vehicle['full_name'] ?? ''));
	return $fullName !== '' ? $fullName : 'Vehicle';
};

$resolveVehicleImage = static function (array $vehicle): string {
	$imagePath = trim((string) ($vehicle['image_path'] ?? ''));
	if ($imagePath === '') {
		return 'images/vehicle-feature.png';
	}

	return $imagePath;
};

$fleetEmptyMessage = 'No available vehicles.';
if ($fleetMode === 'status') {
	$statusLabel = $statusTabs[$selectedFleetStatus] ?? 'Selected';
	$fleetEmptyMessage = 'No ' . strtolower($statusLabel) . ' vehicles.';
}

?>


<?php // manage fleet: primary page layout. ?>
<section class="admin-fleet" aria-labelledby="admin-fleet-title">
	<div class="admin-dashboard__shell">
		<aside class="admin-sidebar" aria-label="Admin panel navigation">
			<nav class="admin-sidebar__nav" aria-label="Admin sections">
				<a class="admin-sidebar__link" href="index.php?page=admin-dashboard">Dashboard</a>
				<a class="admin-sidebar__link is-active" href="index.php?page=admin-manage-fleet" aria-current="page">Manage Fleet</a>
				<span class="admin-sidebar__link is-disabled" aria-disabled="true">All Bookings</span>
				<span class="admin-sidebar__link is-disabled" aria-disabled="true">Live Tracking</span>
			</nav>
		</aside>

		<div class="admin-dashboard__content admin-fleet__content">
			<h1 class="admin-fleet__title" id="admin-fleet-title">Manage Fleet</h1>

			<?php // manage fleet: top filter/action controls. ?>
			<div class="admin-fleet__toolbar">
				<div class="admin-fleet__filters" role="tablist" aria-label="Manage fleet filters">
					<?php if ($fleetMode === 'status'): ?>
						<?php foreach ($statusTabs as $statusValue => $statusLabel): ?>
							<?php
							$isActiveStatus = ($selectedFleetStatus === $statusValue);
							$statusClass = 'admin-fleet__pill admin-fleet__pill--status-' . str_replace('_', '-', $statusValue);
							if ($isActiveStatus) {
								$statusClass .= ' is-active';
							}
							?>
							<a class="<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($buildFleetUrl('status', $statusValue), ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-selected="<?= $isActiveStatus ? 'true' : 'false' ?>">
								<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
							</a>
						<?php endforeach; ?>
					<?php else: ?>
						<?php foreach ($typeTabs as $typeValue => $typeLabel): ?>
							<?php $isActiveType = ($selectedFleetType === $typeValue); ?>
							<a class="admin-fleet__pill admin-fleet__pill--type<?= $isActiveType ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildFleetUrl('type', $typeValue), ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-selected="<?= $isActiveType ? 'true' : 'false' ?>">
								<?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<?php if ($fleetMode !== 'status'): ?>
					<div class="admin-fleet__actions">
						<a class="admin-fleet__pill admin-fleet__pill--outline-red" href="<?= htmlspecialchars($buildFleetUrl('status', 'reserved'), ENT_QUOTES, 'UTF-8') ?>">Unavailable</a>
						<a class="admin-fleet__pill admin-fleet__pill--fill-green" href="index.php?page=admin-manage-fleet">Create</a>
					</div>
				<?php endif; ?>
			</div>

			<?php // manage fleet: vehicle cards sourced from DB. ?>
			<div class="admin-fleet__grid">
				<?php foreach ($fleetVehicles as $vehicle): ?>
					<?php
					$vehicleName = $resolveVehicleName($vehicle);
					$vehicleImage = $resolveVehicleImage($vehicle);
					?>
					<article class="admin-fleet-card">
						<div class="admin-fleet-card__image-wrap">
							<img
								src="<?= htmlspecialchars($vehicleImage, ENT_QUOTES, 'UTF-8') ?>"
								alt="<?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?>"
								class="admin-fleet-card__image"
								onerror="this.onerror=null;this.src='images/vehicle-feature.png';"
							/>
						</div>

						<h2 class="admin-fleet-card__name"><?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?></h2>

						<?php // manage fleet delete: opens delete confirmation modal with selected vehicle context. ?>
						<div class="admin-fleet-card__actions<?= $fleetMode === 'status' ? ' admin-fleet-card__actions--single' : '' ?>">
							<a class="admin-fleet-card__btn admin-fleet-card__btn--edit" href="index.php?page=admin-manage-fleet">Edit</a>
							<?php if ($fleetMode !== 'status'): ?>
								<button
									class="admin-fleet-card__btn admin-fleet-card__btn--delete"
									type="button"
									data-modal-target="admin-delete-vehicle-modal"
									data-delete-vehicle-trigger
									data-delete-vehicle-id="<?= (int) ($vehicle['id'] ?? 0) ?>"
									data-delete-vehicle-label="<?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?>"
									data-delete-fleet-mode="<?= htmlspecialchars($fleetMode, ENT_QUOTES, 'UTF-8') ?>"
									data-delete-fleet-type="<?= htmlspecialchars($selectedFleetType, ENT_QUOTES, 'UTF-8') ?>"
									data-delete-fleet-status="<?= htmlspecialchars($selectedFleetStatus, ENT_QUOTES, 'UTF-8') ?>"
								>
									Delete
								</button>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>

				<?php if (empty($fleetVehicles)): ?>
					<p class="admin-fleet__empty"><?= htmlspecialchars($fleetEmptyMessage, ENT_QUOTES, 'UTF-8') ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
