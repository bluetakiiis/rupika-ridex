<?php
/**
 * Purpose: Admin live GPS tracking view for current vehicle locations.
 * Website Section: Admin GPS Tracking.
 * Developer Notes: Render map container, connect to live data feed/polling, show status legends, and allow vehicle selection.
 */

$adminUserName = isset($adminUserName) ? trim((string) $adminUserName) : 'Admin';
$liveTrackingKpis = isset($liveTrackingKpis) && is_array($liveTrackingKpis) ? $liveTrackingKpis : [];
$liveTrackingMarkers = isset($liveTrackingMarkers) && is_array($liveTrackingMarkers) ? $liveTrackingMarkers : [];
$liveTrackingGeneratedAt = isset($liveTrackingGeneratedAt) ? trim((string) $liveTrackingGeneratedAt) : '';

$formatInteger = static function ($value): string {
	if (!is_numeric($value)) {
		return 'Unavailable';
	}

	return number_format((int) round((float) $value));
};

$formatScore = static function ($value): string {
	if (!is_numeric($value)) {
		return 'Unavailable';
	}

	return number_format((float) $value, 0);
};

$normalizedMarkers = [];
foreach ($liveTrackingMarkers as $marker) {
	if (!is_array($marker)) {
		continue;
	}

	$leftPercent = is_numeric($marker['leftPercent'] ?? null) ? (float) $marker['leftPercent'] : 50.0;
	if ($leftPercent < 8) {
		$leftPercent = 8;
	} elseif ($leftPercent > 92) {
		$leftPercent = 92;
	}

	$topPercent = is_numeric($marker['topPercent'] ?? null) ? (float) $marker['topPercent'] : 50.0;
	if ($topPercent < 8) {
		$topPercent = 8;
	} elseif ($topPercent > 86) {
		$topPercent = 86;
	}

	$bookingNumber = trim((string) ($marker['bookingNumber'] ?? ''));
	if ($bookingNumber === '') {
		$bookingNumber = '#N/A';
	}

	$customerName = trim((string) ($marker['customerName'] ?? ''));
	if ($customerName === '') {
		$customerName = 'Unknown';
	}

	$customerPhone = trim((string) ($marker['customerPhone'] ?? ''));
	if ($customerPhone === '') {
		$customerPhone = 'Unavailable';
	}

	$location = 'Unavailable';

	$status = strtolower(trim((string) ($marker['status'] ?? 'reserved')));
	$hasGpsSignal = (bool) ($marker['hasGpsSignal'] ?? false);

	$normalizedMarkers[] = [
		'leftPercent' => $leftPercent,
		'topPercent' => $topPercent,
		'bookingNumber' => $bookingNumber,
		'customerName' => $customerName,
		'customerPhone' => $customerPhone,
		'location' => $location,
		'status' => $status,
		'hasGpsSignal' => $hasGpsSignal,
	];
}

$kpiTotalActive = $formatInteger($liveTrackingKpis['totalActive'] ?? null);
$kpiAverageSafetyScore = $formatScore($liveTrackingKpis['averageSafetyScore'] ?? null);
$kpiOverdueRiskPrediction = 'Unavailable';
$kpiActiveOverdue = $formatInteger($liveTrackingKpis['activeOverdue'] ?? null);

$generatedAtLabel = '';
if ($liveTrackingGeneratedAt !== '') {
	try {
		$generatedAtLabel = (new DateTimeImmutable($liveTrackingGeneratedAt))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('M d, Y h:i A');
	} catch (Throwable $exception) {
		$generatedAtLabel = '';
	}
}
?>

<section class="admin-live-tracking" aria-labelledby="admin-live-tracking-title">
	<div class="admin-dashboard__shell">
		<aside class="admin-sidebar" aria-label="Admin panel navigation">
			<nav class="admin-sidebar__nav" aria-label="Admin sections">
				<a class="admin-sidebar__link" href="index.php?page=admin-dashboard">Dashboard</a>
				<a class="admin-sidebar__link" href="index.php?page=admin-manage-fleet">Manage Fleet</a>
				<a class="admin-sidebar__link" href="index.php?page=admin-all-bookings">All Bookings</a>
				<a class="admin-sidebar__link is-active" href="index.php?page=admin-live-tracking" aria-current="page">Live Tracking</a>
			</nav>
		</aside>

		<div class="admin-dashboard__content admin-live-tracking__content">
			<h1 class="admin-dashboard__title" id="admin-live-tracking-title">Live Tracking</h1>

			<section class="admin-kpi-grid admin-live-tracking__kpi-grid" aria-label="Live tracking key metrics">
				<article class="admin-kpi-card">
					<header class="admin-kpi-card__header">
						<h2>Total Active</h2>
						<span class="material-symbols-rounded admin-kpi-card__icon admin-kpi-card__icon--up" aria-hidden="true">sensors</span>
					</header>
					<p class="admin-kpi-card__value"><?= htmlspecialchars($kpiTotalActive, ENT_QUOTES, 'UTF-8') ?></p>
				</article>

				<article class="admin-kpi-card">
					<header class="admin-kpi-card__header">
						<h2>Average Safety Score</h2>
						<span class="material-symbols-rounded admin-kpi-card__icon admin-kpi-card__icon--up" aria-hidden="true">speed</span>
					</header>
					<p class="admin-kpi-card__value"><?= htmlspecialchars($kpiAverageSafetyScore, ENT_QUOTES, 'UTF-8') ?></p>
				</article>

				<article class="admin-kpi-card">
					<header class="admin-kpi-card__header">
						<h2>Overdue Risk Prediction</h2>
						<span class="material-symbols-rounded admin-kpi-card__icon admin-kpi-card__icon--up" aria-hidden="true">warning</span>
					</header>
					<p class="admin-kpi-card__value"><?= htmlspecialchars($kpiOverdueRiskPrediction, ENT_QUOTES, 'UTF-8') ?></p>
				</article>

				<article class="admin-kpi-card">
					<header class="admin-kpi-card__header">
						<h2>Active Overdue</h2>
						<span class="material-symbols-rounded admin-kpi-card__icon admin-kpi-card__icon--down" aria-hidden="true">event_busy</span>
					</header>
					<p class="admin-kpi-card__value"><?= htmlspecialchars($kpiActiveOverdue, ENT_QUOTES, 'UTF-8') ?></p>
				</article>
			</section>

			<section class="admin-live-tracking__map-card" aria-label="Live tracking map">
				<div class="admin-live-tracking__map-header">
					<h2>Vehicle Locations</h2>
					<?php if ($generatedAtLabel !== ''): ?>
						<p>Updated <?= htmlspecialchars($generatedAtLabel, ENT_QUOTES, 'UTF-8') ?></p>
					<?php endif; ?>
				</div>

				<div class="admin-live-tracking__map-canvas" data-live-map-root>
					<div class="admin-live-tracking__map-viewport" data-live-map-viewport aria-label="Interactive tracking map">
						<div class="admin-live-tracking__map-stage" data-live-map-stage>
							<span class="admin-live-tracking__map-grid" aria-hidden="true"></span>

							<?php if (!empty($normalizedMarkers)): ?>
								<?php foreach ($normalizedMarkers as $marker): ?>
									<button
										type="button"
										class="admin-live-tracking__marker<?= $marker['hasGpsSignal'] ? '' : ' is-unavailable' ?>"
										style="left: <?= htmlspecialchars(number_format((float) $marker['leftPercent'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>%; top: <?= htmlspecialchars(number_format((float) $marker['topPercent'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>%;"
										aria-label="<?= htmlspecialchars($marker['bookingNumber'] . ' tracking marker', ENT_QUOTES, 'UTF-8') ?>"
									>
										<span class="material-symbols-rounded" aria-hidden="true">location_on</span>
										<span class="admin-live-tracking__marker-tooltip">
											<strong><?= htmlspecialchars($marker['bookingNumber'], ENT_QUOTES, 'UTF-8') ?></strong>
											<span><?= htmlspecialchars($marker['customerName'], ENT_QUOTES, 'UTF-8') ?></span>
											<span><?= htmlspecialchars($marker['customerPhone'], ENT_QUOTES, 'UTF-8') ?></span>
											<span><?= htmlspecialchars($marker['location'], ENT_QUOTES, 'UTF-8') ?></span>
										</span>
									</button>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="admin-live-tracking__map-empty">Unavailable</p>
							<?php endif; ?>
						</div>
					</div>

					<div class="admin-live-tracking__map-controls" role="group" aria-label="Map controls">
						<button type="button" class="admin-live-tracking__map-control" data-live-map-zoom-in aria-label="Zoom in">
							<span class="material-symbols-rounded" aria-hidden="true">add</span>
						</button>
						<button type="button" class="admin-live-tracking__map-control" data-live-map-zoom-out aria-label="Zoom out">
							<span class="material-symbols-rounded" aria-hidden="true">remove</span>
						</button>
						<button type="button" class="admin-live-tracking__map-control" data-live-map-reset aria-label="Reset map position">
							<span class="material-symbols-rounded" aria-hidden="true">my_location</span>
						</button>
					</div>

					<p class="admin-live-tracking__map-help">Drag to move map, use mouse wheel or +/- controls to zoom.</p>
				</div>
			</section>
		</div>
	</div>
</section>
