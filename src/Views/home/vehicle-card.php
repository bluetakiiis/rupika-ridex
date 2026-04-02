<?php
/**
 * Purpose: Partial for displaying a vehicle card with key info and CTA.
 * Website Section: Vehicle Catalog (reusable card in listings/home widgets).
 * Developer Notes: Show image, short name, price per day, badges (seats/transmission), status ribbon, and link to detail/booking.
 */

$vehicle = isset($vehicle) && is_array($vehicle) ? $vehicle : null;

if ($vehicle === null) {
	return;
}

$fullName = (string) ($vehicle['full_name'] ?? 'Vehicle');
$shortNameRaw = trim((string) ($vehicle['short_name'] ?? ''));
$shortName = $shortNameRaw !== '' ? $shortNameRaw : $fullName;
$imagePathRaw = trim((string) ($vehicle['image_path'] ?? ''));
$imagePath = $imagePathRaw !== '' ? $imagePathRaw : 'images/vehicle-feature.png';
$pricePerDay = number_format((float) ($vehicle['price_per_day'] ?? 0), 2);
$vehicleTypeRaw = strtolower(trim((string) ($vehicle['vehicle_type'] ?? 'cars')));
$allowedVehicleTypes = ['cars', 'bikes', 'luxury'];
$vehicleType = in_array($vehicleTypeRaw, $allowedVehicleTypes, true) ? $vehicleTypeRaw : 'cars';
$vehicleId = (int) ($vehicle['id'] ?? 0);

$detailQuery = [
	'page' => 'vehicle-detail',
	'vehicle_type' => $vehicleType,
];

if ($vehicleId > 0) {
	$detailQuery['id'] = $vehicleId;
}

$detailUrl = 'index.php?' . http_build_query($detailQuery);
?>

<article class="vehicle-card">
	<a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="vehicle-image-link" aria-label="View details for <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>">
		<img
			src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
			alt="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
			class="vehicle-image"
			onerror="this.onerror=null;this.src='images/vehicle-feature.png';"
		/>
	</a>
	<h3 class="vehicle-name"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></h3>
	<div class="vehicle-footer">
		<div class="vehicle-price">$<?= htmlspecialchars($pricePerDay, ENT_QUOTES, 'UTF-8') ?> <span class="day-text">/ day</span></div>
		<button class="book-button" type="button">Book Now</button>
	</div>
</article>
