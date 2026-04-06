/**
 * Purpose: Frontend logic for booking form and confirmation flow.
 */

(function () {
  const DATE_INPUT_IDS = ["pickup-date", "return-date"];
  const TIME_INPUT_IDS = ["pickup-time", "return-time"];
  const MAINTENANCE_DATE_INPUT_IDS = [
    "maintenance-fill-estimate",
    "maintenance-edit-estimate",
  ];
  const CREATE_VEHICLE_DATE_INPUT_IDS = [
    "create-vehicle-last-service-date",
    "edit-vehicle-last-service-date",
  ];

  const getInputById = (id) => document.getElementById(id);

  const openPickerForInput = (input) => {
    if (!input) {
      return;
    }

    if (input._flatpickr) {
      input._flatpickr.open();
      return;
    }

    input.focus({ preventScroll: true });

    if (typeof input.showPicker === "function") {
      try {
        input.showPicker();
      } catch (error) {}
      return;
    }

    input.click();
  };

  const bindPickerTriggers = () => {
    document.querySelectorAll("[data-open-picker-for]").forEach((trigger) => {
      const targetId = trigger.getAttribute("data-open-picker-for");

      if (targetId === "admin-booking-return-time-input") {
        return;
      }

      const input = targetId ? getInputById(targetId) : null;

      if (!input) {
        return;
      }

      trigger.addEventListener("click", () => {
        openPickerForInput(input);
      });
    });
  };

  const initVehicleTypeTabs = () => {
    const vehicleTypeInput = getInputById("vehicle-type-input");
    const tabs = Array.from(
      document.querySelectorAll(
        ".booking-engine__tabs .booking-tab[data-vehicle-type]",
      ),
    );

    if (!vehicleTypeInput || tabs.length === 0) {
      return;
    }

    const setActiveTab = (vehicleType) => {
      const normalizedType = String(vehicleType || "").toLowerCase();
      let matchedType = "";

      tabs.forEach((tab, index) => {
        const tabType = String(tab.dataset.vehicleType || "").toLowerCase();
        const isActive = tabType === normalizedType;

        if (isActive) {
          matchedType = tabType;
        }

        tab.classList.toggle("is-active", isActive);
        tab.setAttribute("aria-selected", isActive ? "true" : "false");
        tab.setAttribute("tabindex", isActive || index === 0 ? "0" : "-1");
      });

      vehicleTypeInput.value =
        matchedType || String(tabs[0].dataset.vehicleType || "cars");
    };

    const initialType =
      vehicleTypeInput.value ||
      tabs.find((tab) => tab.classList.contains("is-active"))?.dataset
        .vehicleType ||
      tabs[0].dataset.vehicleType;

    setActiveTab(initialType);

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        setActiveTab(tab.dataset.vehicleType);
      });
    });
  };

  const initHeroBookNowAction = () => {
    const heroBookNowButton = document.querySelector(".hero-banner__cta");
    const bookingEngine = document.querySelector(".booking-engine");
    const pickupLocationInput = getInputById("pickup-location");

    if (!heroBookNowButton || !bookingEngine) {
      return;
    }

    heroBookNowButton.addEventListener("click", () => {
      bookingEngine.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });

      bookingEngine.classList.remove("booking-engine--pop");
      void bookingEngine.offsetWidth;
      bookingEngine.classList.add("booking-engine--pop");

      window.setTimeout(() => {
        bookingEngine.classList.remove("booking-engine--pop");

        if (pickupLocationInput) {
          pickupLocationInput.focus({ preventScroll: true });
        }
      }, 480);
    });
  };

  const initSameReturnLocationSync = () => {
    const sameReturnCheckbox = getInputById("same-return");
    const pickupLocationInput = getInputById("pickup-location");
    const returnLocationInput = getInputById("return-location");

    if (!sameReturnCheckbox || !pickupLocationInput || !returnLocationInput) {
      return;
    }

    const syncReturnLocation = () => {
      if (!sameReturnCheckbox.checked) {
        return;
      }

      returnLocationInput.value = pickupLocationInput.value;
    };

    sameReturnCheckbox.addEventListener("change", () => {
      syncReturnLocation();
    });

    pickupLocationInput.addEventListener("input", () => {
      syncReturnLocation();
    });

    pickupLocationInput.addEventListener("change", () => {
      syncReturnLocation();
    });

    syncReturnLocation();
  };

  const initNativeFallback = () => {
    DATE_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      input.type = "date";
      input.placeholder = "";
    });

    MAINTENANCE_DATE_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      input.type = "date";
      input.placeholder = "";
    });

    CREATE_VEHICLE_DATE_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      input.type = "date";
      input.placeholder = "";
    });

    TIME_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      input.type = "time";
      input.placeholder = "";
      input.step = "300";
    });
  };

  const initFlatpickrPickers = () => {
    if (typeof window.flatpickr !== "function") {
      initNativeFallback();
      return;
    }

    const sharedPickerOptions = {
      allowInput: true,
      disableMobile: true,
      static: true,
      monthSelectorType: "static",
    };

    const dateOptions = {
      ...sharedPickerOptions,
      dateFormat: "d/m/Y",
      prevArrow:
        '<span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>',
      nextArrow:
        '<span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>',
    };

    const maintenanceDateOptions = {
      ...sharedPickerOptions,
      dateFormat: "d M, Y",
      prevArrow:
        '<span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>',
      nextArrow:
        '<span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>',
    };

    DATE_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      window.flatpickr(input, dateOptions);
    });

    MAINTENANCE_DATE_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      window.flatpickr(input, maintenanceDateOptions);
    });

    CREATE_VEHICLE_DATE_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      window.flatpickr(input, dateOptions);
    });

    const timeOptions = {
      ...sharedPickerOptions,
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K",
      minuteIncrement: 5,
    };

    TIME_INPUT_IDS.forEach((id) => {
      const input = getInputById(id);
      if (!input) {
        return;
      }

      window.flatpickr(input, timeOptions);
    });
  };

  const initBookingPickers = () => {
    const hasBookingEngine = Boolean(document.querySelector(".booking-engine"));
    const hasMaintenancePickers = Boolean(
      document.querySelector("[data-maintenance-estimate-input]") ||
      document.querySelector("[data-maintenance-edit-estimate-input]"),
    );
    const hasCreateVehicleDatePicker = Boolean(
      document.querySelector("[data-create-last-service-input]") ||
      document.querySelector("[data-edit-last-service-input]"),
    );

    if (
      !hasBookingEngine &&
      !hasMaintenancePickers &&
      !hasCreateVehicleDatePicker
    ) {
      return;
    }

    initVehicleTypeTabs();
    initFlatpickrPickers();
    bindPickerTriggers();
    initHeroBookNowAction();
    initSameReturnLocationSync();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initBookingPickers);
    return;
  }

  initBookingPickers();
})();
