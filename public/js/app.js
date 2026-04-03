/**
 * Purpose: Shared frontend behaviors (nav interactions, modals, alerts, common utilities).
 * Website Section: Global Frontend.
 * Developer Notes: Implement nav toggle, flash auto-dismiss, CSRF header setup for AJAX, and shared helper functions.
 */

(function () {
	//menu: global header navigation modal behavior
	const menuModal = document.getElementById("menu-modal");
	if (!menuModal) {
		return;
	}

	const menuDialog = menuModal.querySelector(".menu-modal__dialog");
	const openButtons = Array.from(document.querySelectorAll("[data-menu-open]"));
	const closeButtons = Array.from(menuModal.querySelectorAll("[data-menu-close]"));
	const menuLinks = Array.from(menuModal.querySelectorAll(".menu-modal__link[href]"));

	if (!menuDialog || openButtons.length === 0) {
		return;
	}

	let lastFocusedElement = null;

	const setMenuState = (isOpen) => {
		menuModal.hidden = !isOpen;
		menuModal.setAttribute("aria-hidden", isOpen ? "false" : "true");
		menuModal.classList.toggle("is-open", isOpen);
		document.body.classList.toggle("menu-modal-open", isOpen);
	};

	const openMenu = () => {
		lastFocusedElement =
			document.activeElement instanceof HTMLElement ? document.activeElement : null;

		setMenuState(true);

		window.requestAnimationFrame(() => {
			const firstFocusable =
				menuModal.querySelector(".menu-modal__close") || menuDialog;
			if (firstFocusable instanceof HTMLElement) {
				firstFocusable.focus({ preventScroll: true });
			}
		});
	};

	const closeMenu = () => {
		if (!menuModal.classList.contains("is-open")) {
			return;
		}

		setMenuState(false);

		if (lastFocusedElement instanceof HTMLElement) {
			lastFocusedElement.focus({ preventScroll: true });
		}
	};

	openButtons.forEach((button) => {
		button.addEventListener("click", openMenu);
	});

	closeButtons.forEach((button) => {
		button.addEventListener("click", closeMenu);
	});

	menuLinks.forEach((link) => {
		link.addEventListener("click", closeMenu);
	});

	menuModal.addEventListener("click", (event) => {
		const target = event.target;
		if (!(target instanceof HTMLElement)) {
			return;
		}

		if (target.classList.contains("menu-modal__overlay")) {
			closeMenu();
		}
	});

	document.addEventListener("keydown", (event) => {
		if (event.key === "Escape") {
			closeMenu();
		}
	});
})();
