<?php
/**
 * Purpose: Shared HTML <head> and top navigation bar for public pages.
 * Website Section: Global Layout.
 * Developer Notes: Renders brand logo and compact user/menu actions. Keep links and assets responsive.
 */
?>

<header class="site-header" role="banner">
	<div class="site-header__inner">
		<div class="site-header__brand" aria-label="Ridex brand">
			<img
				src="images/ridex-header.png"
				alt="Ridex logo"
				class="site-header__logo"
				onerror="this.onerror=null;this.src='images/logo.svg';"
			/>
		</div>
		<nav class="site-header__actions" aria-label="User navigation">
			<button class="icon-button" type="button" aria-label="User profile">
				<span class="material-symbols-rounded" aria-hidden="true">account_circle</span>
			</button>
			<button class="icon-button" type="button" aria-label="Open menu">
				<span class="material-symbols-rounded" aria-hidden="true">menu</span>
			</button>
		</nav>
	</div>
</header>
