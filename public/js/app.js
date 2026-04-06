/**
 * Purpose: Shared frontend behaviors (nav interactions, modals, alerts, common utilities).
 * Website Section: Global Frontend.
 * Developer Notes: Implement nav toggle, flash auto-dismiss, CSRF header setup for AJAX, and shared helper functions.
 */

(function () {
  // admin login: stack-based modal navigation for menu/admin auth flow
  const modals = Array.from(
    document.querySelectorAll(".menu-modal[data-modal-id]"),
  );
  if (modals.length === 0) {
    return;
  }

  const modalById = new Map();
  modals.forEach((modal) => {
    if (modal.id) {
      modalById.set(modal.id, modal);
    }
  });

  const menuOpenButtons = Array.from(
    document.querySelectorAll("[data-menu-open]"),
  );
  const closeButtons = Array.from(
    document.querySelectorAll("[data-modal-close]"),
  );
  const backButtons = Array.from(
    document.querySelectorAll("[data-modal-back]"),
  );
  const targetButtons = Array.from(
    document.querySelectorAll("[data-modal-target]"),
  );
  const navigationLinks = Array.from(
    document.querySelectorAll(".menu-modal__link[href]"),
  );
  const passwordToggleButtons = Array.from(
    document.querySelectorAll("[data-password-toggle]"),
  );
  const adminLoginModal = document.getElementById("admin-login-modal");
  const adminVehicleReadModal = document.getElementById(
    "admin-vehicle-read-modal",
  );
  const adminMaintenanceFillModal = document.getElementById(
    "admin-maintenance-fill-modal",
  );
  const adminMaintenanceEditModal = document.getElementById(
    "admin-maintenance-edit-modal",
  );
  const adminCreateVehicleModal = document.getElementById(
    "admin-create-vehicle-modal",
  );
  const adminEditVehicleModal = document.getElementById(
    "admin-edit-vehicle-modal",
  );
  const adminDeleteVehicleModal = document.getElementById(
    "admin-delete-vehicle-modal",
  );
  const adminBookingReadModal = document.getElementById(
    "admin-booking-read-modal",
  );
  const adminBookingTrackModal = document.getElementById(
    "admin-booking-track-modal",
  );
  const adminDeleteBookingModal = document.getElementById(
    "admin-delete-booking-modal",
  );
  const adminDeleteVehicleIdInput = adminDeleteVehicleModal?.querySelector(
    "[data-delete-vehicle-id-input]",
  );
  const adminDeleteVehicleNameNode = adminDeleteVehicleModal?.querySelector(
    "[data-delete-vehicle-name]",
  );
  const adminDeleteFleetModeInput = adminDeleteVehicleModal?.querySelector(
    "[data-delete-fleet-mode-input]",
  );
  const adminDeleteFleetTypeInput = adminDeleteVehicleModal?.querySelector(
    "[data-delete-fleet-type-input]",
  );
  const adminDeleteFleetStatusInput = adminDeleteVehicleModal?.querySelector(
    "[data-delete-fleet-status-input]",
  );
  const adminBookingReadNumberNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-number]",
  );
  const adminBookingReadCustomerNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-customer]",
  );
  const adminBookingReadPaymentBadgeNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-payment-badge]",
  );
  const adminBookingReadPaymentIconNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-payment-icon]",
  );
  const adminBookingReadPaymentLabelNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-payment-label]",
  );
  const adminBookingReadStatusPill = adminBookingReadModal?.querySelector(
    "[data-booking-read-status-pill]",
  );
  const adminBookingReadImage = adminBookingReadModal?.querySelector(
    "[data-booking-read-image]",
  );
  const adminBookingReadVehicleTypeNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-vehicle-type]",
  );
  const adminBookingReadVehicleNameNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-vehicle-name]",
  );
  const adminBookingReadCustomerPhoneNode =
    adminBookingReadModal?.querySelector("[data-booking-read-customer-phone]");
  const adminBookingReadCustomerEmailNode =
    adminBookingReadModal?.querySelector("[data-booking-read-customer-email]");
  const adminBookingReadDriverIdNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-driver-id]",
  );
  const adminBookingReadPickupDateNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-pickup-date]",
  );
  const adminBookingReadReturnDateNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-return-date]",
  );
  const adminBookingReadReturnTimeNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-return-time]",
  );
  const adminBookingReadPricePerDayNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-price-per-day]",
  );
  const adminBookingReadDurationLabelNode =
    adminBookingReadModal?.querySelector("[data-booking-read-duration-label]");
  const adminBookingReadDurationPriceNode =
    adminBookingReadModal?.querySelector("[data-booking-read-duration-price]");
  const adminBookingReadDropChargeNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-drop-charge]",
  );
  const adminBookingReadLateFeeNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-late-fee]",
  );
  const adminBookingReadTaxesFeesNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-taxes-fees]",
  );
  const adminBookingReadBillingTotalNode = adminBookingReadModal?.querySelector(
    "[data-booking-read-billing-total]",
  );
  const adminBookingCompleteForm = adminBookingReadModal?.querySelector(
    "[data-booking-complete-form]",
  );
  const adminBookingCompleteIdInput = adminBookingReadModal?.querySelector(
    "[data-booking-complete-id-input]",
  );
  const adminBookingReturnTimeInput = adminBookingReadModal?.querySelector(
    "[data-booking-return-time-input]",
  );
  const adminBookingReturnTimePickerButtons = adminBookingReadModal
    ? Array.from(
        adminBookingReadModal.querySelectorAll(
          "[data-open-picker-for='admin-booking-return-time-input']",
        ),
      )
    : [];
  const adminBookingLateFeePreview = adminBookingReadModal?.querySelector(
    "[data-booking-late-fee-preview]",
  );
  const adminBookingCompleteSubmit = adminBookingReadModal?.querySelector(
    "[data-booking-complete-submit]",
  );
  const adminBookingTrackAction = adminBookingReadModal?.querySelector(
    "[data-booking-track-action]",
  );
  const adminBookingApproveForm = adminBookingReadModal?.querySelector(
    "[data-booking-approve-form]",
  );
  const adminBookingApproveIdInput = adminBookingReadModal?.querySelector(
    "[data-booking-approve-id-input]",
  );
  const adminBookingApproveAction = adminBookingReadModal?.querySelector(
    "[data-booking-approve-action]",
  );
  const adminBookingDeleteAction = adminBookingReadModal?.querySelector(
    "[data-booking-delete-action]",
  );
  const adminBookingTrackNumberNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-number]",
  );
  const adminBookingTrackCustomerNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-customer]",
  );
  const adminBookingTrackLocationLabelNode =
    adminBookingTrackModal?.querySelector(
      "[data-booking-track-location-label]",
    );
  const adminBookingTrackMapEmptyNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-map-empty]",
  );
  const adminBookingTrackRouteNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-route]",
  );
  const adminBookingTrackPickupPinNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-pickup-pin]",
  );
  const adminBookingTrackReturnPinNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-return-pin]",
  );
  const adminBookingTrackOpenMapsAction = adminBookingTrackModal?.querySelector(
    "[data-booking-track-open-maps]",
  );
  const adminBookingTrackPickupLocationNode =
    adminBookingTrackModal?.querySelector(
      "[data-booking-track-pickup-location]",
    );
  const adminBookingTrackReturnLocationNode =
    adminBookingTrackModal?.querySelector(
      "[data-booking-track-return-location]",
    );
  const adminBookingTrackRiskLabelNode = adminBookingTrackModal?.querySelector(
    "[data-booking-track-risk-label]",
  );
  const adminBookingTrackRiskPercentNode =
    adminBookingTrackModal?.querySelector("[data-booking-track-risk-percent]");
  const adminBookingTrackReturnRiskLabelNode =
    adminBookingTrackModal?.querySelector(
      "[data-booking-track-return-risk-label]",
    );
  const adminBookingTrackReturnRiskPercentNode =
    adminBookingTrackModal?.querySelector(
      "[data-booking-track-return-risk-percent]",
    );
  const adminBookingTrackSafetyScoreNode =
    adminBookingTrackModal?.querySelector("[data-booking-track-safety-score]");
  const adminBookingTrackSafetyLabelNode =
    adminBookingTrackModal?.querySelector("[data-booking-track-safety-label]");
  const adminDeleteBookingIdInput = adminDeleteBookingModal?.querySelector(
    "[data-delete-booking-id-input]",
  );
  const adminDeleteBookingNameNode = adminDeleteBookingModal?.querySelector(
    "[data-delete-booking-name]",
  );
  const adminReadBookingNumberNode = adminVehicleReadModal?.querySelector(
    "[data-read-booking-number]",
  );
  const adminReadCurrentUserLine = adminVehicleReadModal?.querySelector(
    "[data-read-current-user-line]",
  );
  const adminReadCurrentUserNode = adminVehicleReadModal?.querySelector(
    "[data-read-current-user]",
  );
  const adminReadStatusPill = adminVehicleReadModal?.querySelector(
    "[data-read-status-pill]",
  );
  const adminReadMaintenanceIndicator = adminVehicleReadModal?.querySelector(
    "[data-read-maintenance-indicator]",
  );
  const adminReadVehicleImage = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-image]",
  );
  const adminReadVehicleTypeNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-type]",
  );
  const adminReadVehicleFullNameNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-full-name]",
  );
  const adminReadVehicleSeatsNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-seats]",
  );
  const adminReadVehicleTransmissionNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-transmission]",
  );
  const adminReadVehicleAgeNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-age]",
  );
  const adminReadVehicleFuelNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-fuel]",
  );
  const adminReadVehiclePlateNode = adminVehicleReadModal?.querySelector(
    "[data-read-vehicle-plate]",
  );
  const adminReadStatusRowsNode = adminVehicleReadModal?.querySelector(
    "[data-read-status-rows]",
  );
  const adminReadDeleteAction = adminVehicleReadModal?.querySelector(
    "[data-read-delete-action]",
  );
  const adminReadEditAction = adminVehicleReadModal?.querySelector(
    "[data-read-edit-action]",
  );
  const adminMaintenanceFillForm = adminMaintenanceFillModal?.querySelector(
    "[data-maintenance-fill-form]",
  );
  const adminMaintenanceFillVehicleIdInput =
    adminMaintenanceFillModal?.querySelector(
      "[data-maintenance-vehicle-id-input]",
    );
  const adminMaintenanceFillFleetModeInput =
    adminMaintenanceFillModal?.querySelector(
      "[data-maintenance-fleet-mode-input]",
    );
  const adminMaintenanceFillFleetTypeInput =
    adminMaintenanceFillModal?.querySelector(
      "[data-maintenance-fleet-type-input]",
    );
  const adminMaintenanceFillFleetStatusInput =
    adminMaintenanceFillModal?.querySelector(
      "[data-maintenance-fleet-status-input]",
    );
  const adminMaintenanceFillIssueInput =
    adminMaintenanceFillModal?.querySelector("[data-maintenance-issue-input]");
  const adminMaintenanceFillWorkshopInput =
    adminMaintenanceFillModal?.querySelector(
      "[data-maintenance-workshop-input]",
    );
  const adminMaintenanceFillEstimateInput =
    adminMaintenanceFillModal?.querySelector(
      "[data-maintenance-estimate-input]",
    );
  const adminMaintenanceFillCostInput =
    adminMaintenanceFillModal?.querySelector("[data-maintenance-cost-input]");
  const adminMaintenanceEditForm = adminMaintenanceEditModal?.querySelector(
    "[data-maintenance-edit-form]",
  );
  const adminMaintenanceEditVehicleIdInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-vehicle-id-input]",
    );
  const adminMaintenanceEditFleetModeInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-fleet-mode-input]",
    );
  const adminMaintenanceEditFleetTypeInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-fleet-type-input]",
    );
  const adminMaintenanceEditFleetStatusInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-fleet-status-input]",
    );
  const adminMaintenanceEditIssueInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-issue-input]",
    );
  const adminMaintenanceEditWorkshopInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-workshop-input]",
    );
  const adminMaintenanceEditEstimateInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-estimate-input]",
    );
  const adminMaintenanceEditCostInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-edit-cost-input]",
    );
  const adminMaintenanceCompleteVehicleIdInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-complete-vehicle-id-input]",
    );
  const adminMaintenanceCompleteFleetModeInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-complete-fleet-mode-input]",
    );
  const adminMaintenanceCompleteFleetTypeInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-complete-fleet-type-input]",
    );
  const adminMaintenanceCompleteFleetStatusInput =
    adminMaintenanceEditModal?.querySelector(
      "[data-maintenance-complete-fleet-status-input]",
    );
  const adminCreateVehicleForm = adminCreateVehicleModal?.querySelector(
    "[data-admin-create-vehicle-form]",
  );
  const adminCreateStepLabel = adminCreateVehicleModal?.querySelector(
    "[data-create-step-label]",
  );
  const adminCreateStepPanels = adminCreateVehicleModal
    ? Array.from(
        adminCreateVehicleModal.querySelectorAll("[data-create-step-panel]"),
      )
    : [];
  const adminCreateStepNextButtons = adminCreateVehicleModal
    ? Array.from(
        adminCreateVehicleModal.querySelectorAll("[data-create-step-next]"),
      )
    : [];
  const adminCreateStepPrevButtons = adminCreateVehicleModal
    ? Array.from(
        adminCreateVehicleModal.querySelectorAll("[data-create-step-prev]"),
      )
    : [];
  const adminCreateStepCloseButton = adminCreateVehicleModal?.querySelector(
    "[data-create-step-close]",
  );
  const adminCreateFleetModeInput = adminCreateVehicleModal?.querySelector(
    "[data-create-fleet-mode-input]",
  );
  const adminCreateFleetTypeInput = adminCreateVehicleModal?.querySelector(
    "[data-create-fleet-type-input]",
  );
  const adminCreateVehicleTypeInput = adminCreateVehicleModal?.querySelector(
    "[data-create-vehicle-type-input]",
  );
  const adminCreateImageInput = adminCreateVehicleModal?.querySelector(
    "[data-create-image-input]",
  );
  const adminCreateImageNameInput = document.getElementById(
    "create-vehicle-image-name",
  );
  const adminCreateImageClearButton = adminCreateVehicleModal?.querySelector(
    "[data-create-image-clear]",
  );
  const adminEditVehicleForm = adminEditVehicleModal?.querySelector(
    "[data-admin-edit-vehicle-form]",
  );
  const adminEditStepPanels = adminEditVehicleModal
    ? Array.from(
        adminEditVehicleModal.querySelectorAll("[data-edit-step-panel]"),
      )
    : [];
  const adminEditStepNextButtons = adminEditVehicleModal
    ? Array.from(
        adminEditVehicleModal.querySelectorAll("[data-edit-step-next]"),
      )
    : [];
  const adminEditStepPrevButtons = adminEditVehicleModal
    ? Array.from(
        adminEditVehicleModal.querySelectorAll("[data-edit-step-prev]"),
      )
    : [];
  const adminEditStepCloseButton = adminEditVehicleModal?.querySelector(
    "[data-edit-step-close]",
  );
  const adminEditVehicleIdInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-id-input]",
  );
  const adminEditCurrentImagePathInput = adminEditVehicleModal?.querySelector(
    "[data-edit-current-image-path-input]",
  );
  const adminEditFleetModeInput = adminEditVehicleModal?.querySelector(
    "[data-edit-fleet-mode-input]",
  );
  const adminEditFleetTypeInput = adminEditVehicleModal?.querySelector(
    "[data-edit-fleet-type-input]",
  );
  const adminEditFleetStatusInput = adminEditVehicleModal?.querySelector(
    "[data-edit-fleet-status-input]",
  );
  const adminEditVehicleTypeInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-type-input]",
  );
  const adminEditFullNameInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-full-name-input]",
  );
  const adminEditShortNameInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-short-name-input]",
  );
  const adminEditPriceInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-price-input]",
  );
  const adminEditDriverAgeInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-driver-age-input]",
  );
  const adminEditSeatsInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-seats-input]",
  );
  const adminEditTransmissionInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-transmission-input]",
  );
  const adminEditFuelInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-fuel-input]",
  );
  const adminEditLicenseInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-license-input]",
  );
  const adminEditGpsInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-gps-id-input]",
  );
  const adminEditStatusInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-status-input]",
  );
  const adminEditLastServiceInput = adminEditVehicleModal?.querySelector(
    "[data-edit-last-service-input]",
  );
  const adminEditDescriptionInput = adminEditVehicleModal?.querySelector(
    "[data-edit-vehicle-description-input]",
  );
  const adminEditImageInput = adminEditVehicleModal?.querySelector(
    "[data-edit-image-input]",
  );
  const adminEditImageNameInput = adminEditVehicleModal?.querySelector(
    "[data-edit-image-name-input]",
  );
  const adminEditImageClearButton = adminEditVehicleModal?.querySelector(
    "[data-edit-image-clear]",
  );
  const adminCreateSelectInputs = Array.from(
    document.querySelectorAll(".admin-create-vehicle-modal__select"),
  );
  const adminLoginForm = adminLoginModal?.querySelector(
    "[data-admin-login-form]",
  );
  const adminLoginInputs = adminLoginModal
    ? Array.from(adminLoginModal.querySelectorAll("[data-admin-login-input]"))
    : [];
  const adminPasswordInput = document.getElementById("admin-password-input");
  const adminPasswordToggle = adminLoginModal?.querySelector(
    "[data-password-toggle][data-password-target='admin-password-input']",
  );

  let lastFocusedElement = null;
  const modalHistory = [];
  let readModalContext = null;
  let bookingReadContext = null;
  let createVehicleStep = 1;
  let editVehicleStep = 1;
  const createCustomSelectControllers = [];
  let openCreateCustomSelectController = null;

  const clearAdminLoginUiState = () => {
    if (adminLoginForm instanceof HTMLFormElement) {
      adminLoginForm.reset();
    }

    adminLoginInputs.forEach((input) => {
      if (input instanceof HTMLElement) {
        input.classList.remove("admin-login-form__input--error");
      }
    });

    const loginError = adminLoginModal?.querySelector(
      "[data-admin-login-error]",
    );
    if (loginError instanceof HTMLElement) {
      loginError.remove();
    }

    if (adminPasswordInput instanceof HTMLInputElement) {
      adminPasswordInput.type = "password";
    }

    if (adminPasswordToggle instanceof HTMLElement) {
      adminPasswordToggle.setAttribute("aria-label", "Show password");
      const icon = adminPasswordToggle.querySelector(
        ".material-symbols-rounded",
      );
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
    rawValue === null ||
    rawValue === undefined ||
    String(rawValue).trim() === "";

  const getReadMissingFallback = (statusKey, isUserRelated = false) => {
    if (
      isUserRelated &&
      (statusKey === "available" || statusKey === "maintenance")
    ) {
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
    const formattedLastServiceDate = formatReadDate(
      details.lastServiceDate,
      statusKey,
      false,
    );

    if (statusKey === "available") {
      rows.push(["Last Service Date", formattedLastServiceDate]);
      rows.push([
        "Upcoming Reservations",
        formatReadDate(details.upcomingPickupDatetime, statusKey, false),
      ]);
      rows.push([
        "Total Earnings",
        formatReadCurrency(details.totalEarnings, statusKey, false),
      ]);
      rows.push([
        "Total Reservations",
        normalizeReadValue(details.totalReservations, statusKey, false),
      ]);
      return rows;
    }

    if (statusKey === "reserved") {
      const reservedPaymentStatus = normalizeReadValue(
        details.paymentStatus,
        statusKey,
        false,
      );
      rows.push(["Last Service Date", formattedLastServiceDate]);
      rows.push([
        "Pickup Date",
        formatReadDate(details.pickupDatetime, statusKey, false),
      ]);
      rows.push([
        "Return Date",
        formatReadDate(details.returnDatetime, statusKey, false),
      ]);
      rows.push([
        "Payment Status",
        reservedPaymentStatus === readUnavailableText
          ? readUnavailableText
          : toTitleCase(reservedPaymentStatus),
      ]);
      return rows;
    }

    if (statusKey === "on_trip") {
      const onTripPaymentStatus = normalizeReadValue(
        details.paymentStatus,
        statusKey,
        false,
      );
      rows.push(["Last Service Date", formattedLastServiceDate]);
      rows.push([
        "Pickup Date",
        formatReadDate(details.pickupDatetime, statusKey, false),
      ]);
      rows.push([
        "Return Date",
        formatReadDate(details.returnDatetime, statusKey, false),
      ]);
      rows.push([
        "Current Location",
        normalizeReadValue(details.currentLocation, statusKey, false),
      ]);
      rows.push([
        "Payment Status",
        onTripPaymentStatus === readUnavailableText
          ? readUnavailableText
          : toTitleCase(onTripPaymentStatus),
      ]);
      return rows;
    }

    if (statusKey === "overdue") {
      const overduePaymentStatus = normalizeReadValue(
        details.paymentStatus,
        statusKey,
        false,
      );
      let overdueDateLabel = formatReadDate(
        details.returnDatetime,
        statusKey,
        false,
      );
      const parsedReturnDatetime = new Date(
        String(details.returnDatetime || "").replace(" ", "T"),
      );
      if (
        overdueDateLabel !== readUnavailableText &&
        overdueDateLabel !== readUserNotApplicableText &&
        !Number.isNaN(parsedReturnDatetime.getTime())
      ) {
        const nowTimestamp = Date.now();
        const overdueHours = Math.floor(
          (nowTimestamp - parsedReturnDatetime.getTime()) / (1000 * 60 * 60),
        );
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
      rows.push([
        "Current Location",
        normalizeReadValue(details.currentLocation, statusKey, false),
      ]);
      rows.push([
        "Total Late Fee ($10/h)",
        formatReadCurrency(details.lateFee, statusKey, false),
      ]);
      rows.push([
        "Payment Status",
        overduePaymentStatus === readUnavailableText
          ? readUnavailableText
          : toTitleCase(overduePaymentStatus),
      ]);
      return rows;
    }

    rows.push([
      "Issue Description",
      normalizeReadValue(details.maintenanceIssueDescription, statusKey, false),
    ]);
    rows.push([
      "Workshop Name",
      normalizeReadValue(details.maintenanceWorkshop, statusKey, false),
    ]);
    rows.push([
      "Est. Completion",
      formatReadDate(
        details.maintenanceEstimateDatetime ||
          details.upcomingPickupDatetime ||
          details.returnDatetime,
        statusKey,
        false,
      ),
    ]);
    rows.push([
      "Service Cost",
      formatReadCurrency(details.maintenanceCost, statusKey, false),
    ]);
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

  // maintenance edit/fillup form: normalize YYYY-MM-DD date values for date inputs.
  const toDateInputValue = (rawValue) => {
    if (isReadValueMissing(rawValue)) {
      return "";
    }

    const normalized = String(rawValue).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
      return normalized;
    }

    const parsedDate = new Date(normalized.replace(" ", "T"));
    if (Number.isNaN(parsedDate.getTime())) {
      return "";
    }

    const day = String(parsedDate.getDate()).padStart(2, "0");
    const month = parsedDate.toLocaleString("en-US", { month: "short" });
    const year = parsedDate.getFullYear();
    return `${day} ${month}, ${year}`;
  };

  const parseDateTimeValue = (rawValue) => {
    const normalized = String(rawValue || "").trim();
    if (normalized === "") {
      return null;
    }

    const parsedDate = new Date(
      normalized.includes("T") ? normalized : normalized.replace(" ", "T"),
    );
    if (Number.isNaN(parsedDate.getTime())) {
      return null;
    }

    return parsedDate;
  };

  const formatBookingCurrency = (rawValue) => {
    const numericValue = Number.parseFloat(String(rawValue || "0"));
    return Number.isFinite(numericValue)
      ? `$${numericValue.toFixed(2)}`
      : "$0.00";
  };

  const normalizeTrackText = (rawValue, fallbackValue = "Unavailable") => {
    const normalized = String(rawValue || "").trim();
    return normalized !== "" ? normalized : fallbackValue;
  };

  const parseTrackCoordinate = (rawValue) => {
    const numericValue = Number.parseFloat(String(rawValue || "").trim());
    if (!Number.isFinite(numericValue)) {
      return null;
    }

    return numericValue;
  };

  const hasTrackGpsSignal = (latitude, longitude) => {
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
      return false;
    }

    return Math.abs(latitude) > 0.00001 || Math.abs(longitude) > 0.00001;
  };

  const normalizeTrackRiskClass = (label) => {
    const normalized = String(label || "")
      .trim()
      .toLowerCase();
    if (normalized === "high") {
      return "is-high";
    }
    if (normalized === "moderate") {
      return "is-moderate";
    }
    if (normalized === "low") {
      return "is-low";
    }

    return "";
  };

  const normalizeTrackSafetyClass = (label) => {
    const normalized = String(label || "")
      .trim()
      .toLowerCase();
    if (normalized === "high") {
      return "is-high";
    }
    if (normalized === "moderate") {
      return "is-moderate";
    }
    if (normalized === "low") {
      return "is-low";
    }

    return "";
  };

  const bookingPaymentClassOptions = [
    "admin-bookings__payment--paid",
    "admin-bookings__payment--pending",
    "admin-bookings__payment--cancelled",
    "admin-bookings__payment--unpaid",
    "admin-bookings__payment--refunded",
    "admin-bookings__payment--unknown",
  ];

  const calculateBookingTaxesAndTotal = (
    durationPrice,
    dropCharge,
    lateFee,
    taxRate = 0.13,
  ) => {
    const normalizedDurationPrice = Number.isFinite(durationPrice)
      ? durationPrice
      : 0;
    const normalizedDropCharge = Number.isFinite(dropCharge) ? dropCharge : 0;
    const normalizedLateFee = Number.isFinite(lateFee) ? lateFee : 0;
    const normalizedTaxRate = Number.isFinite(taxRate) ? taxRate : 0.13;

    const taxBase = Math.max(
      0,
      normalizedDurationPrice + normalizedDropCharge + normalizedLateFee,
    );
    const taxes = taxBase * normalizedTaxRate;
    const total = taxBase + taxes;

    return {
      taxes,
      total,
    };
  };

  const toDateTimeLocalInputValue = (rawValue) => {
    const parsedDate = parseDateTimeValue(rawValue);
    if (!parsedDate) {
      return "";
    }

    const year = parsedDate.getFullYear();
    const month = String(parsedDate.getMonth() + 1).padStart(2, "0");
    const day = String(parsedDate.getDate()).padStart(2, "0");
    const hours = String(parsedDate.getHours()).padStart(2, "0");
    const minutes = String(parsedDate.getMinutes()).padStart(2, "0");
    return `${year}-${month}-${day} ${hours}:${minutes}`;
  };

  const initAdminBookingReturnTimePicker = () => {
    if (!(adminBookingReturnTimeInput instanceof HTMLInputElement)) {
      return;
    }

    if (typeof window.flatpickr !== "function") {
      return;
    }

    if (adminBookingReturnTimeInput._flatpickr) {
      return;
    }

    window.flatpickr(adminBookingReturnTimeInput, {
      allowInput: true,
      disableMobile: true,
      static: true,
      monthSelectorType: "static",
      enableTime: true,
      noCalendar: false,
      dateFormat: "Y-m-d H:i",
      altInput: true,
      altInputClass: "admin-booking-read-modal__input",
      altFormat: "d/m/Y h:i K",
      minuteIncrement: 5,
      onReady: (_selectedDates, _dateStr, instance) => {
        if (instance.altInput instanceof HTMLInputElement) {
          instance.altInput.placeholder =
            adminBookingReturnTimeInput.getAttribute("placeholder") ||
            "dd/mm/yyyy  --:-- --";
        }
      },
      onOpen: (_selectedDates, _dateStr, instance) => {
        if (instance.calendarContainer instanceof HTMLElement) {
          instance.calendarContainer.classList.add(
            "admin-booking-return-flatpickr",
          );
        }
      },
      onValueUpdate: () => {
        updateBookingLateFeePreview();
      },
      prevArrow:
        '<span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>',
      nextArrow:
        '<span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>',
    });
  };

  const readBookingBoolean = (triggerButton, attributeName) =>
    String(
      triggerButton.getAttribute(attributeName) || "false",
    ).toLowerCase() === "true";

  const resetBookingReadModal = () => {
    bookingReadContext = null;

    if (adminBookingCompleteForm instanceof HTMLFormElement) {
      adminBookingCompleteForm.reset();
      adminBookingCompleteForm.hidden = true;
    }

    if (adminBookingReturnTimeInput instanceof HTMLInputElement) {
      adminBookingReturnTimeInput.required = false;
      if (adminBookingReturnTimeInput._flatpickr) {
        adminBookingReturnTimeInput._flatpickr.clear();
      }
    }

    if (adminBookingReadReturnTimeNode instanceof HTMLElement) {
      adminBookingReadReturnTimeNode.textContent = "N/A";
    }

    if (adminBookingReadStatusPill instanceof HTMLElement) {
      adminBookingReadStatusPill.className =
        "admin-booking-read-modal__status-pill admin-booking-read-modal__status-pill--unknown";
      adminBookingReadStatusPill.textContent = "Unknown";
    }

    if (adminBookingReadPaymentBadgeNode instanceof HTMLElement) {
      adminBookingReadPaymentBadgeNode.className =
        "admin-booking-read-modal__payment admin-bookings__payment admin-bookings__payment--unknown";
    }

    if (adminBookingReadPaymentIconNode instanceof HTMLElement) {
      adminBookingReadPaymentIconNode.textContent = "help";
    }

    if (adminBookingReadPaymentLabelNode instanceof HTMLElement) {
      adminBookingReadPaymentLabelNode.textContent = "Unknown";
    }

    if (adminBookingTrackAction instanceof HTMLElement) {
      adminBookingTrackAction.hidden = true;
      adminBookingTrackAction.classList.remove("is-disabled");
      adminBookingTrackAction.removeAttribute("aria-disabled");
      adminBookingTrackAction.removeAttribute("tabindex");
      adminBookingTrackAction.removeAttribute("data-booking-track-number");
      adminBookingTrackAction.removeAttribute("data-booking-track-customer");
      adminBookingTrackAction.removeAttribute("data-booking-track-location");
      adminBookingTrackAction.removeAttribute("data-booking-track-pickup");
      adminBookingTrackAction.removeAttribute("data-booking-track-return");
      adminBookingTrackAction.removeAttribute(
        "data-booking-track-return-risk-label",
      );
      adminBookingTrackAction.removeAttribute(
        "data-booking-track-return-risk-percent",
      );
      adminBookingTrackAction.removeAttribute("data-booking-track-risk-label");
      adminBookingTrackAction.removeAttribute(
        "data-booking-track-risk-percent",
      );
      adminBookingTrackAction.removeAttribute(
        "data-booking-track-safety-score",
      );
      adminBookingTrackAction.removeAttribute(
        "data-booking-track-safety-label",
      );
      adminBookingTrackAction.removeAttribute("data-booking-track-map-url");
      adminBookingTrackAction.removeAttribute("data-booking-track-has-signal");
    }

    if (adminBookingApproveForm instanceof HTMLElement) {
      adminBookingApproveForm.hidden = true;
    }

    if (adminBookingDeleteAction instanceof HTMLElement) {
      adminBookingDeleteAction.hidden = true;
    }

    if (adminBookingCompleteSubmit instanceof HTMLButtonElement) {
      adminBookingCompleteSubmit.disabled = true;
      adminBookingCompleteSubmit.hidden = true;
    }

    if (adminBookingLateFeePreview instanceof HTMLElement) {
      adminBookingLateFeePreview.textContent = "Late fee: $0.00 (0h x $10)";
      adminBookingLateFeePreview.hidden = true;
    }
  };

  const updateBookingLateFeePreview = () => {
    if (
      !(adminBookingLateFeePreview instanceof HTMLElement) ||
      !(adminBookingReturnTimeInput instanceof HTMLInputElement) ||
      !bookingReadContext ||
      bookingReadContext.allowLateFeePreview !== true
    ) {
      return;
    }

    const scheduledReturnDate = parseDateTimeValue(
      bookingReadContext.scheduledReturnDatetime,
    );
    const selectedReturnDate = parseDateTimeValue(
      adminBookingReturnTimeInput.value,
    );

    let lateHours = 0;
    if (
      scheduledReturnDate instanceof Date &&
      selectedReturnDate instanceof Date &&
      selectedReturnDate > scheduledReturnDate
    ) {
      lateHours = Math.floor(
        (selectedReturnDate.getTime() - scheduledReturnDate.getTime()) /
          (1000 * 60 * 60),
      );
      if (!Number.isFinite(lateHours) || lateHours < 0) {
        lateHours = 0;
      }
    }

    const lateFee = lateHours * 10;
    adminBookingLateFeePreview.textContent = `Late fee: $${lateFee.toFixed(2)} (${lateHours}h x $10)`;
    adminBookingLateFeePreview.hidden = false;

    if (adminBookingReadLateFeeNode instanceof HTMLElement) {
      adminBookingReadLateFeeNode.textContent = formatBookingCurrency(lateFee);
    }

    const billingSummary = calculateBookingTaxesAndTotal(
      bookingReadContext.durationPrice,
      bookingReadContext.dropCharge,
      lateFee,
      bookingReadContext.taxRate,
    );

    if (adminBookingReadTaxesFeesNode instanceof HTMLElement) {
      adminBookingReadTaxesFeesNode.textContent = formatBookingCurrency(
        billingSummary.taxes,
      );
    }

    if (adminBookingReadBillingTotalNode instanceof HTMLElement) {
      adminBookingReadBillingTotalNode.textContent = formatBookingCurrency(
        billingSummary.total,
      );
    }
  };

  const hydrateBookingReadModal = (triggerButton) => {
    if (!(triggerButton instanceof HTMLElement)) {
      return;
    }

    const bookingId = Number.parseInt(
      triggerButton.getAttribute("data-booking-id") || "0",
      10,
    );
    const bookingDisplayId =
      triggerButton.getAttribute("data-booking-display-id") || "#N/A";
    const customerName =
      triggerButton.getAttribute("data-booking-customer-name") || "Unknown";
    const customerPhone =
      triggerButton.getAttribute("data-booking-customer-phone") || "N/A";
    const customerEmail =
      triggerButton.getAttribute("data-booking-customer-email") || "N/A";
    const driverId =
      triggerButton.getAttribute("data-booking-driver-id") || "N/A";
    const vehicleName =
      triggerButton.getAttribute("data-booking-vehicle-name") || "Vehicle";
    const vehicleType =
      triggerButton.getAttribute("data-booking-vehicle-type") || "car";
    const vehicleStatusKey = (
      triggerButton.getAttribute("data-booking-vehicle-status") || "unknown"
    ).toLowerCase();
    const vehicleStatusLabel =
      triggerButton.getAttribute("data-booking-vehicle-status-label") ||
      toTitleCase(vehicleStatusKey.replace("_", " "));
    const vehicleImage =
      triggerButton.getAttribute("data-booking-vehicle-image") ||
      "images/vehicle-feature.png";
    const pickupDate =
      triggerButton.getAttribute("data-booking-pickup-date") || "N/A";
    const returnDate =
      triggerButton.getAttribute("data-booking-return-date") || "N/A";
    const returnTimeDisplay =
      triggerButton.getAttribute("data-booking-return-time-display") || "N/A";
    const scheduledReturnDatetime =
      triggerButton.getAttribute("data-booking-return-datetime") || "";
    const paymentStatusLabel =
      triggerButton.getAttribute("data-booking-payment-label") || "N/A";
    const paymentStatusIcon =
      triggerButton.getAttribute("data-booking-payment-icon") || "help";
    const paymentStatusClass =
      triggerButton.getAttribute("data-booking-payment-class") ||
      "admin-bookings__payment--unknown";
    const pricePerDay = Number.parseFloat(
      triggerButton.getAttribute("data-booking-price-per-day") || "0",
    );
    const durationDays = Number.parseInt(
      triggerButton.getAttribute("data-booking-duration-days") || "1",
      10,
    );
    const durationLabel =
      triggerButton.getAttribute("data-booking-duration-label") ||
      "Price for 1 day";
    const durationPrice = Number.parseFloat(
      triggerButton.getAttribute("data-booking-duration-price") || "0",
    );
    const dropCharge = Number.parseFloat(
      triggerButton.getAttribute("data-booking-drop-charge") || "0",
    );
    const existingLateFee = Number.parseFloat(
      triggerButton.getAttribute("data-booking-late-fee") || "0",
    );
    const lateFeeNotApplicable = readBookingBoolean(
      triggerButton,
      "data-booking-late-fee-na",
    );
    const taxesFees = Number.parseFloat(
      triggerButton.getAttribute("data-booking-taxes-fees") || "0",
    );
    const billingTotal = Number.parseFloat(
      triggerButton.getAttribute("data-booking-billing-total") || "0",
    );
    const returnTimeInputValue =
      triggerButton.getAttribute("data-booking-return-time-input") ||
      toDateTimeLocalInputValue(scheduledReturnDatetime);
    const trackPickupLocation = normalizeTrackText(
      triggerButton.getAttribute("data-booking-pickup-location"),
    );
    const trackReturnLocation = normalizeTrackText(
      triggerButton.getAttribute("data-booking-return-location"),
    );
    const trackLocationLabel = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-location-label"),
    );
    const trackRiskLabel = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-risk-label"),
    );
    const trackRiskPercent = String(
      triggerButton.getAttribute("data-booking-track-risk-percent") || "",
    ).trim();
    const trackSafetyScore = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-safety-score"),
    );
    const trackSafetyLabel = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-safety-label"),
      "",
    );
    const trackMapUrl = String(
      triggerButton.getAttribute("data-booking-track-map-url") || "",
    ).trim();
    const trackLatitude = parseTrackCoordinate(
      triggerButton.getAttribute("data-booking-gps-latitude"),
    );
    const trackLongitude = parseTrackCoordinate(
      triggerButton.getAttribute("data-booking-gps-longitude"),
    );
    const trackHasSignal =
      hasTrackGpsSignal(trackLatitude, trackLongitude) ||
      readBookingBoolean(triggerButton, "data-booking-track-has-signal");

    const canTrack = readBookingBoolean(
      triggerButton,
      "data-booking-can-track",
    );
    const isTrackDisabled = readBookingBoolean(
      triggerButton,
      "data-booking-track-disabled",
    );
    const canCompleteReturn = readBookingBoolean(
      triggerButton,
      "data-booking-can-complete",
    );
    const canApproveCancellation = readBookingBoolean(
      triggerButton,
      "data-booking-can-approve-cancellation",
    );
    const canDelete = readBookingBoolean(
      triggerButton,
      "data-booking-can-delete",
    );

    if (adminBookingReadNumberNode instanceof HTMLElement) {
      adminBookingReadNumberNode.textContent = bookingDisplayId;
    }

    if (adminBookingReadCustomerNode instanceof HTMLElement) {
      adminBookingReadCustomerNode.textContent = customerName;
    }

    if (adminBookingReadPaymentBadgeNode instanceof HTMLElement) {
      const sanitizedPaymentClass = bookingPaymentClassOptions.includes(
        paymentStatusClass,
      )
        ? paymentStatusClass
        : "admin-bookings__payment--unknown";
      adminBookingReadPaymentBadgeNode.className =
        "admin-booking-read-modal__payment admin-bookings__payment";
      adminBookingReadPaymentBadgeNode.classList.add(sanitizedPaymentClass);
    }

    if (adminBookingReadPaymentIconNode instanceof HTMLElement) {
      adminBookingReadPaymentIconNode.textContent = paymentStatusIcon;
    }

    if (adminBookingReadPaymentLabelNode instanceof HTMLElement) {
      adminBookingReadPaymentLabelNode.textContent = paymentStatusLabel;
    }

    if (adminBookingReadStatusPill instanceof HTMLElement) {
      const statusPillKey = [
        "available",
        "unavailable",
        "reserved",
        "on_trip",
        "overdue",
        "maintenance",
        "ready",
      ].includes(vehicleStatusKey)
        ? vehicleStatusKey
        : "available";

      const normalizedVehicleStatusLabel =
        String(vehicleStatusLabel || "").trim() ||
        (statusPillKey === "unavailable" ? "Unavailable" : "Available");

      adminBookingReadStatusPill.textContent = normalizedVehicleStatusLabel;
      adminBookingReadStatusPill.className = `admin-booking-read-modal__status-pill admin-booking-read-modal__status-pill--${statusPillKey.replace("_", "-")}`;
    }

    if (adminBookingReadImage instanceof HTMLImageElement) {
      adminBookingReadImage.src = vehicleImage;
      adminBookingReadImage.alt = vehicleName;
    }

    if (adminBookingReadVehicleTypeNode instanceof HTMLElement) {
      adminBookingReadVehicleTypeNode.textContent = toTitleCase(
        String(vehicleType).replace(/s$/, ""),
      );
    }

    if (adminBookingReadVehicleNameNode instanceof HTMLElement) {
      adminBookingReadVehicleNameNode.textContent = vehicleName;
    }

    if (adminBookingReadCustomerPhoneNode instanceof HTMLElement) {
      adminBookingReadCustomerPhoneNode.textContent = customerPhone || "N/A";
    }

    if (adminBookingReadCustomerEmailNode instanceof HTMLElement) {
      adminBookingReadCustomerEmailNode.textContent = customerEmail || "N/A";
    }

    if (adminBookingReadDriverIdNode instanceof HTMLElement) {
      adminBookingReadDriverIdNode.textContent = driverId || "N/A";
    }

    if (adminBookingReadPickupDateNode instanceof HTMLElement) {
      adminBookingReadPickupDateNode.textContent = pickupDate;
    }

    if (adminBookingReadReturnDateNode instanceof HTMLElement) {
      adminBookingReadReturnDateNode.textContent = returnDate;
    }

    if (adminBookingReadReturnTimeNode instanceof HTMLElement) {
      adminBookingReadReturnTimeNode.textContent = returnTimeDisplay;
    }

    if (adminBookingReadPricePerDayNode instanceof HTMLElement) {
      adminBookingReadPricePerDayNode.textContent =
        formatBookingCurrency(pricePerDay);
    }

    if (adminBookingReadDurationLabelNode instanceof HTMLElement) {
      const normalizedDays =
        Number.isFinite(durationDays) && durationDays > 0 ? durationDays : 1;
      adminBookingReadDurationLabelNode.textContent =
        durationLabel ||
        (normalizedDays === 1
          ? "Price for 1 day"
          : `Price for ${normalizedDays} days`);
    }

    if (adminBookingReadDurationPriceNode instanceof HTMLElement) {
      adminBookingReadDurationPriceNode.textContent =
        formatBookingCurrency(durationPrice);
    }

    if (adminBookingReadDropChargeNode instanceof HTMLElement) {
      adminBookingReadDropChargeNode.textContent =
        formatBookingCurrency(dropCharge);
    }

    if (adminBookingReadLateFeeNode instanceof HTMLElement) {
      adminBookingReadLateFeeNode.textContent =
        lateFeeNotApplicable && !canCompleteReturn
          ? "N/A"
          : formatBookingCurrency(existingLateFee);
    }

    if (adminBookingReadTaxesFeesNode instanceof HTMLElement) {
      adminBookingReadTaxesFeesNode.textContent =
        formatBookingCurrency(taxesFees);
    }

    if (adminBookingReadBillingTotalNode instanceof HTMLElement) {
      adminBookingReadBillingTotalNode.textContent =
        formatBookingCurrency(billingTotal);
    }

    if (adminBookingCompleteIdInput instanceof HTMLInputElement) {
      adminBookingCompleteIdInput.value =
        bookingId > 0 ? String(bookingId) : "";
    }

    if (adminBookingReturnTimeInput instanceof HTMLInputElement) {
      adminBookingReturnTimeInput.value = returnTimeInputValue;
      adminBookingReturnTimeInput.required = canCompleteReturn;

      if (adminBookingReturnTimeInput._flatpickr) {
        const parsedReturnDate = parseDateTimeValue(returnTimeInputValue);
        if (parsedReturnDate instanceof Date) {
          adminBookingReturnTimeInput._flatpickr.setDate(
            parsedReturnDate,
            false,
          );
        } else {
          adminBookingReturnTimeInput._flatpickr.clear();
        }
      }
    }

    if (adminBookingCompleteForm instanceof HTMLElement) {
      adminBookingCompleteForm.hidden = !canCompleteReturn;
    }

    if (adminBookingCompleteSubmit instanceof HTMLButtonElement) {
      adminBookingCompleteSubmit.disabled = !canCompleteReturn;
      adminBookingCompleteSubmit.hidden = !canCompleteReturn;
    }

    if (adminBookingLateFeePreview instanceof HTMLElement) {
      adminBookingLateFeePreview.hidden = !canCompleteReturn;
    }

    if (adminBookingTrackAction instanceof HTMLElement) {
      adminBookingTrackAction.hidden = !canTrack;

      if (!canTrack) {
        adminBookingTrackAction.classList.remove("is-disabled");
        adminBookingTrackAction.removeAttribute("aria-disabled");
        adminBookingTrackAction.removeAttribute("tabindex");
      } else if (isTrackDisabled) {
        adminBookingTrackAction.classList.add("is-disabled");
        adminBookingTrackAction.setAttribute("aria-disabled", "true");
        adminBookingTrackAction.setAttribute("tabindex", "-1");
      } else {
        adminBookingTrackAction.classList.remove("is-disabled");
        adminBookingTrackAction.removeAttribute("aria-disabled");
        adminBookingTrackAction.removeAttribute("tabindex");

        adminBookingTrackAction.setAttribute(
          "data-booking-track-number",
          bookingDisplayId,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-customer",
          customerName,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-location",
          "Unavailable",
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-pickup",
          trackPickupLocation,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-return",
          trackReturnLocation,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-return-risk-label",
          trackRiskLabel,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-return-risk-percent",
          trackRiskPercent,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-risk-label",
          trackRiskLabel,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-risk-percent",
          trackRiskPercent,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-safety-score",
          trackSafetyScore,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-safety-label",
          trackSafetyLabel,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-map-url",
          trackMapUrl,
        );
        adminBookingTrackAction.setAttribute(
          "data-booking-track-has-signal",
          trackHasSignal ? "true" : "false",
        );
        if (trackLatitude !== null) {
          adminBookingTrackAction.setAttribute(
            "data-booking-track-latitude",
            String(trackLatitude),
          );
        } else {
          adminBookingTrackAction.removeAttribute(
            "data-booking-track-latitude",
          );
        }

        if (trackLongitude !== null) {
          adminBookingTrackAction.setAttribute(
            "data-booking-track-longitude",
            String(trackLongitude),
          );
        } else {
          adminBookingTrackAction.removeAttribute(
            "data-booking-track-longitude",
          );
        }
      }
    }

    if (adminBookingApproveIdInput instanceof HTMLInputElement) {
      adminBookingApproveIdInput.value = bookingId > 0 ? String(bookingId) : "";
    }

    if (adminBookingApproveForm instanceof HTMLElement) {
      adminBookingApproveForm.hidden = !canApproveCancellation;
    }

    if (adminBookingApproveAction instanceof HTMLButtonElement) {
      adminBookingApproveAction.disabled = !canApproveCancellation;
    }

    if (adminBookingDeleteAction instanceof HTMLElement) {
      adminBookingDeleteAction.hidden = !canDelete;
      adminBookingDeleteAction.setAttribute(
        "data-delete-booking-id",
        bookingId > 0 ? String(bookingId) : "",
      );
      adminBookingDeleteAction.setAttribute(
        "data-delete-booking-label",
        bookingDisplayId,
      );
    }

    bookingReadContext = {
      scheduledReturnDatetime,
      durationPrice: Number.isFinite(durationPrice) ? durationPrice : 0,
      dropCharge: Number.isFinite(dropCharge) ? dropCharge : 0,
      taxRate: 0.13,
      allowLateFeePreview: canCompleteReturn,
    };

    if (canCompleteReturn) {
      updateBookingLateFeePreview();
    } else if (adminBookingLateFeePreview instanceof HTMLElement) {
      adminBookingLateFeePreview.textContent = lateFeeNotApplicable
        ? "Late fee: N/A"
        : `Late fee: ${formatBookingCurrency(existingLateFee)}`;
      adminBookingLateFeePreview.hidden = true;
    }
  };

  const hydrateBookingTrackModal = (triggerButton) => {
    if (!(triggerButton instanceof HTMLElement)) {
      return;
    }

    const trackNumber = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-number"),
      "#N/A",
    );
    const trackCustomer = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-customer"),
      "Unknown",
    );
    const trackLocation = "Unavailable";
    const trackPickup = "Unavailable";
    const trackReturn = "Unavailable";
    const trackReturnRiskLabel = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-return-risk-label"),
      normalizeTrackText(
        triggerButton.getAttribute("data-booking-track-risk-label"),
      ),
    );
    const trackReturnRiskPercent = String(
      triggerButton.getAttribute("data-booking-track-return-risk-percent") ||
        triggerButton.getAttribute("data-booking-track-risk-percent") ||
        "",
    ).trim();
    const trackRiskLabel = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-risk-label"),
    );
    const trackRiskPercent = String(
      triggerButton.getAttribute("data-booking-track-risk-percent") || "",
    ).trim();
    const trackSafetyScore = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-safety-score"),
    );
    const trackSafetyLabel = normalizeTrackText(
      triggerButton.getAttribute("data-booking-track-safety-label"),
      "",
    );
    const trackMapUrl = String(
      triggerButton.getAttribute("data-booking-track-map-url") || "",
    ).trim();
    const hasSignal = readBookingBoolean(
      triggerButton,
      "data-booking-track-has-signal",
    );

    if (adminBookingTrackNumberNode instanceof HTMLElement) {
      adminBookingTrackNumberNode.textContent = trackNumber;
    }

    if (adminBookingTrackCustomerNode instanceof HTMLElement) {
      adminBookingTrackCustomerNode.textContent = trackCustomer;
    }

    if (adminBookingTrackLocationLabelNode instanceof HTMLElement) {
      adminBookingTrackLocationLabelNode.textContent = trackLocation;
    }

    if (adminBookingTrackPickupLocationNode instanceof HTMLElement) {
      adminBookingTrackPickupLocationNode.textContent = trackPickup;
    }

    if (adminBookingTrackReturnLocationNode instanceof HTMLElement) {
      adminBookingTrackReturnLocationNode.textContent = trackReturn;
    }

    if (adminBookingTrackRiskLabelNode instanceof HTMLElement) {
      adminBookingTrackRiskLabelNode.textContent = trackRiskLabel;
      adminBookingTrackRiskLabelNode.classList.remove(
        "is-high",
        "is-moderate",
        "is-low",
      );

      const riskClass = normalizeTrackRiskClass(trackRiskLabel);
      if (riskClass !== "") {
        adminBookingTrackRiskLabelNode.classList.add(riskClass);
      }
    }

    if (adminBookingTrackRiskPercentNode instanceof HTMLElement) {
      adminBookingTrackRiskPercentNode.textContent =
        trackRiskPercent !== "" ? `${trackRiskPercent}%` : "";
      adminBookingTrackRiskPercentNode.classList.remove(
        "is-high",
        "is-moderate",
        "is-low",
      );

      const riskClass = normalizeTrackRiskClass(trackRiskLabel);
      if (trackRiskPercent !== "" && riskClass !== "") {
        adminBookingTrackRiskPercentNode.classList.add(riskClass);
      }
    }

    if (adminBookingTrackReturnRiskLabelNode instanceof HTMLElement) {
      adminBookingTrackReturnRiskLabelNode.textContent = trackReturnRiskLabel;
      adminBookingTrackReturnRiskLabelNode.classList.remove(
        "is-high",
        "is-moderate",
        "is-low",
      );

      const riskClass = normalizeTrackRiskClass(trackReturnRiskLabel);
      if (riskClass !== "") {
        adminBookingTrackReturnRiskLabelNode.classList.add(riskClass);
      }
    }

    if (adminBookingTrackReturnRiskPercentNode instanceof HTMLElement) {
      adminBookingTrackReturnRiskPercentNode.textContent =
        trackReturnRiskPercent !== "" ? `${trackReturnRiskPercent}%` : "";
      adminBookingTrackReturnRiskPercentNode.classList.remove(
        "is-high",
        "is-moderate",
        "is-low",
      );

      const riskClass = normalizeTrackRiskClass(trackReturnRiskLabel);
      if (trackReturnRiskPercent !== "" && riskClass !== "") {
        adminBookingTrackReturnRiskPercentNode.classList.add(riskClass);
      }
    }

    if (adminBookingTrackSafetyScoreNode instanceof HTMLElement) {
      adminBookingTrackSafetyScoreNode.textContent = trackSafetyScore;
    }

    if (adminBookingTrackSafetyLabelNode instanceof HTMLElement) {
      adminBookingTrackSafetyLabelNode.textContent = trackSafetyLabel;
      adminBookingTrackSafetyLabelNode.classList.remove(
        "is-high",
        "is-moderate",
        "is-low",
      );

      const safetyClass = normalizeTrackSafetyClass(trackSafetyLabel);
      if (safetyClass !== "") {
        adminBookingTrackSafetyLabelNode.classList.add(safetyClass);
      }
    }

    const hasMapTarget = hasSignal && trackMapUrl !== "";
    if (adminBookingTrackOpenMapsAction instanceof HTMLAnchorElement) {
      if (hasMapTarget) {
        adminBookingTrackOpenMapsAction.href = trackMapUrl;
        adminBookingTrackOpenMapsAction.classList.remove("is-disabled");
        adminBookingTrackOpenMapsAction.removeAttribute("aria-disabled");
        adminBookingTrackOpenMapsAction.removeAttribute("tabindex");
      } else {
        adminBookingTrackOpenMapsAction.href = "#";
        adminBookingTrackOpenMapsAction.classList.add("is-disabled");
        adminBookingTrackOpenMapsAction.setAttribute("aria-disabled", "true");
        adminBookingTrackOpenMapsAction.setAttribute("tabindex", "-1");
      }
    }

    if (adminBookingTrackMapEmptyNode instanceof HTMLElement) {
      adminBookingTrackMapEmptyNode.hidden = hasSignal;
    }

    if (adminBookingTrackRouteNode instanceof HTMLElement) {
      adminBookingTrackRouteNode.hidden = !hasSignal;
    }

    if (adminBookingTrackPickupPinNode instanceof HTMLElement) {
      adminBookingTrackPickupPinNode.hidden = !hasSignal;
    }

    if (adminBookingTrackReturnPinNode instanceof HTMLElement) {
      adminBookingTrackReturnPinNode.hidden = !hasSignal;
    }
  };

  const hydrateDeleteBookingModal = (triggerButton) => {
    if (!(triggerButton instanceof HTMLElement)) {
      return;
    }

    const bookingId = Number.parseInt(
      triggerButton.getAttribute("data-delete-booking-id") ||
        triggerButton.getAttribute("data-booking-id") ||
        "0",
      10,
    );
    const bookingLabel =
      triggerButton.getAttribute("data-delete-booking-label") ||
      triggerButton.getAttribute("data-booking-display-id") ||
      "this booking";

    if (adminDeleteBookingIdInput instanceof HTMLInputElement) {
      adminDeleteBookingIdInput.value =
        Number.isFinite(bookingId) && bookingId > 0 ? String(bookingId) : "";
    }

    if (adminDeleteBookingNameNode instanceof HTMLElement) {
      adminDeleteBookingNameNode.textContent = bookingLabel;
    }
  };

  // maintenance edit/fillup form: populate create modal with currently selected available vehicle context.
  const resolveMaintenanceContext = (triggerButton = null) => {
    if (triggerButton instanceof HTMLElement) {
      const triggerVehicleId = Number.parseInt(
        triggerButton.getAttribute("data-read-vehicle-id") || "0",
        10,
      );
      const triggerIssue =
        triggerButton.getAttribute("data-read-maintenance-issue") || "";
      const triggerWorkshop =
        triggerButton.getAttribute("data-read-maintenance-workshop") || "";
      const triggerEstimate =
        triggerButton.getAttribute("data-read-maintenance-estimate") || "";
      const triggerCost =
        triggerButton.getAttribute("data-read-maintenance-cost") || "";

      if (
        triggerVehicleId > 0 ||
        triggerIssue ||
        triggerWorkshop ||
        triggerEstimate ||
        triggerCost
      ) {
        return {
          vehicleId: triggerVehicleId,
          vehicleStatus: (
            triggerButton.getAttribute("data-read-vehicle-status") ||
            "maintenance"
          ).toLowerCase(),
          fleetMode:
            triggerButton.getAttribute("data-delete-fleet-mode") || "status",
          fleetType:
            triggerButton.getAttribute("data-delete-fleet-type") || "cars",
          fleetStatus:
            triggerButton.getAttribute("data-delete-fleet-status") ||
            "maintenance",
          maintenanceIssueDescription: triggerIssue,
          maintenanceWorkshop: triggerWorkshop,
          maintenanceEstimateDatetime: triggerEstimate,
          maintenanceCost: triggerCost,
        };
      }
    }

    return readModalContext;
  };

  // maintenance edit/fillup form: populate create modal with currently selected available vehicle context.
  const hydrateMaintenanceFillModal = (triggerButton = null) => {
    const maintenanceContext = resolveMaintenanceContext(triggerButton);
    if (!maintenanceContext) {
      return;
    }

    if (adminMaintenanceFillForm instanceof HTMLFormElement) {
      adminMaintenanceFillForm.reset();
    }

    if (adminMaintenanceFillVehicleIdInput instanceof HTMLInputElement) {
      adminMaintenanceFillVehicleIdInput.value = String(
        maintenanceContext.vehicleId || "",
      );
    }

    if (adminMaintenanceFillFleetModeInput instanceof HTMLInputElement) {
      adminMaintenanceFillFleetModeInput.value =
        maintenanceContext.fleetMode || "type";
    }

    if (adminMaintenanceFillFleetTypeInput instanceof HTMLInputElement) {
      adminMaintenanceFillFleetTypeInput.value =
        maintenanceContext.fleetType || "cars";
    }

    if (adminMaintenanceFillFleetStatusInput instanceof HTMLInputElement) {
      adminMaintenanceFillFleetStatusInput.value =
        maintenanceContext.fleetStatus || "maintenance";
    }

    if (adminMaintenanceFillIssueInput instanceof HTMLInputElement) {
      adminMaintenanceFillIssueInput.value =
        maintenanceContext.maintenanceIssueDescription || "";
    }

    if (adminMaintenanceFillWorkshopInput instanceof HTMLInputElement) {
      adminMaintenanceFillWorkshopInput.value =
        maintenanceContext.maintenanceWorkshop || "";
    }

    if (adminMaintenanceFillEstimateInput instanceof HTMLInputElement) {
      adminMaintenanceFillEstimateInput.value = toDateInputValue(
        maintenanceContext.maintenanceEstimateDatetime || "",
      );
    }

    if (adminMaintenanceFillCostInput instanceof HTMLInputElement) {
      adminMaintenanceFillCostInput.value =
        maintenanceContext.maintenanceCost || "";
    }
  };

  // maintenance edit/fillup form: populate maintenance edit modal and complete-service form from read modal context.
  const hydrateMaintenanceEditModal = (triggerButton = null) => {
    const maintenanceContext = resolveMaintenanceContext(triggerButton);
    if (!maintenanceContext) {
      return;
    }

    if (adminMaintenanceEditVehicleIdInput instanceof HTMLInputElement) {
      adminMaintenanceEditVehicleIdInput.value = String(
        maintenanceContext.vehicleId || "",
      );
    }

    if (adminMaintenanceEditFleetModeInput instanceof HTMLInputElement) {
      adminMaintenanceEditFleetModeInput.value =
        maintenanceContext.fleetMode || "status";
    }

    if (adminMaintenanceEditFleetTypeInput instanceof HTMLInputElement) {
      adminMaintenanceEditFleetTypeInput.value =
        maintenanceContext.fleetType || "cars";
    }

    if (adminMaintenanceEditFleetStatusInput instanceof HTMLInputElement) {
      adminMaintenanceEditFleetStatusInput.value =
        maintenanceContext.fleetStatus || "maintenance";
    }

    if (adminMaintenanceEditIssueInput instanceof HTMLInputElement) {
      adminMaintenanceEditIssueInput.value =
        maintenanceContext.maintenanceIssueDescription || "";
    }

    if (adminMaintenanceEditWorkshopInput instanceof HTMLInputElement) {
      adminMaintenanceEditWorkshopInput.value =
        maintenanceContext.maintenanceWorkshop || "";
    }

    if (adminMaintenanceEditEstimateInput instanceof HTMLInputElement) {
      adminMaintenanceEditEstimateInput.value = toDateInputValue(
        maintenanceContext.maintenanceEstimateDatetime || "",
      );
    }

    if (adminMaintenanceEditCostInput instanceof HTMLInputElement) {
      adminMaintenanceEditCostInput.value =
        maintenanceContext.maintenanceCost || "";
    }

    if (adminMaintenanceCompleteVehicleIdInput instanceof HTMLInputElement) {
      adminMaintenanceCompleteVehicleIdInput.value = String(
        maintenanceContext.vehicleId || "",
      );
    }

    if (adminMaintenanceCompleteFleetModeInput instanceof HTMLInputElement) {
      adminMaintenanceCompleteFleetModeInput.value =
        maintenanceContext.fleetMode || "status";
    }

    if (adminMaintenanceCompleteFleetTypeInput instanceof HTMLInputElement) {
      adminMaintenanceCompleteFleetTypeInput.value =
        maintenanceContext.fleetType || "cars";
    }

    if (adminMaintenanceCompleteFleetStatusInput instanceof HTMLInputElement) {
      adminMaintenanceCompleteFleetStatusInput.value =
        maintenanceContext.fleetStatus || "maintenance";
    }

    readModalContext = maintenanceContext;
  };

  const closeCreateCustomSelect = (controller = null) => {
    const targetController = controller || openCreateCustomSelectController;
    if (!targetController) {
      return;
    }

    targetController.shell.classList.remove("is-open");
    targetController.shell.classList.remove("is-drop-up");
    targetController.list.hidden = true;
    targetController.list.style.maxHeight = "";
    targetController.trigger.setAttribute("aria-expanded", "false");

    if (openCreateCustomSelectController === targetController) {
      openCreateCustomSelectController = null;
    }
  };

  const updateCreateCustomSelectViewport = (controller) => {
    if (!controller) {
      return;
    }

    const dialog = controller.shell.closest(".menu-modal__dialog");
    const triggerRect = controller.trigger.getBoundingClientRect();
    const dialogRect = dialog ? dialog.getBoundingClientRect() : null;

    const viewportTop = 0;
    const viewportBottom = window.innerHeight;
    const boundaryTop = dialogRect
      ? Math.max(dialogRect.top, viewportTop)
      : viewportTop;
    const boundaryBottom = dialogRect
      ? Math.min(dialogRect.bottom, viewportBottom)
      : viewportBottom;

    const spacing = 10;
    const preferredMaxHeight = 220;
    const minimumUsableHeight = 72;
    const availableBelow = Math.floor(
      boundaryBottom - triggerRect.bottom - spacing,
    );
    const availableAbove = Math.floor(triggerRect.top - boundaryTop - spacing);
    const shouldOpenUpward =
      availableBelow < 140 && availableAbove > availableBelow;

    const availableSpace = shouldOpenUpward ? availableAbove : availableBelow;
    const cappedHeight = Math.max(
      minimumUsableHeight,
      Math.min(preferredMaxHeight, availableSpace),
    );

    controller.shell.classList.toggle("is-drop-up", shouldOpenUpward);
    controller.list.style.maxHeight = `${cappedHeight}px`;
  };

  const syncCreateCustomSelect = (controller) => {
    if (!controller) {
      return;
    }

    const selectedOption =
      controller.nativeSelect.options[controller.nativeSelect.selectedIndex] ||
      controller.nativeSelect.options[0] ||
      null;
    const selectedValue = selectedOption ? selectedOption.value : "";
    const selectedLabel = selectedOption
      ? String(selectedOption.textContent || "").trim()
      : "";

    controller.value.textContent = selectedLabel;

    controller.items.forEach((item) => {
      const isSelected = item.dataset.value === selectedValue;
      item.classList.toggle("is-selected", isSelected);
      item.setAttribute("aria-selected", isSelected ? "true" : "false");
    });
  };

  const syncAllCreateCustomSelects = () => {
    createCustomSelectControllers.forEach((controller) => {
      syncCreateCustomSelect(controller);
    });
  };

  const initCreateCustomSelects = () => {
    if (adminCreateSelectInputs.length === 0) {
      return;
    }

    adminCreateSelectInputs.forEach((nativeSelect) => {
      if (
        !(nativeSelect instanceof HTMLSelectElement) ||
        nativeSelect.dataset.customSelectReady === "true"
      ) {
        return;
      }

      nativeSelect.dataset.customSelectReady = "true";
      nativeSelect.classList.add("admin-create-vehicle-modal__native-select");

      const shell = document.createElement("div");
      shell.className = "admin-create-vehicle-modal__select-shell";

      const trigger = document.createElement("button");
      trigger.type = "button";
      trigger.className = "admin-create-vehicle-modal__select-control";
      trigger.setAttribute("aria-haspopup", "listbox");
      trigger.setAttribute("aria-expanded", "false");

      const value = document.createElement("span");
      value.className = "admin-create-vehicle-modal__select-value";

      const icon = document.createElement("span");
      icon.className =
        "material-symbols-rounded admin-create-vehicle-modal__select-icon";
      icon.setAttribute("aria-hidden", "true");
      icon.textContent = "expand_more";

      trigger.append(value, icon);

      const list = document.createElement("ul");
      list.className = "admin-create-vehicle-modal__select-options";
      list.setAttribute("role", "listbox");
      list.hidden = true;

      const controller = {
        nativeSelect,
        shell,
        trigger,
        value,
        list,
        items: [],
      };

      Array.from(nativeSelect.options).forEach((option) => {
        const optionItem = document.createElement("li");

        const optionButton = document.createElement("button");
        optionButton.type = "button";
        optionButton.className = "admin-create-vehicle-modal__select-option";
        optionButton.dataset.value = option.value;
        optionButton.setAttribute("role", "option");
        optionButton.textContent = String(option.textContent || option.value);

        optionButton.addEventListener("click", () => {
          nativeSelect.value = option.value;
          nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));
          syncCreateCustomSelect(controller);
          closeCreateCustomSelect(controller);
        });

        optionItem.appendChild(optionButton);
        list.appendChild(optionItem);
        controller.items.push(optionButton);
      });

      trigger.addEventListener("click", () => {
        const isOpen = shell.classList.contains("is-open");
        if (isOpen) {
          closeCreateCustomSelect(controller);
          return;
        }

        if (openCreateCustomSelectController) {
          closeCreateCustomSelect(openCreateCustomSelectController);
        }

        shell.classList.add("is-open");
        list.hidden = false;
        updateCreateCustomSelectViewport(controller);
        trigger.setAttribute("aria-expanded", "true");
        openCreateCustomSelectController = controller;
      });

      nativeSelect.addEventListener("change", () => {
        syncCreateCustomSelect(controller);
      });

      shell.append(trigger, list);
      nativeSelect.insertAdjacentElement("afterend", shell);

      createCustomSelectControllers.push(controller);
      syncCreateCustomSelect(controller);
    });
  };

  // create vehicle modal: manage 3-step navigation, step validation, and file-label hydration.
  const getCreateVehicleStepTitle = (step) =>
    step <= 1 ? "Create Vehicle" : `Create Vehicle Part ${step}`;

  const getCreateVehicleStepPanel = (step) => {
    if (adminCreateStepPanels.length === 0) {
      return null;
    }

    return (
      adminCreateStepPanels.find((panel) => {
        const panelStep = Number.parseInt(
          panel.getAttribute("data-create-step-panel") || "0",
          10,
        );
        return panelStep === step;
      }) || null
    );
  };

  const setCreateVehicleStep = (requestedStep) => {
    if (adminCreateStepPanels.length === 0) {
      return;
    }

    const boundedStep = Math.min(
      Math.max(Number.parseInt(String(requestedStep), 10) || 1, 1),
      adminCreateStepPanels.length,
    );

    createVehicleStep = boundedStep;

    adminCreateStepPanels.forEach((panel) => {
      const panelStep = Number.parseInt(
        panel.getAttribute("data-create-step-panel") || "0",
        10,
      );
      const isActive = panelStep === boundedStep;
      panel.hidden = !isActive;
      panel.classList.toggle("is-active", isActive);
    });

    if (adminCreateStepLabel instanceof HTMLElement) {
      adminCreateStepLabel.textContent = getCreateVehicleStepTitle(boundedStep);
    }
  };

  const validateCreateVehicleStep = (step) => {
    const panel = getCreateVehicleStepPanel(step);
    if (!(panel instanceof HTMLElement)) {
      return true;
    }

    const requiredControls = Array.from(
      panel.querySelectorAll(
        "input[required], select[required], textarea[required]",
      ),
    );

    for (const control of requiredControls) {
      if (
        control instanceof HTMLInputElement &&
        control.type === "file" &&
        control.required &&
        control.files &&
        control.files.length === 0
      ) {
        control.reportValidity();
        return false;
      }

      if (
        typeof control.checkValidity === "function" &&
        !control.checkValidity()
      ) {
        control.reportValidity();
        return false;
      }
    }

    return true;
  };

  const updateCreateImageUi = () => {
    const imageName =
      adminCreateImageInput instanceof HTMLInputElement &&
      adminCreateImageInput.files &&
      adminCreateImageInput.files.length > 0
        ? adminCreateImageInput.files[0].name
        : "";

    if (adminCreateImageNameInput instanceof HTMLInputElement) {
      adminCreateImageNameInput.value = imageName;
    }

    if (adminCreateImageClearButton instanceof HTMLElement) {
      adminCreateImageClearButton.hidden = imageName === "";
    }
  };

  const resetCreateVehicleModal = () => {
    closeCreateCustomSelect();

    if (adminCreateVehicleForm instanceof HTMLFormElement) {
      adminCreateVehicleForm.reset();
    }

    if (adminCreateFleetModeInput instanceof HTMLInputElement) {
      adminCreateFleetModeInput.value = "type";
    }

    if (adminCreateFleetTypeInput instanceof HTMLInputElement) {
      adminCreateFleetTypeInput.value = "cars";
    }

    if (adminCreateVehicleTypeInput instanceof HTMLSelectElement) {
      adminCreateVehicleTypeInput.value = "cars";
    }

    syncAllCreateCustomSelects();
    setCreateVehicleStep(1);
    updateCreateImageUi();
  };

  const hydrateCreateVehicleModal = (triggerButton) => {
    resetCreateVehicleModal();

    if (!(triggerButton instanceof HTMLElement)) {
      return;
    }

    const fleetMode =
      triggerButton.getAttribute("data-create-fleet-mode") || "type";
    const fleetType =
      triggerButton.getAttribute("data-create-fleet-type") || "cars";
    const vehicleType =
      triggerButton.getAttribute("data-create-vehicle-type") || fleetType;

    if (adminCreateFleetModeInput instanceof HTMLInputElement) {
      adminCreateFleetModeInput.value = fleetMode;
    }

    if (adminCreateFleetTypeInput instanceof HTMLInputElement) {
      adminCreateFleetTypeInput.value = fleetType;
    }

    if (adminCreateVehicleTypeInput instanceof HTMLSelectElement) {
      adminCreateVehicleTypeInput.value = vehicleType;
      if (adminCreateFleetTypeInput instanceof HTMLInputElement) {
        adminCreateFleetTypeInput.value = vehicleType;
      }
    }

    syncAllCreateCustomSelects();
  };

  // edit vehicle modal: resolve trigger context, hydrate 3-step form state, and keep image/select UI in sync.
  const getFileNameFromPath = (rawPath) => {
    const normalized = String(rawPath || "").trim();
    if (normalized === "") {
      return "";
    }

    const segments = normalized.split(/[\\/]+/);
    return segments.length > 0 ? segments[segments.length - 1] : normalized;
  };

  const normalizeEditVehicleStatus = (rawStatus) => {
    const normalized = String(rawStatus || "available")
      .trim()
      .toLowerCase();
    if (Object.prototype.hasOwnProperty.call(readStatusLabels, normalized)) {
      return normalized;
    }

    return "available";
  };

  const resolveEditVehicleContext = (triggerButton = null) => {
    if (triggerButton instanceof HTMLElement) {
      const triggerVehicleId = Number.parseInt(
        triggerButton.getAttribute("data-edit-vehicle-id") ||
          triggerButton.getAttribute("data-read-vehicle-id") ||
          "0",
        10,
      );
      const triggerFullName =
        triggerButton.getAttribute("data-edit-vehicle-full-name") ||
        triggerButton.getAttribute("data-read-vehicle-full-name") ||
        "";
      const triggerShortName =
        triggerButton.getAttribute("data-edit-vehicle-short-name") ||
        triggerButton.getAttribute("data-read-vehicle-name") ||
        "";
      const triggerLicense =
        triggerButton.getAttribute("data-edit-vehicle-license") ||
        triggerButton.getAttribute("data-edit-vehicle-license-plate") ||
        triggerButton.getAttribute("data-read-vehicle-plate") ||
        "";

      if (
        triggerVehicleId > 0 ||
        triggerFullName ||
        triggerShortName ||
        triggerLicense
      ) {
        return {
          vehicleId: triggerVehicleId,
          vehicleStatus: normalizeEditVehicleStatus(
            triggerButton.getAttribute("data-edit-vehicle-status") ||
              triggerButton.getAttribute("data-read-vehicle-status") ||
              "available",
          ),
          fleetMode:
            triggerButton.getAttribute("data-delete-fleet-mode") || "type",
          fleetType:
            triggerButton.getAttribute("data-delete-fleet-type") || "cars",
          fleetStatus:
            triggerButton.getAttribute("data-delete-fleet-status") ||
            "available",
          vehicleType:
            triggerButton.getAttribute("data-edit-vehicle-type") ||
            triggerButton.getAttribute("data-read-vehicle-type") ||
            "cars",
          vehicleFullName: triggerFullName,
          vehicleShortName: triggerShortName,
          vehiclePricePerDay:
            triggerButton.getAttribute("data-edit-vehicle-price") ||
            triggerButton.getAttribute("data-read-vehicle-price") ||
            "",
          vehicleDriverAge:
            triggerButton.getAttribute("data-edit-vehicle-driver-age") ||
            triggerButton.getAttribute("data-read-vehicle-age") ||
            "",
          vehicleSeats:
            triggerButton.getAttribute("data-edit-vehicle-seats") ||
            triggerButton.getAttribute("data-read-vehicle-seats") ||
            "",
          vehicleTransmission:
            triggerButton.getAttribute("data-edit-vehicle-transmission") ||
            triggerButton.getAttribute("data-read-vehicle-transmission") ||
            "manual",
          vehicleFuel:
            triggerButton.getAttribute("data-edit-vehicle-fuel") ||
            triggerButton.getAttribute("data-read-vehicle-fuel") ||
            "petrol",
          vehicleLicensePlate: triggerLicense,
          vehicleGpsId:
            triggerButton.getAttribute("data-edit-vehicle-gps-id") ||
            triggerButton.getAttribute("data-read-vehicle-gps-id") ||
            "",
          vehicleLastServiceDate:
            triggerButton.getAttribute("data-edit-vehicle-last-service") ||
            triggerButton.getAttribute("data-read-vehicle-last-service") ||
            "",
          vehicleDescription:
            triggerButton.getAttribute("data-edit-vehicle-description") ||
            triggerButton.getAttribute("data-read-vehicle-description") ||
            "",
          vehicleImagePath:
            triggerButton.getAttribute("data-edit-vehicle-image") ||
            triggerButton.getAttribute("data-read-vehicle-image") ||
            "",
        };
      }
    }

    return readModalContext;
  };

  const getEditVehicleStepPanel = (step) => {
    if (adminEditStepPanels.length === 0) {
      return null;
    }

    return (
      adminEditStepPanels.find((panel) => {
        const panelStep = Number.parseInt(
          panel.getAttribute("data-edit-step-panel") || "0",
          10,
        );
        return panelStep === step;
      }) || null
    );
  };

  const setEditVehicleStep = (requestedStep) => {
    if (adminEditStepPanels.length === 0) {
      return;
    }

    const boundedStep = Math.min(
      Math.max(Number.parseInt(String(requestedStep), 10) || 1, 1),
      adminEditStepPanels.length,
    );

    editVehicleStep = boundedStep;

    adminEditStepPanels.forEach((panel) => {
      const panelStep = Number.parseInt(
        panel.getAttribute("data-edit-step-panel") || "0",
        10,
      );
      const isActive = panelStep === boundedStep;
      panel.hidden = !isActive;
      panel.classList.toggle("is-active", isActive);
    });
  };

  const validateEditVehicleStep = (step) => {
    const panel = getEditVehicleStepPanel(step);
    if (!(panel instanceof HTMLElement)) {
      return true;
    }

    const requiredControls = Array.from(
      panel.querySelectorAll(
        "input[required], select[required], textarea[required]",
      ),
    );

    for (const control of requiredControls) {
      if (
        typeof control.checkValidity === "function" &&
        !control.checkValidity()
      ) {
        control.reportValidity();
        return false;
      }
    }

    return true;
  };

  const updateEditImageUi = () => {
    const selectedImageName =
      adminEditImageInput instanceof HTMLInputElement &&
      adminEditImageInput.files &&
      adminEditImageInput.files.length > 0
        ? adminEditImageInput.files[0].name
        : "";
    const currentImageName = getFileNameFromPath(
      adminEditCurrentImagePathInput instanceof HTMLInputElement
        ? adminEditCurrentImagePathInput.value
        : "",
    );

    if (adminEditImageNameInput instanceof HTMLInputElement) {
      adminEditImageNameInput.value =
        selectedImageName || currentImageName || "Keep current image";
    }

    if (adminEditImageClearButton instanceof HTMLElement) {
      adminEditImageClearButton.hidden = selectedImageName === "";
    }
  };

  const resetEditVehicleModal = () => {
    closeCreateCustomSelect();

    if (adminEditVehicleForm instanceof HTMLFormElement) {
      adminEditVehicleForm.reset();
    }

    if (adminEditCurrentImagePathInput instanceof HTMLInputElement) {
      adminEditCurrentImagePathInput.value = "";
    }

    if (adminEditFleetModeInput instanceof HTMLInputElement) {
      adminEditFleetModeInput.value = "type";
    }

    if (adminEditFleetTypeInput instanceof HTMLInputElement) {
      adminEditFleetTypeInput.value = "cars";
    }

    if (adminEditFleetStatusInput instanceof HTMLInputElement) {
      adminEditFleetStatusInput.value = "available";
    }

    if (adminEditVehicleTypeInput instanceof HTMLSelectElement) {
      adminEditVehicleTypeInput.value = "cars";
    }

    if (adminEditDriverAgeInput instanceof HTMLSelectElement) {
      adminEditDriverAgeInput.value = "18";
    }

    if (adminEditStatusInput instanceof HTMLSelectElement) {
      adminEditStatusInput.value = "available";
    }

    if (adminEditImageInput instanceof HTMLInputElement) {
      adminEditImageInput.value = "";
    }

    syncAllCreateCustomSelects();
    setEditVehicleStep(1);
    updateEditImageUi();
  };

  const hydrateEditVehicleModal = (triggerButton = null) => {
    const editContext = resolveEditVehicleContext(triggerButton);
    if (!editContext) {
      return;
    }

    resetEditVehicleModal();

    const normalizedVehicleType =
      String(editContext.vehicleType || "cars").toLowerCase() || "cars";
    const normalizedVehicleStatus = normalizeEditVehicleStatus(
      editContext.vehicleStatus,
    );
    const normalizedDriverAge = String(
      editContext.vehicleDriverAge || "18",
    ).trim();
    const sanitizedDriverAge =
      normalizedDriverAge === "21" || normalizedDriverAge === "18"
        ? normalizedDriverAge
        : "18";

    if (adminEditVehicleIdInput instanceof HTMLInputElement) {
      adminEditVehicleIdInput.value = String(editContext.vehicleId || "");
    }

    if (adminEditFleetModeInput instanceof HTMLInputElement) {
      adminEditFleetModeInput.value = editContext.fleetMode || "type";
    }

    if (adminEditFleetTypeInput instanceof HTMLInputElement) {
      adminEditFleetTypeInput.value = editContext.fleetType || "cars";
    }

    if (adminEditFleetStatusInput instanceof HTMLInputElement) {
      adminEditFleetStatusInput.value =
        editContext.fleetStatus || normalizedVehicleStatus;
    }

    if (adminEditVehicleTypeInput instanceof HTMLSelectElement) {
      adminEditVehicleTypeInput.value = normalizedVehicleType;
      if (adminEditFleetTypeInput instanceof HTMLInputElement) {
        adminEditFleetTypeInput.value = normalizedVehicleType;
      }
    }

    if (adminEditFullNameInput instanceof HTMLInputElement) {
      adminEditFullNameInput.value = editContext.vehicleFullName || "";
    }

    if (adminEditShortNameInput instanceof HTMLInputElement) {
      adminEditShortNameInput.value = editContext.vehicleShortName || "";
    }

    if (adminEditPriceInput instanceof HTMLInputElement) {
      adminEditPriceInput.value = editContext.vehiclePricePerDay || "";
    }

    if (adminEditDriverAgeInput instanceof HTMLSelectElement) {
      adminEditDriverAgeInput.value = sanitizedDriverAge;
    }

    if (adminEditSeatsInput instanceof HTMLInputElement) {
      adminEditSeatsInput.value = editContext.vehicleSeats || "";
    }

    if (adminEditTransmissionInput instanceof HTMLSelectElement) {
      adminEditTransmissionInput.value =
        editContext.vehicleTransmission || "manual";
    }

    if (adminEditFuelInput instanceof HTMLSelectElement) {
      adminEditFuelInput.value = editContext.vehicleFuel || "petrol";
    }

    if (adminEditLicenseInput instanceof HTMLInputElement) {
      adminEditLicenseInput.value = editContext.vehicleLicensePlate || "";
    }

    if (adminEditGpsInput instanceof HTMLInputElement) {
      adminEditGpsInput.value = editContext.vehicleGpsId || "";
    }

    if (adminEditStatusInput instanceof HTMLSelectElement) {
      adminEditStatusInput.value = normalizedVehicleStatus;
    }

    if (adminEditLastServiceInput instanceof HTMLInputElement) {
      adminEditLastServiceInput.value = toDateInputValue(
        editContext.vehicleLastServiceDate || "",
      );
    }

    if (adminEditDescriptionInput instanceof HTMLTextAreaElement) {
      adminEditDescriptionInput.value = editContext.vehicleDescription || "";
    }

    if (adminEditCurrentImagePathInput instanceof HTMLInputElement) {
      adminEditCurrentImagePathInput.value = editContext.vehicleImagePath || "";
    }

    if (adminEditImageInput instanceof HTMLInputElement) {
      adminEditImageInput.value = "";
    }

    syncAllCreateCustomSelects();
    setEditVehicleStep(1);
    updateEditImageUi();

    readModalContext = {
      ...(readModalContext && typeof readModalContext === "object"
        ? readModalContext
        : {}),
      ...editContext,
      vehicleStatus: normalizedVehicleStatus,
      vehicleType: normalizedVehicleType,
    };
  };

  initCreateCustomSelects();

  if (adminReadEditAction instanceof HTMLElement) {
    adminReadEditAction.addEventListener("click", () => {
      const maintenanceContext = resolveMaintenanceContext(adminReadEditAction);
      const editContext = resolveEditVehicleContext(adminReadEditAction);

      if (!maintenanceContext && !editContext) {
        return;
      }

      if (maintenanceContext) {
        readModalContext = maintenanceContext;
      }

      const targetId =
        maintenanceContext?.vehicleStatus === "maintenance"
          ? "admin-maintenance-edit-modal"
          : "admin-edit-vehicle-modal";

      if (targetId === "admin-maintenance-edit-modal") {
        hydrateMaintenanceEditModal(adminReadEditAction);
      } else {
        hydrateEditVehicleModal(adminReadEditAction);
      }

      if (modalHistory.length === 0) {
        lastFocusedElement =
          document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
      }

      showModal(targetId, true);
    });
  }

  const hydrateReadVehicleModal = (triggerButton) => {
    if (!(triggerButton instanceof HTMLElement)) {
      return;
    }

    const vehicleId = Number.parseInt(
      triggerButton.getAttribute("data-read-vehicle-id") || "0",
      10,
    );
    const vehicleName =
      triggerButton.getAttribute("data-read-vehicle-name") || "Vehicle";
    const vehicleFullName =
      triggerButton.getAttribute("data-read-vehicle-full-name") || vehicleName;
    const vehicleTypeRaw = (
      triggerButton.getAttribute("data-read-vehicle-type") || "cars"
    ).toLowerCase();
    const vehicleStatusRaw = (
      triggerButton.getAttribute("data-read-vehicle-status") || "available"
    ).toLowerCase();
    const vehicleStatus = Object.prototype.hasOwnProperty.call(
      readStatusLabels,
      vehicleStatusRaw,
    )
      ? vehicleStatusRaw
      : "available";
    const vehicleImagePath =
      triggerButton.getAttribute("data-read-vehicle-image") ||
      "images/vehicle-feature.png";
    const vehiclePricePerDay =
      triggerButton.getAttribute("data-read-vehicle-price") || "";
    const vehicleDriverAge =
      triggerButton.getAttribute("data-read-vehicle-age") || "";
    const vehicleSeats =
      triggerButton.getAttribute("data-read-vehicle-seats") || "";
    const vehicleTransmission =
      triggerButton.getAttribute("data-read-vehicle-transmission") || "";
    const vehicleFuel =
      triggerButton.getAttribute("data-read-vehicle-fuel") || "";
    const vehicleLicensePlate =
      triggerButton.getAttribute("data-read-vehicle-plate") || "";
    const vehicleGpsId =
      triggerButton.getAttribute("data-read-vehicle-gps-id") || "";
    const vehicleLastServiceDate =
      triggerButton.getAttribute("data-read-vehicle-last-service") || "";
    const vehicleDescription =
      triggerButton.getAttribute("data-read-vehicle-description") || "";
    const fleetModeForReadModal =
      triggerButton.getAttribute("data-delete-fleet-mode") || "type";

    const bookingNumber =
      triggerButton.getAttribute("data-read-booking-number") || "";
    const bookingId = Number.parseInt(
      triggerButton.getAttribute("data-read-booking-id") || "0",
      10,
    );
    const bookingUserName =
      triggerButton.getAttribute("data-read-booking-user-name") || "";
    const bookingUserPhone =
      triggerButton.getAttribute("data-read-booking-user-phone") || "";
    const pickupDatetime =
      triggerButton.getAttribute("data-read-booking-pickup") || "";
    const returnDatetime =
      triggerButton.getAttribute("data-read-booking-return") || "";
    const paymentStatus =
      triggerButton.getAttribute("data-read-booking-payment-status") || "";
    const lateFee =
      triggerButton.getAttribute("data-read-booking-late-fee") || "";
    const latitude = triggerButton.getAttribute("data-read-gps-latitude") || "";
    const longitude =
      triggerButton.getAttribute("data-read-gps-longitude") || "";
    const parsedLatitude = Number.parseFloat(String(latitude).trim());
    const parsedLongitude = Number.parseFloat(String(longitude).trim());
    const hasGpsSignal =
      Number.isFinite(parsedLatitude) &&
      Number.isFinite(parsedLongitude) &&
      (Math.abs(parsedLatitude) > 0.00001 ||
        Math.abs(parsedLongitude) > 0.00001);
    const currentLocation = hasGpsSignal
      ? `${parsedLatitude.toFixed(6)}, ${parsedLongitude.toFixed(6)}`
      : "";

    const resolvedBookingNumber =
      !isReadValueMissing(bookingNumber) ||
      !Number.isFinite(bookingId) ||
      bookingId <= 0
        ? bookingNumber
        : `#BK-${String(bookingId).padStart(4, "0")}`;

    const hasBookingTimelineData =
      !isReadValueMissing(resolvedBookingNumber) ||
      !isReadValueMissing(pickupDatetime) ||
      !isReadValueMissing(returnDatetime) ||
      !isReadValueMissing(paymentStatus);
    const resolvedVehicleStatus =
      readUserStatuses.includes(vehicleStatus) && !hasBookingTimelineData
        ? "available"
        : vehicleStatus;

    const details = {
      lastServiceDate: vehicleLastServiceDate,
      description: vehicleDescription,
      maintenanceIssueDescription:
        triggerButton.getAttribute("data-read-maintenance-issue") || "",
      maintenanceWorkshop:
        triggerButton.getAttribute("data-read-maintenance-workshop") || "",
      maintenanceEstimateDatetime:
        triggerButton.getAttribute("data-read-maintenance-estimate") || "",
      maintenanceCost:
        triggerButton.getAttribute("data-read-maintenance-cost") || "",
      upcomingPickupDatetime:
        triggerButton.getAttribute("data-read-upcoming-pickup") || "",
      totalReservations:
        triggerButton.getAttribute("data-read-total-reservations") || "0",
      totalEarnings:
        triggerButton.getAttribute("data-read-total-earnings") || "0",
      pickupDatetime,
      returnDatetime,
      paymentStatus,
      lateFee,
      currentLocation,
    };

    const isUserStatus = readUserStatuses.includes(resolvedVehicleStatus);

    if (adminReadBookingNumberNode instanceof HTMLElement) {
      adminReadBookingNumberNode.textContent = isUserStatus
        ? normalizeReadValue(resolvedBookingNumber, resolvedVehicleStatus, true)
        : readUserNotApplicableText;
    }

    if (
      adminReadCurrentUserLine instanceof HTMLElement &&
      adminReadCurrentUserNode instanceof HTMLElement
    ) {
      adminReadCurrentUserLine.hidden = !isUserStatus;

      if (!isUserStatus) {
        adminReadCurrentUserNode.textContent = "";
      } else {
        const userDetails = [bookingUserName, bookingUserPhone]
          .map((value) => String(value || "").trim())
          .filter((value) => value !== "");
        adminReadCurrentUserNode.textContent =
          userDetails.length > 0 ? userDetails.join(" ") : readUnavailableText;
      }
    }

    if (adminReadStatusPill instanceof HTMLElement) {
      adminReadStatusPill.textContent =
        readStatusLabels[resolvedVehicleStatus] || "Available";
      adminReadStatusPill.className = `admin-vehicle-read-modal__status-pill admin-vehicle-read-modal__status-pill--${resolvedVehicleStatus.replace("_", "-")}`;
    }

    if (adminReadMaintenanceIndicator instanceof HTMLElement) {
      const showMaintenanceIndicator =
        fleetModeForReadModal === "type" &&
        resolvedVehicleStatus === "available";
      adminReadMaintenanceIndicator.hidden = !showMaintenanceIndicator;
    }

    if (adminReadEditAction instanceof HTMLButtonElement) {
      const canEditMaintenance = resolvedVehicleStatus === "maintenance";
      adminReadEditAction.hidden = !(
        fleetModeForReadModal === "type" || canEditMaintenance
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-id",
        String(vehicleId > 0 ? vehicleId : ""),
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-type",
        vehicleTypeRaw || "cars",
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-full-name",
        vehicleFullName,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-short-name",
        vehicleName,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-price",
        vehiclePricePerDay,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-driver-age",
        vehicleDriverAge,
      );
      adminReadEditAction.setAttribute("data-edit-vehicle-seats", vehicleSeats);
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-transmission",
        vehicleTransmission,
      );
      adminReadEditAction.setAttribute("data-edit-vehicle-fuel", vehicleFuel);
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-license",
        vehicleLicensePlate,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-license-plate",
        vehicleLicensePlate,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-gps-id",
        vehicleGpsId,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-status",
        resolvedVehicleStatus,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-last-service",
        vehicleLastServiceDate,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-description",
        vehicleDescription,
      );
      adminReadEditAction.setAttribute(
        "data-edit-vehicle-image",
        vehicleImagePath,
      );
      adminReadEditAction.setAttribute(
        "data-delete-fleet-mode",
        fleetModeForReadModal,
      );
      adminReadEditAction.setAttribute(
        "data-delete-fleet-type",
        triggerButton.getAttribute("data-delete-fleet-type") || "cars",
      );
      adminReadEditAction.setAttribute(
        "data-delete-fleet-status",
        triggerButton.getAttribute("data-delete-fleet-status") ||
          resolvedVehicleStatus,
      );
    }

    if (adminReadVehicleImage instanceof HTMLImageElement) {
      adminReadVehicleImage.src = vehicleImagePath;
      adminReadVehicleImage.alt = vehicleName;
    }

    if (adminReadVehicleTypeNode instanceof HTMLElement) {
      adminReadVehicleTypeNode.textContent = toTitleCase(
        vehicleTypeRaw.replace(/s$/, ""),
      );
    }

    if (adminReadVehicleFullNameNode instanceof HTMLElement) {
      adminReadVehicleFullNameNode.textContent = vehicleFullName;
    }

    if (adminReadVehicleSeatsNode instanceof HTMLElement) {
      const seatsValue = normalizeReadValue(
        vehicleSeats,
        resolvedVehicleStatus,
        false,
      );
      adminReadVehicleSeatsNode.textContent =
        seatsValue === readUnavailableText
          ? readUnavailableText
          : `${seatsValue} Seats`;
    }

    if (adminReadVehicleTransmissionNode instanceof HTMLElement) {
      adminReadVehicleTransmissionNode.textContent = toTitleCase(
        normalizeReadValue(vehicleTransmission, resolvedVehicleStatus, false),
      );
    }

    if (adminReadVehicleAgeNode instanceof HTMLElement) {
      const ageValue = normalizeReadValue(
        vehicleDriverAge,
        resolvedVehicleStatus,
        false,
      );
      adminReadVehicleAgeNode.textContent =
        ageValue === readUnavailableText
          ? readUnavailableText
          : `${ageValue}+ Years`;
    }

    if (adminReadVehicleFuelNode instanceof HTMLElement) {
      adminReadVehicleFuelNode.textContent = toTitleCase(
        normalizeReadValue(vehicleFuel, resolvedVehicleStatus, false),
      );
    }

    if (adminReadVehiclePlateNode instanceof HTMLElement) {
      adminReadVehiclePlateNode.textContent = normalizeReadValue(
        vehicleLicensePlate,
        resolvedVehicleStatus,
        false,
      );
    }

    updateReadStatusRows(resolvedVehicleStatus, details);

    if (adminReadDeleteAction instanceof HTMLElement) {
      const canDeleteFromReadModal =
        fleetModeForReadModal === "type" &&
        resolvedVehicleStatus === "available";
      adminReadDeleteAction.hidden = !canDeleteFromReadModal;
      adminReadDeleteAction.setAttribute(
        "data-delete-vehicle-id",
        String(vehicleId > 0 ? vehicleId : ""),
      );
      adminReadDeleteAction.setAttribute(
        "data-delete-vehicle-label",
        vehicleName,
      );
      adminReadDeleteAction.setAttribute(
        "data-delete-fleet-mode",
        fleetModeForReadModal,
      );
      adminReadDeleteAction.setAttribute(
        "data-delete-fleet-type",
        triggerButton.getAttribute("data-delete-fleet-type") || "cars",
      );
      adminReadDeleteAction.setAttribute(
        "data-delete-fleet-status",
        triggerButton.getAttribute("data-delete-fleet-status") || "reserved",
      );
    }

    readModalContext = {
      vehicleId,
      vehicleStatus: resolvedVehicleStatus,
      fleetMode: fleetModeForReadModal,
      fleetType: triggerButton.getAttribute("data-delete-fleet-type") || "cars",
      fleetStatus:
        triggerButton.getAttribute("data-delete-fleet-status") || "maintenance",
      vehicleType: vehicleTypeRaw,
      vehicleFullName,
      vehicleShortName: vehicleName,
      vehiclePricePerDay,
      vehicleDriverAge,
      vehicleSeats,
      vehicleTransmission,
      vehicleFuel,
      vehicleLicensePlate,
      vehicleGpsId,
      vehicleLastServiceDate,
      vehicleDescription,
      vehicleImagePath,
      maintenanceIssueDescription: details.maintenanceIssueDescription,
      maintenanceWorkshop: details.maintenanceWorkshop,
      maintenanceEstimateDatetime: details.maintenanceEstimateDatetime,
      maintenanceCost: details.maintenanceCost,
    };
  };

  // admin fleet delete: populate delete modal fields with the selected vehicle and active fleet filters.
  const hydrateDeleteVehicleModal = (triggerButton) => {
    if (!(triggerButton instanceof HTMLElement)) {
      return;
    }

    const vehicleId = Number.parseInt(
      triggerButton.getAttribute("data-delete-vehicle-id") || "0",
      10,
    );
    const vehicleName =
      triggerButton.getAttribute("data-delete-vehicle-label") ||
      triggerButton.getAttribute("data-delete-vehicle-name") ||
      "this vehicle";
    const fleetMode =
      triggerButton.getAttribute("data-delete-fleet-mode") || "type";
    const fleetType =
      triggerButton.getAttribute("data-delete-fleet-type") || "cars";
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
        modal.querySelector(".menu-modal__close") ||
        modal.querySelector(".menu-modal__dialog");
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

    const activeModalId =
      modalHistory.length > 0 ? modalHistory[modalHistory.length - 1] : null;
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
    closeCreateCustomSelect();
    clearAdminLoginUiState();
    resetCreateVehicleModal();
    resetEditVehicleModal();
    resetBookingReadModal();

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
        document.activeElement instanceof HTMLElement
          ? document.activeElement
          : null;
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

      if (targetId === "admin-booking-read-modal") {
        hydrateBookingReadModal(button);
      }

      if (targetId === "admin-booking-track-modal") {
        hydrateBookingTrackModal(button);
      }

      if (targetId === "admin-delete-booking-modal") {
        hydrateDeleteBookingModal(button);
      }

      if (targetId === "admin-maintenance-fill-modal") {
        hydrateMaintenanceFillModal(button);
      }

      if (targetId === "admin-maintenance-edit-modal") {
        hydrateMaintenanceEditModal(button);
      }

      if (targetId === "admin-create-vehicle-modal") {
        hydrateCreateVehicleModal(button);
      }

      if (targetId === "admin-edit-vehicle-modal") {
        hydrateEditVehicleModal(button);
      }

      if (modalHistory.length === 0) {
        lastFocusedElement =
          document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
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

  if (
    adminCreateVehicleTypeInput instanceof HTMLSelectElement &&
    adminCreateFleetTypeInput instanceof HTMLInputElement
  ) {
    adminCreateVehicleTypeInput.addEventListener("change", () => {
      adminCreateFleetTypeInput.value = adminCreateVehicleTypeInput.value;
    });
  }

  if (
    adminEditVehicleTypeInput instanceof HTMLSelectElement &&
    adminEditFleetTypeInput instanceof HTMLInputElement
  ) {
    adminEditVehicleTypeInput.addEventListener("change", () => {
      adminEditFleetTypeInput.value = adminEditVehicleTypeInput.value;
    });
  }

  if (adminCreateImageInput instanceof HTMLInputElement) {
    adminCreateImageInput.addEventListener("change", updateCreateImageUi);
  }

  if (adminEditImageInput instanceof HTMLInputElement) {
    adminEditImageInput.addEventListener("change", updateEditImageUi);
  }

  initAdminBookingReturnTimePicker();

  if (adminBookingReturnTimeInput instanceof HTMLInputElement) {
    adminBookingReturnTimeInput.addEventListener(
      "input",
      updateBookingLateFeePreview,
    );
    adminBookingReturnTimeInput.addEventListener(
      "change",
      updateBookingLateFeePreview,
    );
  }

  if (adminBookingTrackAction instanceof HTMLElement) {
    adminBookingTrackAction.addEventListener("click", (event) => {
      if (adminBookingTrackAction.classList.contains("is-disabled")) {
        event.preventDefault();
        event.stopPropagation();
      }
    });
  }

  if (adminBookingReturnTimeInput instanceof HTMLInputElement) {
    adminBookingReturnTimePickerButtons.forEach((pickerButton) => {
      if (!(pickerButton instanceof HTMLButtonElement)) {
        return;
      }

      pickerButton.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (adminBookingReturnTimeInput._flatpickr) {
          adminBookingReturnTimeInput._flatpickr.open();
          return;
        }

        adminBookingReturnTimeInput.focus({ preventScroll: true });
        if (typeof adminBookingReturnTimeInput.showPicker === "function") {
          try {
            adminBookingReturnTimeInput.showPicker();
            return;
          } catch (error) {}
        }

        adminBookingReturnTimeInput.click();
      });
    });
  }

  if (
    adminCreateImageClearButton instanceof HTMLElement &&
    adminCreateImageInput instanceof HTMLInputElement
  ) {
    adminCreateImageClearButton.addEventListener("click", (event) => {
      event.preventDefault();
      adminCreateImageInput.value = "";
      updateCreateImageUi();
    });
  }

  if (
    adminEditImageClearButton instanceof HTMLElement &&
    adminEditImageInput instanceof HTMLInputElement
  ) {
    adminEditImageClearButton.addEventListener("click", (event) => {
      event.preventDefault();
      adminEditImageInput.value = "";
      updateEditImageUi();
    });
  }

  adminCreateStepNextButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (!validateCreateVehicleStep(createVehicleStep)) {
        return;
      }

      setCreateVehicleStep(createVehicleStep + 1);
    });
  });

  adminCreateStepPrevButtons.forEach((button) => {
    button.addEventListener("click", () => {
      setCreateVehicleStep(createVehicleStep - 1);
    });
  });

  adminEditStepNextButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (!validateEditVehicleStep(editVehicleStep)) {
        return;
      }

      setEditVehicleStep(editVehicleStep + 1);
    });
  });

  adminEditStepPrevButtons.forEach((button) => {
    button.addEventListener("click", () => {
      setEditVehicleStep(editVehicleStep - 1);
    });
  });

  if (adminCreateStepCloseButton instanceof HTMLElement) {
    adminCreateStepCloseButton.addEventListener("click", closeAllModals);
  }

  if (adminEditStepCloseButton instanceof HTMLElement) {
    adminEditStepCloseButton.addEventListener("click", closeAllModals);
  }

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
      button.setAttribute(
        "aria-label",
        shouldShow ? "Hide password" : "Show password",
      );

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

    if (
      openCreateCustomSelectController &&
      !openCreateCustomSelectController.shell.contains(target)
    ) {
      closeCreateCustomSelect(openCreateCustomSelectController);
    }

    if (target.classList.contains("menu-modal__overlay")) {
      closeAllModals();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      if (openCreateCustomSelectController) {
        closeCreateCustomSelect(openCreateCustomSelectController);
        return;
      }

      closeAllModals();
    }
  });

  const autoOpenAdminModal =
    document
      .getElementById("admin-login-modal")
      ?.getAttribute("data-open-on-load") === "true";
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

  // maintenance edit/fillup form: reopen read modal after maintenance actions when backend provides auto-open vehicle context.
  const autoOpenReadTrigger = document.querySelector(
    "[data-modal-target='admin-vehicle-read-modal'][data-auto-open-read='true']",
  );
  if (autoOpenReadTrigger instanceof HTMLElement) {
    lastFocusedElement = autoOpenReadTrigger;
    hydrateReadVehicleModal(autoOpenReadTrigger);
    showModal("admin-vehicle-read-modal", true);
  }

  const autoOpenBookingTrigger = document.querySelector(
    "[data-modal-target='admin-booking-read-modal'][data-auto-open-booking='true']",
  );
  if (autoOpenBookingTrigger instanceof HTMLElement) {
    lastFocusedElement = autoOpenBookingTrigger;
    hydrateBookingReadModal(autoOpenBookingTrigger);
    showModal("admin-booking-read-modal", true);
  }
})();
