/**
 * Purpose: Shared frontend behaviors (nav interactions, modals, alerts, common utilities).
 * Website Section: Global Frontend.
 * Developer Notes: Implement nav toggle, flash auto-dismiss, CSRF header setup for AJAX, and shared helper functions.
 */

(function () {
	// admin login: stack-based modal navigation for menu/admin auth flow
	const modals = Array.from(document.querySelectorAll(".menu-modal[data-modal-id]"));
	if (modals.length === 0) {
		return;
	}

	const modalById = new Map();
	modals.forEach((modal) => {
		if (modal.id) {
			modalById.set(modal.id, modal);
		}
	});

	const menuOpenButtons = Array.from(document.querySelectorAll("[data-menu-open]"));
	const closeButtons = Array.from(document.querySelectorAll("[data-modal-close]"));
	const backButtons = Array.from(document.querySelectorAll("[data-modal-back]"));
	const targetButtons = Array.from(document.querySelectorAll("[data-modal-target]"));
	const navigationLinks = Array.from(document.querySelectorAll(".menu-modal__link[href]"));
	const passwordToggleButtons = Array.from(document.querySelectorAll("[data-password-toggle]"));
	const adminLoginModal = document.getElementById("admin-login-modal");
	const adminDeleteVehicleModal = document.getElementById("admin-delete-vehicle-modal");
	const adminDeleteVehicleIdInput = adminDeleteVehicleModal?.querySelector("[data-delete-vehicle-id-input]");
	const adminDeleteVehicleNameNode = adminDeleteVehicleModal?.querySelector("[data-delete-vehicle-name]");
	const adminDeleteFleetModeInput = adminDeleteVehicleModal?.querySelector("[data-delete-fleet-mode-input]");
	const adminDeleteFleetTypeInput = adminDeleteVehicleModal?.querySelector("[data-delete-fleet-type-input]");
	const adminDeleteFleetStatusInput = adminDeleteVehicleModal?.querySelector("[data-delete-fleet-status-input]");
	const adminLoginForm = adminLoginModal?.querySelector("[data-admin-login-form]");
	const adminLoginInputs = adminLoginModal
		? Array.from(adminLoginModal.querySelectorAll("[data-admin-login-input]"))
		: [];
	const adminPasswordInput = document.getElementById("admin-password-input");
	const adminPasswordToggle = adminLoginModal?.querySelector(
		"[data-password-toggle][data-password-target='admin-password-input']"
	);

	let lastFocusedElement = null;
	const modalHistory = [];

	const clearAdminLoginUiState = () => {
		if (adminLoginForm instanceof HTMLFormElement) {
			adminLoginForm.reset();
		}

		adminLoginInputs.forEach((input) => {
			if (input instanceof HTMLElement) {
				input.classList.remove("admin-login-form__input--error");
			}
		});

		const loginError = adminLoginModal?.querySelector("[data-admin-login-error]");
		if (loginError instanceof HTMLElement) {
			loginError.remove();
		}

		if (adminPasswordInput instanceof HTMLInputElement) {
			adminPasswordInput.type = "password";
		}

		if (adminPasswordToggle instanceof HTMLElement) {
			adminPasswordToggle.setAttribute("aria-label", "Show password");
			const icon = adminPasswordToggle.querySelector(".material-symbols-rounded");
			if (icon) {
				icon.textContent = "visibility";
			}
		}
	};

	// admin fleet delete: populate delete modal fields with the selected vehicle and active fleet filters.
	const hydrateDeleteVehicleModal = (triggerButton) => {
		if (!(triggerButton instanceof HTMLElement)) {
			return;
		}

		const vehicleId = Number.parseInt(
			triggerButton.getAttribute("data-delete-vehicle-id") || "0",
			10
		);
		const vehicleName =
			triggerButton.getAttribute("data-delete-vehicle-label") ||
			triggerButton.getAttribute("data-delete-vehicle-name") ||
			"this vehicle";
		const fleetMode = triggerButton.getAttribute("data-delete-fleet-mode") || "type";
		const fleetType = triggerButton.getAttribute("data-delete-fleet-type") || "cars";
		const fleetStatus =
			triggerButton.getAttribute("data-delete-fleet-status") || "reserved";

		if (adminDeleteVehicleIdInput instanceof HTMLInputElement) {
			adminDeleteVehicleIdInput.value =
				Number.isFinite(vehicleId) && vehicleId > 0 ? String(vehicleId) : "";
		}

		if (adminDeleteVehicleNameNode instanceof HTMLElement) {
			adminDeleteVehicleNameNode.textContent = vehicleName;
		}

		if (adminDeleteFleetModeInput instanceof HTMLInputElement) {
			adminDeleteFleetModeInput.value = fleetMode;
		}

		if (adminDeleteFleetTypeInput instanceof HTMLInputElement) {
			adminDeleteFleetTypeInput.value = fleetType;
		}

		if (adminDeleteFleetStatusInput instanceof HTMLInputElement) {
			adminDeleteFleetStatusInput.value = fleetStatus;
		}
	};

	const setModalState = (modal, isOpen) => {
		modal.hidden = !isOpen;
		modal.setAttribute("aria-hidden", isOpen ? "false" : "true");
		modal.classList.toggle("is-open", isOpen);
	};

	const focusFirstModalControl = (modal) => {
		window.requestAnimationFrame(() => {
			const focusTarget =
				modal.querySelector(".menu-modal__close") || modal.querySelector(".menu-modal__dialog");
			if (focusTarget instanceof HTMLElement) {
				focusTarget.focus({ preventScroll: true });
			}
		});
	};

	const showModal = (modalId, pushHistory = true) => {
		const targetModal = modalById.get(modalId);
		if (!targetModal) {
			return;
		}

		const activeModalId = modalHistory.length > 0 ? modalHistory[modalHistory.length - 1] : null;
		if (activeModalId) {
			const activeModal = modalById.get(activeModalId);
			if (activeModal) {
				setModalState(activeModal, false);
			}
		}

		setModalState(targetModal, true);
		document.body.classList.add("menu-modal-open");

		if (pushHistory) {
			modalHistory.push(modalId);
		}

		focusFirstModalControl(targetModal);
	};

	const closeAllModals = () => {
		modalHistory.length = 0;
		modalById.forEach((modal) => {
			setModalState(modal, false);
		});
		document.body.classList.remove("menu-modal-open");
		clearAdminLoginUiState();

		if (lastFocusedElement instanceof HTMLElement) {
			lastFocusedElement.focus({ preventScroll: true });
		}
	};

	const goBack = () => {
		if (modalHistory.length > 1) {
			const currentModalId = modalHistory.pop();
			const previousModalId = modalHistory[modalHistory.length - 1];
			const currentModal = modalById.get(currentModalId);
			const previousModal = modalById.get(previousModalId);

			if (currentModalId === "admin-login-modal") {
				clearAdminLoginUiState();
			}

			if (currentModal) {
				setModalState(currentModal, false);
			}

			if (previousModal) {
				setModalState(previousModal, true);
				focusFirstModalControl(previousModal);
			}

			return;
		}

		const hasSafeHistory =
			window.history.length > 1 &&
			typeof document.referrer === "string" &&
			document.referrer.startsWith(window.location.origin);

		closeAllModals();

		if (hasSafeHistory) {
			window.history.back();
		}
	};

	menuOpenButtons.forEach((button) => {
		button.addEventListener("click", () => {
			lastFocusedElement =
				document.activeElement instanceof HTMLElement ? document.activeElement : null;
			modalHistory.length = 0;
			showModal("menu-modal", true);
		});
	});

	targetButtons.forEach((button) => {
		button.addEventListener("click", () => {
			const targetId = button.getAttribute("data-modal-target");
			if (!targetId) {
				return;
			}

			if (targetId === "admin-delete-vehicle-modal") {
				hydrateDeleteVehicleModal(button);
			}

			if (modalHistory.length === 0) {
				lastFocusedElement =
					document.activeElement instanceof HTMLElement ? document.activeElement : null;
			}

			showModal(targetId, true);
		});
	});

	closeButtons.forEach((button) => {
		button.addEventListener("click", closeAllModals);
	});

	backButtons.forEach((button) => {
		button.addEventListener("click", goBack);
	});

	navigationLinks.forEach((link) => {
		link.addEventListener("click", () => {
			closeAllModals();
		});
	});

	passwordToggleButtons.forEach((button) => {
		button.addEventListener("click", () => {
			const targetId = button.getAttribute("data-password-target");
			if (!targetId) {
				return;
			}

			const targetInput = document.getElementById(targetId);
			if (!(targetInput instanceof HTMLInputElement)) {
				return;
			}

			const shouldShow = targetInput.type === "password";
			targetInput.type = shouldShow ? "text" : "password";
			button.setAttribute("aria-label", shouldShow ? "Hide password" : "Show password");

			const icon = button.querySelector(".material-symbols-rounded");
			if (icon) {
				icon.textContent = shouldShow ? "visibility_off" : "visibility";
			}
		});
	});

	document.addEventListener("click", (event) => {
		const target = event.target;
		if (!(target instanceof HTMLElement)) {
			return;
		}

		if (target.classList.contains("menu-modal__overlay")) {
			closeAllModals();
		}
	});

	document.addEventListener("keydown", (event) => {
		if (event.key === "Escape") {
			closeAllModals();
		}
	});

	const autoOpenAdminModal = document.getElementById("admin-login-modal")?.getAttribute("data-open-on-load") === "true";
	if (autoOpenAdminModal) {
		modalHistory.length = 0;
		showModal("menu-modal", true);
		showModal("admin-login-modal", true);
	} else {
		clearAdminLoginUiState();
	}

	window.addEventListener("pageshow", () => {
		if (!autoOpenAdminModal) {
			clearAdminLoginUiState();
		}
	});
})();
