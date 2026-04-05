<?php
/**
 * Purpose: Shared modal markup (login/register/forgot password, generic alerts).
*/

$adminLoginError = isset($adminLoginError) ? trim((string) $adminLoginError) : '';
$adminLoginEmail = isset($adminLoginEmail) ? trim((string) $adminLoginEmail) : '';
$adminLoginEmailInvalid = isset($adminLoginEmailInvalid) ? (bool) $adminLoginEmailInvalid : false;
$adminLoginPasswordInvalid = isset($adminLoginPasswordInvalid) ? (bool) $adminLoginPasswordInvalid : false;
$adminSessionUser = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])
	? $_SESSION['auth_user']
	: [];
$isAdminSession = (($adminSessionUser['role'] ?? '') === 'admin');
$adminProfileNameRaw = trim((string) ($adminSessionUser['name'] ?? ''));
$adminProfileName = trim((string) preg_replace('/\s*admin\s*$/i', '', $adminProfileNameRaw));
if ($adminProfileName === '') {
	$adminProfileName = 'Ridex';
}
$adminProfileEmail = trim((string) ($adminSessionUser['email'] ?? 'info@ridex.com'));
$adminProfilePhone = trim((string) ($adminSessionUser['phone'] ?? ''));
if ($adminProfilePhone === '') {
	$adminProfilePhone = '+977 9841222200';
}

//menu: global menu modal (entry point to admin and primary navigation)
?>

<div class="menu-modal" id="menu-modal" hidden aria-hidden="true" data-modal-id="menu-modal">
	<div class="menu-modal__overlay" data-modal-close></div>

	<section class="menu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="menu-modal-title">
		<header class="menu-modal__header">
			<div class="menu-modal__brand" id="menu-modal-title" aria-label="Ridex menu">
				<img
					src="images/ridex-header.png"
					alt="Ridex logo"
					class="menu-modal__logo"
					onerror="this.onerror=null;this.src='images/logo.svg';"
				/>
			</div>

			<button class="menu-modal__close" type="button" aria-label="Close menu" data-modal-close>
				<span class="material-symbols-rounded" aria-hidden="true">close</span>
			</button>
		</header>

		<nav class="menu-modal__nav" aria-label="Primary menu links">
			<a class="menu-modal__link" href="index.php">Home</a>
			<a class="menu-modal__link" href="index.php?page=vehicles&amp;vehicle_type=cars">Cars</a>
			<a class="menu-modal__link" href="index.php?page=vehicles&amp;vehicle_type=bikes">Bikes</a>
			<a class="menu-modal__link" href="index.php?page=vehicles&amp;vehicle_type=luxury">Luxury</a>
			<button class="menu-modal__link menu-modal__link--static" type="button" aria-disabled="true" disabled>Your Bookings</button>
			<button class="menu-modal__link menu-modal__link--admin" type="button" data-modal-target="admin-login-modal">Log in as admin</button>
		</nav>

		<button class="menu-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
			<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
		</button>
	</section>
</div>

<?php if ($isAdminSession): ?>
	<?php // admin login: admin profile modal for authenticated admins ?>
	<div class="menu-modal admin-profile-modal" id="admin-profile-modal" hidden aria-hidden="true" data-modal-id="admin-profile-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-profile-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-profile-title">
			<header class="menu-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex admin profile">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close profile" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-profile-modal__content">
				<h2 class="admin-profile-modal__title" id="admin-profile-title">Profile</h2>

				<div class="admin-profile-modal__identity">
					<span class="material-symbols-rounded admin-profile-modal__avatar" aria-hidden="true">account_circle</span>
					<p class="admin-profile-modal__name"><?= htmlspecialchars($adminProfileName, ENT_QUOTES, 'UTF-8') ?></p>
				</div>

				<dl class="admin-profile-modal__details">
					<div class="admin-profile-modal__detail-row">
						<dt>Email</dt>
						<dd><?= htmlspecialchars($adminProfileEmail, ENT_QUOTES, 'UTF-8') ?></dd>
					</div>
					<div class="admin-profile-modal__detail-row">
						<dt>Phone Number</dt>
						<dd><?= htmlspecialchars($adminProfilePhone, ENT_QUOTES, 'UTF-8') ?></dd>
					</div>
				</dl>

				<button class="admin-profile-modal__logout" type="button" data-modal-target="admin-logout-modal">Logout</button>
			</div>

			<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
				<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
			</button>
		</section>
	</div>

	<?php // admin login: logout confirmation modal ?>
	<div class="menu-modal admin-logout-modal" id="admin-logout-modal" hidden aria-hidden="true" data-modal-id="admin-logout-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-logout-title">
			<header class="menu-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex logout confirmation">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close logout prompt" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-logout-modal__content">
				<span class="material-symbols-rounded admin-logout-modal__icon" aria-hidden="true">delete</span>
				<p class="admin-logout-modal__text" id="admin-logout-title">Are you sure you want to logout? This action can’t be undone.</p>

				<div class="admin-logout-modal__actions">
					<button class="admin-logout-modal__cancel" type="button" data-modal-back>Cancel</button>
					<form class="admin-logout-modal__form" method="post" action="index.php">
						<input type="hidden" name="action" value="admin-logout" />
						<button class="admin-logout-modal__confirm" type="submit">Logout</button>
					</form>
				</div>
			</div>

			<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
				<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
			</button>
		</section>
	</div>

	<?php // read modal reference: C:\Users\User\Downloads\Ridex includes the 5-status read examples, and available status has an extra maintenance icon. ?>
	<div class="menu-modal admin-vehicle-read-modal" id="admin-vehicle-read-modal" hidden aria-hidden="true" data-modal-id="admin-vehicle-read-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-vehicle-read-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-vehicle-read-title">
			<header class="menu-modal__header admin-vehicle-read-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex vehicle details">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<?php // maintenance edit/fillup form: available-vehicle maintenance trigger icon opens maintenance fill modal. ?>
				<button
					class="admin-vehicle-read-modal__maintenance-indicator"
					type="button"
					aria-label="Open maintenance form"
					data-read-maintenance-indicator
					data-modal-target="admin-maintenance-fill-modal"
					hidden
				>
					<span class="material-symbols-rounded" aria-hidden="true">build</span>
				</button>
				<button class="menu-modal__close" type="button" aria-label="Close vehicle read prompt" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-vehicle-read-modal__content">
				<div class="admin-vehicle-read-modal__headline">
					<div class="admin-vehicle-read-modal__booking-block">
						<p class="admin-vehicle-read-modal__booking-number">Booking Number: <span data-read-booking-number>N/A</span></p>
						<p class="admin-vehicle-read-modal__current-user" data-read-current-user-line>Current User: <span data-read-current-user>N/A</span></p>
					</div>
					<span class="admin-vehicle-read-modal__status-pill admin-vehicle-read-modal__status-pill--available" data-read-status-pill>Available</span>
				</div>

				<div class="admin-vehicle-read-modal__hero">
					<div class="admin-vehicle-read-modal__image-wrap">
						<img src="images/vehicle-feature.png" alt="Vehicle" class="admin-vehicle-read-modal__image" data-read-vehicle-image onerror="this.onerror=null;this.src='images/vehicle-feature.png';" />
					</div>

					<div class="admin-vehicle-read-modal__meta">
						<h2 class="admin-vehicle-read-modal__type" id="admin-vehicle-read-title" data-read-vehicle-type>Car</h2>
						<p class="admin-vehicle-read-modal__full-name" data-read-vehicle-full-name>Vehicle</p>

						<ul class="admin-vehicle-read-modal__specs" aria-label="Vehicle specifications">
							<li><span class="material-symbols-rounded" aria-hidden="true">person</span><span data-read-vehicle-seats>0 Seats</span></li>
							<li><span class="material-symbols-rounded" aria-hidden="true">directions_car</span><span data-read-vehicle-transmission>N/A</span></li>
							<li><span class="material-symbols-rounded" aria-hidden="true">badge</span><span data-read-vehicle-age>0+ Years</span></li>
							<li><span class="material-symbols-rounded" aria-hidden="true">local_gas_station</span><span data-read-vehicle-fuel>Fuel</span></li>
							<li><span class="material-symbols-rounded" aria-hidden="true">id_card</span><span data-read-vehicle-plate>N/A</span></li>
						</ul>
					</div>
				</div>

				<table class="admin-vehicle-read-modal__table" aria-label="Vehicle status details">
					<tbody data-read-status-rows>
						<tr>
							<th scope="row">Last Service Date</th>
							<td>unavailable</td>
						</tr>
					</tbody>
				</table>

				<div class="admin-vehicle-read-modal__actions">
					<?php // maintenance edit/fillup form: read modal edit action routes to maintenance edit modal when vehicle is in maintenance. ?>
					<button class="admin-vehicle-read-modal__edit" type="button" data-read-edit-action>Edit</button>
					<button
						class="admin-vehicle-read-modal__delete"
						type="button"
						data-modal-target="admin-delete-vehicle-modal"
						data-read-delete-action
						hidden
					>
						Delete
					</button>
				</div>
			</div>

			<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
				<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
			</button>
		</section>
	</div>

	<?php // maintenance edit/fillup form: maintenance creation modal for transitioning an available vehicle to maintenance status. ?>
	<div class="menu-modal admin-maintenance-modal" id="admin-maintenance-fill-modal" hidden aria-hidden="true" data-modal-id="admin-maintenance-fill-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-maintenance-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-maintenance-fill-title">
			<header class="menu-modal__header admin-maintenance-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex maintenance form">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close maintenance form" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-maintenance-modal__content">
				<form class="admin-maintenance-modal__form" method="post" action="index.php" data-maintenance-fill-form>
					<input type="hidden" name="action" value="admin-start-maintenance" />
					<input type="hidden" name="vehicle_id" value="" data-maintenance-vehicle-id-input />
					<input type="hidden" name="fleet_mode" value="type" data-maintenance-fleet-mode-input />
					<input type="hidden" name="fleet_type" value="cars" data-maintenance-fleet-type-input />
					<input type="hidden" name="fleet_status" value="maintenance" data-maintenance-fleet-status-input />

					<label class="admin-maintenance-modal__label" for="maintenance-fill-issue">Issue Description</label>
					<input class="admin-maintenance-modal__input" type="text" id="maintenance-fill-issue" name="issue_description" maxlength="255" required data-maintenance-issue-input />

					<label class="admin-maintenance-modal__label" for="maintenance-fill-workshop">Workshop Name</label>
					<input class="admin-maintenance-modal__input" type="text" id="maintenance-fill-workshop" name="workshop_name" maxlength="150" required data-maintenance-workshop-input />

					<label class="admin-maintenance-modal__label" for="maintenance-fill-estimate">Est. Completion</label>
					<div class="admin-maintenance-modal__date-wrap">
						<input class="admin-maintenance-modal__input" type="text" id="maintenance-fill-estimate" name="estimate_completion_date" required inputmode="none" data-maintenance-estimate-input />
						<button class="booking-input__icon-button admin-maintenance-modal__date-button" type="button" aria-label="Open calendar" data-open-picker-for="maintenance-fill-estimate">
							<span class="material-symbols-rounded admin-maintenance-modal__date-icon" aria-hidden="true">calendar_month</span>
						</button>
					</div>

					<label class="admin-maintenance-modal__label" for="maintenance-fill-cost">Service Cost</label>
					<input class="admin-maintenance-modal__input" type="number" id="maintenance-fill-cost" name="service_cost" min="0" step="0.01" required data-maintenance-cost-input />

					<div class="admin-maintenance-modal__actions">
						<button class="admin-maintenance-modal__submit" type="submit">Update</button>
					</div>
				</form>
			</div>

			<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
				<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
			</button>
		</section>
	</div>

	<?php // maintenance edit/fillup form: maintenance edit modal with update and complete service actions. ?>
	<div class="menu-modal admin-maintenance-modal" id="admin-maintenance-edit-modal" hidden aria-hidden="true" data-modal-id="admin-maintenance-edit-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-maintenance-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-maintenance-edit-title">
			<header class="menu-modal__header admin-maintenance-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex maintenance edit form">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close maintenance edit form" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-maintenance-modal__content">
				<form class="admin-maintenance-modal__form" method="post" action="index.php" id="admin-maintenance-edit-form" data-maintenance-edit-form>
					<input type="hidden" name="action" value="admin-update-maintenance" />
					<input type="hidden" name="vehicle_id" value="" data-maintenance-edit-vehicle-id-input />
					<input type="hidden" name="fleet_mode" value="status" data-maintenance-edit-fleet-mode-input />
					<input type="hidden" name="fleet_type" value="cars" data-maintenance-edit-fleet-type-input />
					<input type="hidden" name="fleet_status" value="maintenance" data-maintenance-edit-fleet-status-input />

					<label class="admin-maintenance-modal__label" for="maintenance-edit-issue">Issue Description</label>
					<input class="admin-maintenance-modal__input" type="text" id="maintenance-edit-issue" name="issue_description" maxlength="255" required data-maintenance-edit-issue-input />

					<label class="admin-maintenance-modal__label" for="maintenance-edit-workshop">Workshop Name</label>
					<input class="admin-maintenance-modal__input" type="text" id="maintenance-edit-workshop" name="workshop_name" maxlength="150" required data-maintenance-edit-workshop-input />

					<label class="admin-maintenance-modal__label" for="maintenance-edit-estimate">Est. Completion</label>
					<div class="admin-maintenance-modal__date-wrap">
						<input class="admin-maintenance-modal__input" type="text" id="maintenance-edit-estimate" name="estimate_completion_date" required inputmode="none" data-maintenance-edit-estimate-input />
						<button class="booking-input__icon-button admin-maintenance-modal__date-button" type="button" aria-label="Open calendar" data-open-picker-for="maintenance-edit-estimate">
							<span class="material-symbols-rounded admin-maintenance-modal__date-icon" aria-hidden="true">calendar_month</span>
						</button>
					</div>

					<label class="admin-maintenance-modal__label" for="maintenance-edit-cost">Service Cost</label>
					<input class="admin-maintenance-modal__input" type="number" id="maintenance-edit-cost" name="service_cost" min="0" step="0.01" required data-maintenance-edit-cost-input />

				</form>

				<div class="admin-maintenance-modal__actions admin-maintenance-modal__actions--dual">
					<form id="admin-maintenance-complete-form" method="post" action="index.php" class="admin-maintenance-modal__complete-form">
						<input type="hidden" name="action" value="admin-complete-maintenance" />
						<input type="hidden" name="vehicle_id" value="" data-maintenance-complete-vehicle-id-input />
						<input type="hidden" name="fleet_mode" value="status" data-maintenance-complete-fleet-mode-input />
						<input type="hidden" name="fleet_type" value="cars" data-maintenance-complete-fleet-type-input />
						<input type="hidden" name="fleet_status" value="maintenance" data-maintenance-complete-fleet-status-input />
						<button class="admin-maintenance-modal__complete" type="submit">Complete Service</button>
					</form>
					<button class="admin-maintenance-modal__submit" type="submit" form="admin-maintenance-edit-form">Update</button>
				</div>
			</div>

			<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
				<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
			</button>
		</section>
	</div>

	<?php // create vehicle modal: three-part admin create flow (part 1/2/3) using existing modal system and required fields before submission. ?>
	<div class="menu-modal admin-create-vehicle-modal" id="admin-create-vehicle-modal" hidden aria-hidden="true" data-modal-id="admin-create-vehicle-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-create-vehicle-modal__dialog" role="dialog" aria-modal="true" aria-label="Create vehicle form">
			<header class="menu-modal__header admin-create-vehicle-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex create vehicle form">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close create vehicle form" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-create-vehicle-modal__content">
				<form class="admin-create-vehicle-modal__form" method="post" action="index.php" enctype="multipart/form-data" data-admin-create-vehicle-form>
					<input type="hidden" name="action" value="admin-create-vehicle" />
					<input type="hidden" name="fleet_mode" value="type" data-create-fleet-mode-input />
					<input type="hidden" name="fleet_type" value="cars" data-create-fleet-type-input />

					<section class="admin-create-vehicle-modal__step is-active" data-create-step-panel="1" aria-label="Create vehicle part 1">
						<label class="admin-create-vehicle-modal__label" for="create-vehicle-type">Vehicle Type</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="create-vehicle-type" name="vehicle_type" required data-create-vehicle-type-input>
							<option value="cars">Car</option>
							<option value="bikes">Bike</option>
							<option value="luxury">Luxury</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-full-name">Vehicle Full Name</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="create-vehicle-full-name" name="full_name" maxlength="150" required />

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-name">Vehicle Name</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="create-vehicle-name" name="short_name" maxlength="100" required />

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-price">Price Per Day</label>
						<input class="admin-create-vehicle-modal__input" type="number" id="create-vehicle-price" name="price_per_day" min="1" step="0.01" required />

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-driver-age">Driver's ID</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="create-vehicle-driver-age" name="driver_age_requirement" required>
							<option value="18">18+</option>
							<option value="21">21+</option>
						</select>

						<div class="admin-create-vehicle-modal__footer">
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Close create vehicle form" data-create-step-close>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
							</button>
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go to create vehicle part 2" data-create-step-next>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
							</button>
						</div>
					</section>

					<section class="admin-create-vehicle-modal__step" data-create-step-panel="2" aria-label="Create vehicle part 2" hidden>
						<label class="admin-create-vehicle-modal__label" for="create-vehicle-image">Upload Image</label>
						<div class="admin-create-vehicle-modal__file-wrap">
							<input class="admin-create-vehicle-modal__input admin-create-vehicle-modal__input--file-name" type="text" id="create-vehicle-image-name" readonly aria-hidden="true" tabindex="-1" />
							<button class="admin-create-vehicle-modal__file-clear" type="button" aria-label="Clear selected image" data-create-image-clear hidden>
								<span class="material-symbols-rounded" aria-hidden="true">close</span>
							</button>
							<input class="admin-create-vehicle-modal__file-input" type="file" id="create-vehicle-image" name="image_file" accept="image/png,image/jpeg,image/jpg,image/webp" required data-create-image-input />
						</div>

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-seats">Seats</label>
						<input class="admin-create-vehicle-modal__input" type="number" id="create-vehicle-seats" name="number_of_seats" min="1" max="12" required />

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-transmission">Transmission</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="create-vehicle-transmission" name="transmission_type" required>
							<option value="manual">Manual</option>
							<option value="automatic">Automatic</option>
							<option value="hybrid">Hybrid</option>
							<option value="N/A">N/A</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-fuel">Fuel Type</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="create-vehicle-fuel" name="fuel_type" required>
							<option value="petrol">Petrol</option>
							<option value="diesel">Diesel</option>
							<option value="electric">Electric</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-license">License Plate No.</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="create-vehicle-license" name="license_plate" maxlength="50" required />

						<div class="admin-create-vehicle-modal__footer">
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go back to create vehicle part 1" data-create-step-prev>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
							</button>
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go to create vehicle part 3" data-create-step-next>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
							</button>
						</div>
					</section>

					<section class="admin-create-vehicle-modal__step" data-create-step-panel="3" aria-label="Create vehicle part 3" hidden>
						<label class="admin-create-vehicle-modal__label" for="create-vehicle-gps-id">GPS ID</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="create-vehicle-gps-id" name="gps_id" maxlength="50" required />

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-status">Initial Vehicle Status</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="create-vehicle-status" name="status" required>
							<option value="available">Available</option>
							<option value="reserved">Reserved</option>
							<option value="on_trip">On Trip</option>
							<option value="overdue">Overdue</option>
							<option value="maintenance">Maintenance</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-last-service-date">Last Service Date</label>
						<div class="admin-create-vehicle-modal__date-wrap">
							<input class="admin-create-vehicle-modal__input" type="text" id="create-vehicle-last-service-date" name="last_service_date" required inputmode="none" data-create-last-service-input />
							<button class="booking-input__icon-button admin-create-vehicle-modal__date-button" type="button" aria-label="Open calendar" data-open-picker-for="create-vehicle-last-service-date">
								<span class="material-symbols-rounded admin-create-vehicle-modal__date-icon" aria-hidden="true">calendar_month</span>
							</button>
						</div>

						<label class="admin-create-vehicle-modal__label" for="create-vehicle-description">Vehicle Description (Detailed)</label>
						<textarea class="admin-create-vehicle-modal__textarea" id="create-vehicle-description" name="description" maxlength="2000" required></textarea>

						<div class="admin-create-vehicle-modal__footer admin-create-vehicle-modal__footer--submit">
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go back to create vehicle part 2" data-create-step-prev>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
							</button>
							<button class="admin-create-vehicle-modal__submit" type="submit">Create</button>
						</div>
					</section>
				</form>
			</div>
		</section>
	</div>

	<?php // edit vehicle modal: three-part admin edit flow matching create modal layout with prefilled vehicle data and update action. ?>
	<div class="menu-modal admin-edit-vehicle-modal" id="admin-edit-vehicle-modal" hidden aria-hidden="true" data-modal-id="admin-edit-vehicle-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-create-vehicle-modal__dialog" role="dialog" aria-modal="true" aria-label="Edit vehicle form">
			<header class="menu-modal__header admin-create-vehicle-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex edit vehicle form">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close edit vehicle form" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-create-vehicle-modal__content">
				<form class="admin-create-vehicle-modal__form" method="post" action="index.php" enctype="multipart/form-data" data-admin-edit-vehicle-form>
					<input type="hidden" name="action" value="admin-update-vehicle" />
					<input type="hidden" name="vehicle_id" value="" data-edit-vehicle-id-input />
					<input type="hidden" name="current_image_path" value="" data-edit-current-image-path-input />
					<input type="hidden" name="fleet_mode" value="type" data-edit-fleet-mode-input />
					<input type="hidden" name="fleet_type" value="cars" data-edit-fleet-type-input />
					<input type="hidden" name="fleet_status" value="reserved" data-edit-fleet-status-input />

					<section class="admin-create-vehicle-modal__step is-active" data-edit-step-panel="1" aria-label="Edit vehicle part 1">
						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-type">Vehicle Type</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="edit-vehicle-type" name="vehicle_type" required data-edit-vehicle-type-input>
							<option value="cars">Car</option>
							<option value="bikes">Bike</option>
							<option value="luxury">Luxury</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-full-name">Vehicle Full Name</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="edit-vehicle-full-name" name="full_name" maxlength="150" required data-edit-vehicle-full-name-input />

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-name">Vehicle Name</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="edit-vehicle-name" name="short_name" maxlength="100" required data-edit-vehicle-short-name-input />

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-price">Price Per Day</label>
						<input class="admin-create-vehicle-modal__input" type="number" id="edit-vehicle-price" name="price_per_day" min="1" step="0.01" required data-edit-vehicle-price-input />

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-driver-age">Driver's ID</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="edit-vehicle-driver-age" name="driver_age_requirement" required data-edit-vehicle-driver-age-input>
							<option value="18">18+</option>
							<option value="21">21+</option>
						</select>

						<div class="admin-create-vehicle-modal__footer">
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Close edit vehicle form" data-edit-step-close>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
							</button>
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go to edit vehicle part 2" data-edit-step-next>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
							</button>
						</div>
					</section>

					<section class="admin-create-vehicle-modal__step" data-edit-step-panel="2" aria-label="Edit vehicle part 2" hidden>
						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-image">Upload New Image</label>
						<div class="admin-create-vehicle-modal__file-wrap">
							<input class="admin-create-vehicle-modal__input admin-create-vehicle-modal__input--file-name" type="text" id="edit-vehicle-image-name" readonly aria-hidden="true" tabindex="-1" data-edit-image-name-input />
							<button class="admin-create-vehicle-modal__file-clear" type="button" aria-label="Clear selected image" data-edit-image-clear hidden>
								<span class="material-symbols-rounded" aria-hidden="true">close</span>
							</button>
							<input class="admin-create-vehicle-modal__file-input" type="file" id="edit-vehicle-image" name="image_file" accept="image/png,image/jpeg,image/jpg,image/webp" data-edit-image-input />
						</div>

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-seats">Seats</label>
						<input class="admin-create-vehicle-modal__input" type="number" id="edit-vehicle-seats" name="number_of_seats" min="1" max="12" required data-edit-vehicle-seats-input />

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-transmission">Transmission</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="edit-vehicle-transmission" name="transmission_type" required data-edit-vehicle-transmission-input>
							<option value="manual">Manual</option>
							<option value="automatic">Automatic</option>
							<option value="hybrid">Hybrid</option>
							<option value="N/A">N/A</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-fuel">Fuel Type</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="edit-vehicle-fuel" name="fuel_type" required data-edit-vehicle-fuel-input>
							<option value="petrol">Petrol</option>
							<option value="diesel">Diesel</option>
							<option value="electric">Electric</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-license">License Plate No.</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="edit-vehicle-license" name="license_plate" maxlength="50" required data-edit-vehicle-license-input />

						<div class="admin-create-vehicle-modal__footer">
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go back to edit vehicle part 1" data-edit-step-prev>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
							</button>
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go to edit vehicle part 3" data-edit-step-next>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
							</button>
						</div>
					</section>

					<section class="admin-create-vehicle-modal__step" data-edit-step-panel="3" aria-label="Edit vehicle part 3" hidden>
						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-gps-id">GPS ID</label>
						<input class="admin-create-vehicle-modal__input" type="text" id="edit-vehicle-gps-id" name="gps_id" maxlength="50" required data-edit-vehicle-gps-id-input />

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-status">Vehicle Status</label>
						<select class="admin-create-vehicle-modal__input admin-create-vehicle-modal__select" id="edit-vehicle-status" name="status" required data-edit-vehicle-status-input>
							<option value="available">Available</option>
							<option value="reserved">Reserved</option>
							<option value="on_trip">On Trip</option>
							<option value="overdue">Overdue</option>
							<option value="maintenance">Maintenance</option>
						</select>

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-last-service-date">Last Service Date</label>
						<div class="admin-create-vehicle-modal__date-wrap">
							<input class="admin-create-vehicle-modal__input" type="text" id="edit-vehicle-last-service-date" name="last_service_date" required inputmode="none" data-edit-last-service-input />
							<button class="booking-input__icon-button admin-create-vehicle-modal__date-button" type="button" aria-label="Open calendar" data-open-picker-for="edit-vehicle-last-service-date">
								<span class="material-symbols-rounded admin-create-vehicle-modal__date-icon" aria-hidden="true">calendar_month</span>
							</button>
						</div>

						<label class="admin-create-vehicle-modal__label" for="edit-vehicle-description">Vehicle Description (Detailed)</label>
						<textarea class="admin-create-vehicle-modal__textarea" id="edit-vehicle-description" name="description" maxlength="2000" required data-edit-vehicle-description-input></textarea>

						<div class="admin-create-vehicle-modal__footer admin-create-vehicle-modal__footer--submit">
							<button class="menu-modal__back admin-create-vehicle-modal__nav" type="button" aria-label="Go back to edit vehicle part 2" data-edit-step-prev>
								<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
							</button>
							<button class="admin-create-vehicle-modal__submit" type="submit">Update</button>
						</div>
					</section>
				</form>
			</div>
		</section>
	</div>

	<?php // admin fleet delete: vehicle delete confirmation modal (same sizing/layout as logout modal). ?>
	<div class="menu-modal admin-logout-modal" id="admin-delete-vehicle-modal" hidden aria-hidden="true" data-modal-id="admin-delete-vehicle-modal">
		<div class="menu-modal__overlay" data-modal-close></div>

		<section class="menu-modal__dialog admin-modal__dialog admin-logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-delete-vehicle-title">
			<header class="menu-modal__header">
				<div class="menu-modal__brand" aria-label="Ridex delete vehicle confirmation">
					<img
						src="images/ridex-header.png"
						alt="Ridex logo"
						class="menu-modal__logo"
						onerror="this.onerror=null;this.src='images/logo.svg';"
					/>
				</div>

				<button class="menu-modal__close" type="button" aria-label="Close delete vehicle prompt" data-modal-close>
					<span class="material-symbols-rounded" aria-hidden="true">close</span>
				</button>
			</header>

			<div class="admin-logout-modal__content">
				<span class="material-symbols-rounded admin-logout-modal__icon" aria-hidden="true">delete</span>
				<p class="admin-logout-modal__text" id="admin-delete-vehicle-title">
					Are you sure you want to delete <span data-delete-vehicle-name>this vehicle</span>? This action can’t be undone.
				</p>

				<div class="admin-logout-modal__actions">
					<button class="admin-logout-modal__cancel" type="button" data-modal-back>Cancel</button>
					<form class="admin-logout-modal__form" method="post" action="index.php">
						<input type="hidden" name="action" value="admin-delete-vehicle" />
						<input type="hidden" name="vehicle_id" value="" data-delete-vehicle-id-input />
						<input type="hidden" name="fleet_mode" value="type" data-delete-fleet-mode-input />
						<input type="hidden" name="fleet_type" value="cars" data-delete-fleet-type-input />
						<input type="hidden" name="fleet_status" value="reserved" data-delete-fleet-status-input />
						<button class="admin-logout-modal__confirm" type="submit">Delete</button>
					</form>
				</div>
			</div>

			<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
				<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
			</button>
		</section>
	</div>
<?php endif; ?>

<?php // admin login: admin authentication modal ?>
<div class="menu-modal admin-login-modal" id="admin-login-modal" hidden aria-hidden="true" data-modal-id="admin-login-modal" data-open-on-load="<?= $adminLoginError !== '' ? 'true' : 'false' ?>">
	<div class="menu-modal__overlay" data-modal-close></div>

	<section class="menu-modal__dialog admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-login-title">
		<header class="menu-modal__header">
			<div class="menu-modal__brand" aria-label="Ridex admin login">
				<img
					src="images/ridex-header.png"
					alt="Ridex logo"
					class="menu-modal__logo"
					onerror="this.onerror=null;this.src='images/logo.svg';"
				/>
			</div>

			<button class="menu-modal__close" type="button" aria-label="Close admin login" data-modal-close>
				<span class="material-symbols-rounded" aria-hidden="true">close</span>
			</button>
		</header>

		<div class="admin-login-modal__content">
			<h2 class="admin-login-modal__title" id="admin-login-title">Log In</h2>

			<form class="admin-login-form" method="post" action="index.php" autocomplete="off" data-admin-login-form>
				<input type="hidden" name="action" value="admin-login" />

				<label class="admin-login-form__label" for="admin-email-input">Administrative Email</label>
				<input
					class="admin-login-form__input<?= $adminLoginEmailInvalid ? ' admin-login-form__input--error' : '' ?>"
					type="email"
					id="admin-email-input"
					name="admin_email"
					placeholder="info@ridex.com"
					autocomplete="off"
					spellcheck="false"
					data-admin-login-input
					required
					value="<?= htmlspecialchars($adminLoginEmail, ENT_QUOTES, 'UTF-8') ?>"
				/>

				<label class="admin-login-form__label" for="admin-password-input">Password</label>
				<div class="admin-login-form__password-wrap">
					<input
						class="admin-login-form__input<?= $adminLoginPasswordInvalid ? ' admin-login-form__input--error' : '' ?>"
						type="password"
						id="admin-password-input"
						name="admin_password"
						placeholder="Enter your password"
						autocomplete="off"
						data-admin-login-input
						required
						minlength="8"
					/>
					<button
						class="admin-login-form__password-toggle"
						type="button"
						aria-label="Show password"
						data-password-toggle
						data-password-target="admin-password-input"
					>
						<span class="material-symbols-rounded" aria-hidden="true">visibility</span>
					</button>
				</div>

				<button class="admin-login-form__forgot" type="button" data-modal-target="admin-reset-modal">Forgot your password?</button>

				<?php if ($adminLoginError !== ''): ?>
					<p class="admin-login-form__error" role="alert" data-admin-login-error><?= htmlspecialchars($adminLoginError, ENT_QUOTES, 'UTF-8') ?></p>
				<?php endif; ?>

				<button class="admin-login-form__submit" type="submit">Log In</button>
			</form>
		</div>

		<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
			<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
		</button>
	</section>
</div>

<?php // admin login: forgot-password confirmation modal ?>
<div class="menu-modal admin-reset-modal" id="admin-reset-modal" hidden aria-hidden="true" data-modal-id="admin-reset-modal">
	<div class="menu-modal__overlay" data-modal-close></div>

	<section class="menu-modal__dialog admin-modal__dialog admin-reset-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admin-reset-title">
		<header class="menu-modal__header">
			<div class="menu-modal__brand" aria-label="Ridex password reset">
				<img
					src="images/ridex-header.png"
					alt="Ridex logo"
					class="menu-modal__logo"
					onerror="this.onerror=null;this.src='images/logo.svg';"
				/>
			</div>

			<button class="menu-modal__close" type="button" aria-label="Close reset prompt" data-modal-close>
				<span class="material-symbols-rounded" aria-hidden="true">close</span>
			</button>
		</header>

		<div class="admin-reset-modal__content">
			<span class="material-symbols-rounded admin-reset-modal__icon" aria-hidden="true">mail</span>
			<h2 class="admin-reset-modal__title" id="admin-reset-title">Check your inbox</h2>
			<p class="admin-reset-modal__text">
				You should receive an email in the next few minutes with a link to reset your password.
			</p>
			<a class="admin-reset-modal__submit" href="index.php">Reset Password</a>
		</div>

		<button class="menu-modal__back admin-modal__back" type="button" aria-label="Back to previous view" data-modal-back>
			<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
		</button>
	</section>
</div>
