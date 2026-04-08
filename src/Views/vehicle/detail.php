<?php
/**
 * Purpose: Vehicle detail page presenting full specs, gallery, pricing, and booking CTA.
 * Website Section: Vehicle Catalog.
 * Developer Notes: Display vehicle attributes (seats, transmission, fuel), current status badge, pricing per day, and a booking call-to-action.
 */

$vehicle = isset($vehicle) && is_array($vehicle) ? $vehicle : null;
$backUrl = (string) ($backUrl ?? 'index.php?page=vehicles');

if ($vehicle !== null) {
	$fullNameRaw = trim((string) ($vehicle['full_name'] ?? ''));
	$shortNameRaw = trim((string) ($vehicle['short_name'] ?? ''));
	$vehicleName = $shortNameRaw !== '' ? $shortNameRaw : ($fullNameRaw !== '' ? $fullNameRaw : 'Vehicle');
	$descriptionRaw = trim((string) ($vehicle['description'] ?? ''));
	$vehicleDescription = $descriptionRaw !== ''
		? $descriptionRaw
		: 'This vehicle is ready to deliver a smooth and safe ride with dependable performance and comfort.';
	$imagePathRaw = trim((string) ($vehicle['image_path'] ?? ''));
	$imagePath = $imagePathRaw !== '' ? $imagePathRaw : 'images/vehicle-feature.png';
	$pricePerDay = number_format((float) ($vehicle['price_per_day'] ?? 0), 2);
	$seatCount = (int) ($vehicle['number_of_seats'] ?? 0);
	$seatLabel = $seatCount > 0 ? $seatCount . ' Seats' : 'Seats N/A';
	$transmissionRaw = trim((string) ($vehicle['transmission_type'] ?? 'N/A'));
	$transmissionLabel = $transmissionRaw !== '' ? strtoupper($transmissionRaw) : 'N/A';
	$ageRequirement = (int) ($vehicle['driver_age_requirement'] ?? 0);
	$ageLabel = $ageRequirement > 0 ? $ageRequirement . '+ Years' : 'Age N/A';
	$fuelRaw = trim((string) ($vehicle['fuel_type'] ?? 'N/A'));
	$fuelLabel = $fuelRaw !== '' ? ucfirst($fuelRaw) : 'N/A';
	$plateRaw = trim((string) ($vehicle['license_plate'] ?? ''));
	$licensePlateLabel = $plateRaw !== '' ? strtoupper($plateRaw) : 'Plate N/A';
}

?>


<section class="vehicle-detail-page" aria-label="Vehicle detail">
	<div class="vehicle-detail-top">
		<a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="vehicle-detail-back" aria-label="Back to vehicles">
			<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
		</a>
	</div>

	<?php if ($vehicle === null): ?>
		<p class="vehicle-results-empty">Vehicle not found.</p>
	<?php else: ?>
		<div class="vehicle-detail-hero">
			<img
				src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
				alt="<?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?>"
				class="vehicle-detail-image"
				onerror="this.onerror=null;this.src='images/vehicle-feature.png';"
			/>
		</div>

		<div class="vehicle-detail-header-row">
			<div class="vehicle-detail-title-block">
				<h1 class="vehicle-detail-title"><?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?></h1>
				<ul class="vehicle-detail-meta" aria-label="Vehicle specifications">
					<li>
						<span class="material-symbols-rounded" aria-hidden="true">airline_seat_recline_normal</span>
						<span><?= htmlspecialchars($seatLabel, ENT_QUOTES, 'UTF-8') ?></span>
					</li>
					<li>
						<span class="material-symbols-rounded" aria-hidden="true">settings</span>
						<span><?= htmlspecialchars($transmissionLabel, ENT_QUOTES, 'UTF-8') ?></span>
					</li>
					<li>
						<span class="material-symbols-rounded" aria-hidden="true">person</span>
						<span><?= htmlspecialchars($ageLabel, ENT_QUOTES, 'UTF-8') ?></span>
					</li>
					<li>
						<span class="material-symbols-rounded" aria-hidden="true">local_gas_station</span>
						<span><?= htmlspecialchars($fuelLabel, ENT_QUOTES, 'UTF-8') ?></span>
					</li>
					<li>
						<span class="material-symbols-rounded" aria-hidden="true">badge</span>
						<span><?= htmlspecialchars($licensePlateLabel, ENT_QUOTES, 'UTF-8') ?></span>
					</li>
				</ul>
			</div>

			<div class="vehicle-detail-cta" aria-label="Pricing and booking">
				<div class="vehicle-price">$<?= htmlspecialchars($pricePerDay, ENT_QUOTES, 'UTF-8') ?> <span class="day-text">/ day</span></div>
				<button
					class="book-button"
					type="button"
					data-book-now-trigger
					data-modal-target="user-booking-confirm-modal"
					data-book-vehicle-id="<?= htmlspecialchars((string) ((int) ($vehicle['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"
					data-book-vehicle-type="<?= htmlspecialchars($backVehicleType, ENT_QUOTES, 'UTF-8') ?>"
					data-book-pickup-location="<?= htmlspecialchars($pickupLocation, ENT_QUOTES, 'UTF-8') ?>"
					data-book-return-location="<?= htmlspecialchars($returnLocation, ENT_QUOTES, 'UTF-8') ?>"
					data-book-pickup-date="<?= htmlspecialchars($pickupDate, ENT_QUOTES, 'UTF-8') ?>"
					data-book-return-date="<?= htmlspecialchars($returnDate, ENT_QUOTES, 'UTF-8') ?>"
					data-book-pickup-time="<?= htmlspecialchars($pickupTime, ENT_QUOTES, 'UTF-8') ?>"
					data-book-return-time="<?= htmlspecialchars($returnTime, ENT_QUOTES, 'UTF-8') ?>"
				>
					Book Now
				</button>
			</div>
		</div>

		<div class="vehicle-detail-content-row">
			<ul class="vehicle-detail-benefits" aria-label="Vehicle benefits">
				<li>
					<span class="material-symbols-rounded" aria-hidden="true">check</span>
					<span>Unlimited mileage</span>
				</li>
				<li>
					<span class="material-symbols-rounded" aria-hidden="true">check</span>
					<span>No deposit</span>
				</li>
				<li>
					<span class="material-symbols-rounded" aria-hidden="true">check</span>
					<span>Free cancellation</span>
				</li>
			</ul>

			<p class="vehicle-detail-description"><?= nl2br(htmlspecialchars($vehicleDescription, ENT_QUOTES, 'UTF-8')) ?></p>
		</div>
	<?php endif; ?>
</section>
