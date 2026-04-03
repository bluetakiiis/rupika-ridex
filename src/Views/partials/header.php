<?php
/**
 * Purpose: Shared HTML <head> and top navigation bar for public pages.
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
			<?php //menu: opens global navigation modal ?>
			<button class="icon-button" type="button" aria-label="Open menu" data-menu-open>
				<span class="material-symbols-rounded" aria-hidden="true">menu</span>
			</button>
		</nav>
	</div>
</header>
