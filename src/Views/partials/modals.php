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
