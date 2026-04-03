<?php
/**
 * Purpose: Shared modal markup (login/register/forgot password, generic alerts).
*/

//menu: global menu modal (entry point to admin and primary navigation)
?>

<div class="menu-modal" id="menu-modal" hidden aria-hidden="true">
	<div class="menu-modal__overlay" data-menu-close></div>

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

			<button class="menu-modal__close" type="button" aria-label="Close menu" data-menu-close>
				<span class="material-symbols-rounded" aria-hidden="true">close</span>
			</button>
		</header>

		<nav class="menu-modal__nav" aria-label="Primary menu links">
			<a class="menu-modal__link" href="index.php">Home</a>
			<a class="menu-modal__link" href="index.php?page=vehicles&amp;vehicle_type=cars">Cars</a>
			<a class="menu-modal__link" href="index.php?page=vehicles&amp;vehicle_type=bikes">Bikes</a>
			<a class="menu-modal__link" href="index.php?page=vehicles&amp;vehicle_type=luxury">Luxury</a>
			<button class="menu-modal__link menu-modal__link--static" type="button" aria-disabled="true" disabled>Your Bookings</button>
			<button class="menu-modal__link menu-modal__link--static" type="button" aria-disabled="true" disabled>Log in as admin</button>
		</nav>

		<button class="menu-modal__back" type="button" aria-label="Close menu and return" data-menu-close>
			<span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
		</button>
	</section>
</div>
