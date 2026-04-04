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
	const adminVehicleReadModal = document.getElementById("admin-vehicle-read-modal");
	const adminDeleteVehicleModal = document.getElementById("admin-delete-vehicle-modal");
	const adminDeleteVehicleIdInput = adminDeleteVehicleModal?.querySelector("[data-delete-vehicle-id-input]");
	const adminDeleteVehicleNameNode = adminDeleteVehicleModal?.querySelector("[data-delete-vehicle-name]");
	const adminDeleteFleetModeInput = adminDeleteVehicleModal?.querySelector("[data-delete-fleet-mode-input]");
	const adminDeleteFleetTypeInput = adminDeleteVehicleModal?.querySelector("[data-delete-fleet-type-input]");
	const adminDeleteFleetStatusInput = adminDeleteVehicleModal?.querySelector("[data-delete-fleet-status-input]");
	const adminReadBookingNumberNode = adminVehicleReadModal?.querySelector("[data-read-booking-number]");
	const adminReadCurrentUserLine = adminVehicleReadModal?.querySelector("[data-read-current-user-line]");
	const adminReadCurrentUserNode = adminVehicleReadModal?.querySelector("[data-read-current-user]");
	const adminReadStatusPill = adminVehicleReadModal?.querySelector("[data-read-status-pill]");
	const adminReadMaintenanceIndicator = adminVehicleReadModal?.querySelector("[data-read-maintenance-indicator]");
	const adminReadVehicleImage = adminVehicleReadModal?.querySelector("[data-read-vehicle-image]");
	const adminReadVehicleTypeNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-type]");
	const adminReadVehicleFullNameNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-full-name]");
	const adminReadVehicleSeatsNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-seats]");
	const adminReadVehicleTransmissionNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-transmission]");
	const adminReadVehicleAgeNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-age]");
	const adminReadVehicleFuelNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-fuel]");
	const adminReadVehiclePlateNode = adminVehicleReadModal?.querySelector("[data-read-vehicle-plate]");
	const adminReadStatusRowsNode = adminVehicleReadModal?.querySelector("[data-read-status-rows]");
	const adminReadDeleteAction = adminVehicleReadModal?.querySelector("[data-read-delete-action]");
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

	// read modal reference: C:\Users\User\Downloads\Ridex includes the 5-status read examples, and available status has an extra maintenance icon.
	const readStatusLabels = {
		available: "Available",
		reserved: "Reserved",
		on_trip: "On Trip",
		overdue: "Overdue",
		maintenance: "Maintenance",
	};

	const readUnavailableText = "unavailable";
	const readUserNotApplicableText = "N/A";
	const readUserStatuses = ["reserved", "on_trip", "overdue"];

	const toTitleCase = (rawValue) =>
		String(rawValue || "")
			.replace(/[_-]+/g, " ")
			.replace(/\b\w/g, (character) => character.toUpperCase());

	const isReadValueMissing = (rawValue) =>
		rawValue === null || rawValue === undefined || String(rawValue).trim() === "";

	const getReadMissingFallback = (statusKey, isUserRelated = false) => {
		if (isUserRelated && (statusKey === "available" || statusKey === "maintenance")) {
			return readUserNotApplicableText;
		}

		return readUnavailableText;
	};

	const normalizeReadValue = (rawValue, statusKey, isUserRelated = false) => {
		if (isReadValueMissing(rawValue)) {
			return getReadMissingFallback(statusKey, isUserRelated);
		}

		return String(rawValue).trim();
	};

	const formatReadDate = (rawValue, statusKey, isUserRelated = false) => {
		if (isReadValueMissing(rawValue)) {
			return getReadMissingFallback(statusKey, isUserRelated);
		}

		const text = String(rawValue).trim();

		const parsedDate = new Date(text.replace(" ", "T"));
		if (Number.isNaN(parsedDate.getTime())) {
			return getReadMissingFallback(statusKey, isUserRelated);
		}

		const day = String(parsedDate.getDate()).padStart(2, "0");
		const month = parsedDate.toLocaleString("en-US", { month: "short" });
		const year = parsedDate.getFullYear();

		return `${day} ${month}, ${year}`;
	};

	const formatReadCurrency = (rawValue, statusKey, isUserRelated = false) => {
		if (isReadValueMissing(rawValue)) {
			return getReadMissingFallback(statusKey, isUserRelated);
		}

		const numericValue = Number.parseFloat(String(rawValue).trim());
		if (!Number.isFinite(numericValue)) {
			return getReadMissingFallback(statusKey, isUserRelated);
		}

		return `$${numericValue.toFixed(2)}`;
	};

	const escapeHtml = (rawValue) =>
		String(rawValue || "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#39;");

	const buildReadStatusRows = (statusKey, details) => {
		const rows = [];
		const formattedLastServiceDate = formatReadDate(details.lastServiceDate, statusKey, false);

		if (statusKey === "available") {
			rows.push(["Last Service Date", formattedLastServiceDate]);
			rows.push(["Upcoming Reservations", formatReadDate(details.upcomingPickupDatetime, statusKey, false)]);
			rows.push(["Total Earnings", formatReadCurrency(details.totalEarnings, statusKey, false)]);
			rows.push(["Total Reservations", normalizeReadValue(details.totalReservations, statusKey, false)]);
			return rows;
		}

		if (statusKey === "reserved") {
			const reservedPaymentStatus = normalizeReadValue(details.paymentStatus, statusKey, false);
			rows.push(["Last Service Date", formattedLastServiceDate]);
			rows.push(["Pickup Date", formatReadDate(details.pickupDatetime, statusKey, false)]);
			rows.push(["Return Date", formatReadDate(details.returnDatetime, statusKey, false)]);
			rows.push([
				"Payment Status",
				reservedPaymentStatus === readUnavailableText ? readUnavailableText : toTitleCase(reservedPaymentStatus),
			]);
			return rows;
		}

		if (statusKey === "on_trip") {
			const onTripPaymentStatus = normalizeReadValue(details.paymentStatus, statusKey, false);
			rows.push(["Last Service Date", formattedLastServiceDate]);
			rows.push(["Pickup Date", formatReadDate(details.pickupDatetime, statusKey, false)]);
			rows.push(["Return Date", formatReadDate(details.returnDatetime, statusKey, false)]);
			rows.push(["Current Location", normalizeReadValue(details.currentLocation, statusKey, false)]);
			rows.push([
				"Payment Status",
				onTripPaymentStatus === readUnavailableText ? readUnavailableText : toTitleCase(onTripPaymentStatus),
			]);
			return rows;
		}

		if (statusKey === "overdue") {
			const overduePaymentStatus = normalizeReadValue(details.paymentStatus, statusKey, false);
			let overdueDateLabel = formatReadDate(details.returnDatetime, statusKey, false);
			const parsedReturnDatetime = new Date(String(details.returnDatetime || "").replace(" ", "T"));
			if (
				overdueDateLabel !== readUnavailableText &&
				overdueDateLabel !== readUserNotApplicableText &&
				!Number.isNaN(parsedReturnDatetime.getTime())
			) {
				const nowTimestamp = Date.now();
				const overdueHours = Math.floor((nowTimestamp - parsedReturnDatetime.getTime()) / (1000 * 60 * 60));
				if (overdueHours > 0) {
					overdueDateLabel = `${escapeHtml(overdueDateLabel)} <span class="admin-vehicle-read-modal__overdue-hours">+${overdueHours} hours</span>`;
				} else {
					overdueDateLabel = escapeHtml(overdueDateLabel);
				}
			} else {
				overdueDateLabel = escapeHtml(overdueDateLabel);
			}

			rows.push(["Last Service Date", formattedLastServiceDate]);
			rows.push(["Return Date", overdueDateLabel, true]);
			rows.push(["Current Location", normalizeReadValue(details.currentLocation, statusKey, false)]);
			rows.push(["Total Late Fee ($10/h)", formatReadCurrency(details.lateFee, statusKey, false)]);
			rows.push([
				"Payment Status",
				overduePaymentStatus === readUnavailableText ? readUnavailableText : toTitleCase(overduePaymentStatus),
			]);
			return rows;
		}

		rows.push(["Issue Description", readUnavailableText]);
		rows.push(["Workshop Name", normalizeReadValue(details.maintenanceWorkshop, statusKey, false)]);
		rows.push([
			"Est. Completion",
			formatReadDate(
				details.maintenanceEstimateDatetime || details.upcomingPickupDatetime || details.returnDatetime,
				statusKey,
				false
			),
		]);
		rows.push(["Service Cost", formatReadCurrency(details.maintenanceCost, statusKey, false)]);
		return rows;
	};

	const updateReadStatusRows = (statusKey, details) => {
		if (!(adminReadStatusRowsNode instanceof HTMLElement)) {
			return;
		}

		const rows = buildReadStatusRows(statusKey, details);
		const rowMarkup = rows
			.map(([label, value, isHtml]) => {
				const safeLabel = escapeHtml(label || "");
				const safeValue = String(value || "N/A");
				return `
					<tr>
						<th scope="row">${safeLabel}</th>
						<td>${isHtml ? safeValue : escapeHtml(safeValue)}</td>
					</tr>
				`;
			})
			.join("");

		adminReadStatusRowsNode.innerHTML = rowMarkup;
	};

	const hydrateReadVehicleModal = (triggerButton) => {
		if (!(triggerButton instanceof HTMLElement)) {
			return;
		}

		const vehicleId = Number.parseInt(triggerButton.getAttribute("data-read-vehicle-id") || "0", 10);
		const vehicleName = triggerButton.getAttribute("data-read-vehicle-name") || "Vehicle";
		const vehicleFullName = triggerButton.getAttribute("data-read-vehicle-full-name") || vehicleName;
		const vehicleTypeRaw = (triggerButton.getAttribute("data-read-vehicle-type") || "cars").toLowerCase();
		const vehicleStatusRaw = (triggerButton.getAttribute("data-read-vehicle-status") || "available").toLowerCase();
		const vehicleStatus = Object.prototype.hasOwnProperty.call(readStatusLabels, vehicleStatusRaw)
			? vehicleStatusRaw
			: "available";
		const fleetModeForReadModal = triggerButton.getAttribute("data-delete-fleet-mode") || "type";

		const bookingNumber = triggerButton.getAttribute("data-read-booking-number") || "";
		const bookingUserName = triggerButton.getAttribute("data-read-booking-user-name") || "";
		const bookingUserPhone = triggerButton.getAttribute("data-read-booking-user-phone") || "";
		const pickupDatetime = triggerButton.getAttribute("data-read-booking-pickup") || "";
		const returnDatetime = triggerButton.getAttribute("data-read-booking-return") || "";
		const paymentStatus = triggerButton.getAttribute("data-read-booking-payment-status") || "";
		const lateFee = triggerButton.getAttribute("data-read-booking-late-fee") || "";
		const latitude = triggerButton.getAttribute("data-read-gps-latitude") || "";
		const longitude = triggerButton.getAttribute("data-read-gps-longitude") || "";
		const currentLocation =
			latitude !== "" && longitude !== "" ? `${latitude}, ${longitude}` : "";

		const details = {
			lastServiceDate: triggerButton.getAttribute("data-read-vehicle-last-service") || "",
			description: triggerButton.getAttribute("data-read-vehicle-description") || "",
			maintenanceWorkshop:
				triggerButton.getAttribute("data-read-maintenance-workshop") || "",
			maintenanceEstimateDatetime:
				triggerButton.getAttribute("data-read-maintenance-estimate") || "",
			maintenanceCost: triggerButton.getAttribute("data-read-maintenance-cost") || "",
			upcomingPickupDatetime: triggerButton.getAttribute("data-read-upcoming-pickup") || "",
			totalReservations: triggerButton.getAttribute("data-read-total-reservations") || "0",
			totalEarnings: triggerButton.getAttribute("data-read-total-earnings") || "0",
			pickupDatetime,
			returnDatetime,
			paymentStatus,
			lateFee,
			currentLocation,
		};

		const isUserStatus = readUserStatuses.includes(vehicleStatus);

		if (adminReadBookingNumberNode instanceof HTMLElement) {
			adminReadBookingNumberNode.textContent = isUserStatus
				? normalizeReadValue(bookingNumber, vehicleStatus, true)
				: readUserNotApplicableText;
		}

		if (adminReadCurrentUserLine instanceof HTMLElement && adminReadCurrentUserNode instanceof HTMLElement) {
			adminReadCurrentUserLine.hidden = !isUserStatus;

			if (!isUserStatus) {
				adminReadCurrentUserNode.textContent = "";
			} else {
				const hasFullUserDetails = !isReadValueMissing(bookingUserName) && !isReadValueMissing(bookingUserPhone);
				adminReadCurrentUserNode.textContent = hasFullUserDetails
					? `${String(bookingUserName).trim()} ${String(bookingUserPhone).trim()}`
					: readUnavailableText;
			}
		}

		if (adminReadStatusPill instanceof HTMLElement) {
			adminReadStatusPill.textContent = readStatusLabels[vehicleStatus] || "Available";
			adminReadStatusPill.className = `admin-vehicle-read-modal__status-pill admin-vehicle-read-modal__status-pill--${vehicleStatus.replace("_", "-")}`;
		}

		if (adminReadMaintenanceIndicator instanceof HTMLElement) {
			const showMaintenanceIndicator =
				fleetModeForReadModal === "type" && vehicleStatus === "available";
			adminReadMaintenanceIndicator.hidden = !showMaintenanceIndicator;
		}

		if (adminReadVehicleImage instanceof HTMLImageElement) {
			adminReadVehicleImage.src = triggerButton.getAttribute("data-read-vehicle-image") || "images/vehicle-feature.png";
			adminReadVehicleImage.alt = vehicleName;
		}

		if (adminReadVehicleTypeNode instanceof HTMLElement) {
			adminReadVehicleTypeNode.textContent = toTitleCase(vehicleTypeRaw.replace(/s$/, ""));
		}

		if (adminReadVehicleFullNameNode instanceof HTMLElement) {
			adminReadVehicleFullNameNode.textContent = vehicleFullName;
		}

		if (adminReadVehicleSeatsNode instanceof HTMLElement) {
			const seatsValue = normalizeReadValue(
				triggerButton.getAttribute("data-read-vehicle-seats"),
				vehicleStatus,
				false
			);
			adminReadVehicleSeatsNode.textContent =
				seatsValue === readUnavailableText ? readUnavailableText : `${seatsValue} Seats`;
		}

		if (adminReadVehicleTransmissionNode instanceof HTMLElement) {
			adminReadVehicleTransmissionNode.textContent = toTitleCase(
				normalizeReadValue(triggerButton.getAttribute("data-read-vehicle-transmission"), vehicleStatus, false)
			);
		}

		if (adminReadVehicleAgeNode instanceof HTMLElement) {
			const ageValue = normalizeReadValue(
				triggerButton.getAttribute("data-read-vehicle-age"),
				vehicleStatus,
				false
			);
			adminReadVehicleAgeNode.textContent =
				ageValue === readUnavailableText ? readUnavailableText : `${ageValue}+ Years`;
		}

		if (adminReadVehicleFuelNode instanceof HTMLElement) {
			adminReadVehicleFuelNode.textContent = toTitleCase(
				normalizeReadValue(triggerButton.getAttribute("data-read-vehicle-fuel"), vehicleStatus, false)
			);
		}

		if (adminReadVehiclePlateNode instanceof HTMLElement) {
			adminReadVehiclePlateNode.textContent = normalizeReadValue(
				triggerButton.getAttribute("data-read-vehicle-plate"),
				vehicleStatus,
				false
			);
		}

		updateReadStatusRows(vehicleStatus, details);

		if (adminReadDeleteAction instanceof HTMLElement) {
			const canDeleteFromReadModal =
				fleetModeForReadModal === "type" && vehicleStatus === "available";
			adminReadDeleteAction.hidden = !canDeleteFromReadModal;
			adminReadDeleteAction.setAttribute("data-delete-vehicle-id", String(vehicleId > 0 ? vehicleId : ""));
			adminReadDeleteAction.setAttribute("data-delete-vehicle-label", vehicleName);
			adminReadDeleteAction.setAttribute(
				"data-delete-fleet-mode",
				fleetModeForReadModal
			);
			adminReadDeleteAction.setAttribute(
				"data-delete-fleet-type",
				triggerButton.getAttribute("data-delete-fleet-type") || "cars"
			);
			adminReadDeleteAction.setAttribute(
				"data-delete-fleet-status",
				triggerButton.getAttribute("data-delete-fleet-status") || "reserved"
			);
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

			if (targetId === "admin-vehicle-read-modal") {
				hydrateReadVehicleModal(button);
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
