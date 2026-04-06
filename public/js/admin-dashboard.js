/**
 * Purpose: Admin dashboard JS to load metrics and render charts/widgets.
 * Website Section: Admin Dashboard.
 * Developer Notes: Fetch metrics via AJAX, hydrate Chart.js configs, update KPI cards, and handle auto-refresh/filters.
 */

(function () {
  "use strict";

  var dataNode = document.getElementById("admin-dashboard-data");
  if (!dataNode) {
    return;
  }

  var trendClassOptions = [
    "admin-kpi-card__trend--up",
    "admin-kpi-card__trend--down",
    "admin-kpi-card__trend--flat",
    "admin-kpi-card__trend--unavailable",
  ];
  var salesChartInstance = null;
  var rentChartInstance = null;
  var refreshInFlight = false;

  var parseJsonText = function (rawText) {
    try {
      return JSON.parse(rawText || "{}");
    } catch (error) {
      return {};
    }
  };

  var isFiniteNumber = function (value) {
    if (value === null || value === undefined || value === "") {
      return false;
    }

    var numericValue = Number(value);
    return Number.isFinite(numericValue);
  };

  var toNumericArray = function (values) {
    if (!Array.isArray(values)) {
      return [];
    }

    var numericValues = [];
    for (var index = 0; index < values.length; index += 1) {
      var numericValue = Number(values[index]);
      numericValues.push(Number.isFinite(numericValue) ? numericValue : 0);
    }

    return numericValues;
  };

  var hasNonZeroValues = function (values) {
    if (!Array.isArray(values)) {
      return false;
    }

    for (var index = 0; index < values.length; index += 1) {
      if (Number(values[index]) > 0) {
        return true;
      }
    }

    return false;
  };

  var normalizePayload = function (payload) {
    var normalized = {
      kpis: {},
      charts: {},
    };

    if (!payload || typeof payload !== "object" || Array.isArray(payload)) {
      return normalized;
    }

    var hasKpis =
      payload.kpis &&
      typeof payload.kpis === "object" &&
      !Array.isArray(payload.kpis);
    var hasCharts =
      payload.charts &&
      typeof payload.charts === "object" &&
      !Array.isArray(payload.charts);

    if (hasKpis) {
      normalized.kpis = payload.kpis;
    }

    if (hasCharts) {
      normalized.charts = payload.charts;
    }

    if (!hasKpis && !hasCharts) {
      normalized.charts = payload;
    }

    return normalized;
  };

  var setUnavailableState = function (panelKey, isUnavailable) {
    var panel = document.querySelector('[data-chart-panel="' + panelKey + '"]');
    if (!panel) {
      return;
    }

    var fallback = panel.querySelector("[data-chart-fallback]");
    var status = panel.querySelector("[data-chart-status]");
    var canvasWrap = panel.querySelector(".admin-chart-card__canvas-wrap");

    if (fallback) {
      fallback.hidden = !isUnavailable;
    }

    if (canvasWrap) {
      canvasWrap.hidden = isUnavailable;
    }

    if (status) {
      status.textContent = isUnavailable ? "Unavailable" : "Live";
      status.classList.toggle("is-unavailable", isUnavailable);
    }
  };

  var formatCurrency = function (value) {
    if (!isFiniteNumber(value)) {
      return "Unavailable";
    }

    return "$" + Math.round(Number(value)).toLocaleString("en-US");
  };

  var formatInteger = function (value) {
    if (!isFiniteNumber(value)) {
      return "Unavailable";
    }

    return Math.round(Number(value)).toLocaleString("en-US");
  };

  var formatPercent = function (value) {
    if (!isFiniteNumber(value)) {
      return "Unavailable";
    }

    return Number(value).toFixed(1) + "%";
  };

  var resolveTrend = function (trend) {
    if (!isFiniteNumber(trend)) {
      return {
        label: "New",
        className: "admin-kpi-card__trend--up",
      };
    }

    var trendValue = Number(trend);
    if (Math.abs(trendValue) < 0.05) {
      return {
        label: "0.0%",
        className: "admin-kpi-card__trend--flat",
      };
    }

    if (trendValue > 0) {
      return {
        label: "+" + trendValue.toFixed(1) + "%",
        className: "admin-kpi-card__trend--up",
      };
    }

    return {
      label: trendValue.toFixed(1) + "%",
      className: "admin-kpi-card__trend--down",
    };
  };

  var updateKpis = function (kpis) {
    var kpiNodes = document.querySelectorAll("[data-kpi-value]");
    for (var index = 0; index < kpiNodes.length; index += 1) {
      var valueNode = kpiNodes[index];
      var kpiKey = valueNode.getAttribute("data-kpi-value") || "";
      var formatType = (
        valueNode.getAttribute("data-kpi-format") || "integer"
      ).toLowerCase();
      var metric = kpis && typeof kpis === "object" ? kpis[kpiKey] : null;
      var metricValue =
        metric && typeof metric === "object" ? metric.value : null;

      if (formatType === "currency") {
        valueNode.textContent = formatCurrency(metricValue);
      } else if (formatType === "percent") {
        valueNode.textContent = formatPercent(metricValue);
      } else {
        valueNode.textContent = formatInteger(metricValue);
      }

      var trendNode = document.querySelector(
        '[data-kpi-trend="' + kpiKey + '"]',
      );
      if (!trendNode) {
        continue;
      }

      var trendInfo = resolveTrend(
        metric && typeof metric === "object" ? metric.trend : null,
      );
      trendNode.textContent = trendInfo.label;

      for (
        var trendIndex = 0;
        trendIndex < trendClassOptions.length;
        trendIndex += 1
      ) {
        trendNode.classList.remove(trendClassOptions[trendIndex]);
      }
      trendNode.classList.add(trendInfo.className);
    }

    var fleetTrendNode = document.querySelector(
      '[data-kpi-trend="fleetAvailability"]',
    );
    var fleetIconNode = document.querySelector(
      '[data-kpi-icon="fleetAvailability"]',
    );
    if (fleetTrendNode && fleetIconNode) {
      fleetIconNode.classList.remove(
        "admin-kpi-card__icon--up",
        "admin-kpi-card__icon--down",
      );
      if (fleetTrendNode.classList.contains("admin-kpi-card__trend--down")) {
        fleetIconNode.textContent = "trending_down";
        fleetIconNode.classList.add("admin-kpi-card__icon--down");
      } else {
        fleetIconNode.textContent = "trending_up";
        fleetIconNode.classList.add("admin-kpi-card__icon--up");
      }
    }
  };

  var normalizeSalesData = function (charts) {
    var sales =
      charts && typeof charts === "object"
        ? charts.salesVehicleCategory || {}
        : {};
    var labels = Array.isArray(sales.labels) ? sales.labels.map(String) : [];
    var datasetSource = Array.isArray(sales.datasets) ? sales.datasets : [];
    var defaultColors = ["#f75b7a", "#45aaf2", "#2db9b0"];
    var datasets = [];

    for (var index = 0; index < datasetSource.length; index += 1) {
      var source = datasetSource[index] || {};
      var numericValues = toNumericArray(source.data);
      if (numericValues.length < labels.length) {
        for (var pad = numericValues.length; pad < labels.length; pad += 1) {
          numericValues.push(0);
        }
      } else if (numericValues.length > labels.length) {
        numericValues = numericValues.slice(0, labels.length);
      }

      datasets.push({
        label: typeof source.label === "string" ? source.label : "",
        data: numericValues,
        borderColor:
          source.borderColor || defaultColors[index % defaultColors.length],
        backgroundColor:
          source.borderColor || defaultColors[index % defaultColors.length],
        pointRadius: 3,
        pointHoverRadius: 4,
        tension: 0,
        fill: false,
        borderWidth: 2,
      });
    }

    return {
      labels: labels,
      datasets: datasets,
    };
  };

  var renderSalesChart = function (charts) {
    var chartData = normalizeSalesData(charts);

    if (
      typeof window.Chart !== "function" ||
      chartData.labels.length === 0 ||
      chartData.datasets.length === 0
    ) {
      setUnavailableState("salesVehicleCategory", true);
      if (salesChartInstance) {
        salesChartInstance.destroy();
        salesChartInstance = null;
      }
      return;
    }

    setUnavailableState("salesVehicleCategory", false);
    var canvas = document.getElementById("admin-sales-vehicle-category-chart");
    if (!canvas) {
      return;
    }

    if (salesChartInstance) {
      salesChartInstance.data.labels = chartData.labels;
      salesChartInstance.data.datasets = chartData.datasets;
      salesChartInstance.update("none");
      return;
    }

    salesChartInstance = new window.Chart(canvas, {
      type: "line",
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: false,
          },
          legend: {
            position: "top",
            labels: {
              boxWidth: 34,
              boxHeight: 11,
              padding: 10,
              font: {
                size: 10,
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "value",
              font: {
                size: 12,
              },
            },
            ticks: {
              precision: 0,
              font: {
                size: 10,
              },
            },
            grid: {
              color: "rgba(18, 18, 18, 0.08)",
            },
          },
          x: {
            title: {
              display: true,
              text: "Date",
              font: {
                size: 12,
              },
            },
            ticks: {
              font: {
                size: 10,
              },
            },
            grid: {
              color: "rgba(18, 18, 18, 0.08)",
            },
          },
        },
      },
    });
  };

  var renderMostRentedChart = function (charts) {
    var rentData =
      charts && typeof charts === "object"
        ? charts.mostRentedVehicleCategory || {}
        : {};
    var labels = Array.isArray(rentData.labels)
      ? rentData.labels.map(String)
      : [];
    var datasets = Array.isArray(rentData.datasets) ? rentData.datasets : [];
    var dataset = datasets[0] || {};
    var values = toNumericArray(dataset.data);

    if (values.length < labels.length) {
      for (var pad = values.length; pad < labels.length; pad += 1) {
        values.push(0);
      }
    } else if (values.length > labels.length) {
      values = values.slice(0, labels.length);
    }

    var backgroundColor = Array.isArray(dataset.backgroundColor)
      ? dataset.backgroundColor
      : ["#f75b7a", "#f6a340", "#f4ca55"];

    if (
      typeof window.Chart !== "function" ||
      labels.length === 0 ||
      values.length === 0
    ) {
      setUnavailableState("mostRentedVehicleCategory", true);
      if (rentChartInstance) {
        rentChartInstance.destroy();
        rentChartInstance = null;
      }
      return;
    }

    setUnavailableState("mostRentedVehicleCategory", false);
    var canvas = document.getElementById("admin-most-rented-category-chart");
    if (!canvas) {
      return;
    }

    var chartData = {
      labels: labels,
      datasets: [
        {
          data: values,
          backgroundColor: backgroundColor,
          borderWidth: 1,
          borderColor: "#ffffff",
        },
      ],
    };

    if (rentChartInstance) {
      rentChartInstance.data = chartData;
      rentChartInstance.update("none");
      return;
    }

    rentChartInstance = new window.Chart(canvas, {
      type: "pie",
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: false,
          },
          legend: {
            position: "top",
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              padding: 10,
              font: {
                size: 8,
              },
            },
          },
        },
      },
    });
  };

  var applyPayload = function (rawPayload) {
    var payload = normalizePayload(rawPayload);
    updateKpis(payload.kpis);
    renderSalesChart(payload.charts);
    renderMostRentedChart(payload.charts);
  };

  var fetchLatestPayload = function (refreshUrl) {
    if (
      refreshInFlight ||
      refreshUrl === "" ||
      typeof window.fetch !== "function"
    ) {
      return;
    }

    refreshInFlight = true;
    var abortController =
      typeof window.AbortController === "function"
        ? new window.AbortController()
        : null;
    var timeoutId = window.setTimeout(function () {
      if (abortController) {
        abortController.abort();
      }
    }, 10000);

    var finalize = function () {
      refreshInFlight = false;
      window.clearTimeout(timeoutId);
    };

    var requestOptions = {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    };

    if (abortController) {
      requestOptions.signal = abortController.signal;
    }

    window
      .fetch(refreshUrl, requestOptions)
      .then(function (response) {
        if (!response.ok) {
          throw new Error(
            "Dashboard refresh failed with status " + response.status,
          );
        }
        return response.json();
      })
      .then(function (responseBody) {
        if (
          !responseBody ||
          responseBody.ok !== true ||
          typeof responseBody.payload !== "object"
        ) {
          throw new Error("Dashboard refresh returned an invalid payload.");
        }

        applyPayload(responseBody.payload);
      })
      .catch(function (error) {
        if (error && error.name === "AbortError") {
          return;
        }

        if (
          typeof console !== "undefined" &&
          typeof console.warn === "function"
        ) {
          console.warn("Admin dashboard live refresh failed:", error);
        }
      })
      .then(finalize, finalize);
  };

  var initialPayload = parseJsonText(dataNode.textContent || "{}");
  applyPayload(initialPayload);

  var refreshUrl = (dataNode.getAttribute("data-refresh-url") || "").trim();
  var refreshIntervalMs = Number(
    dataNode.getAttribute("data-refresh-interval-ms") || 30000,
  );
  if (!Number.isFinite(refreshIntervalMs) || refreshIntervalMs < 10000) {
    refreshIntervalMs = 30000;
  } else if (refreshIntervalMs > 300000) {
    refreshIntervalMs = 300000;
  }

  if (refreshUrl !== "") {
    fetchLatestPayload(refreshUrl);

    window.setInterval(function () {
      if (document.visibilityState === "hidden") {
        return;
      }

      fetchLatestPayload(refreshUrl);
    }, refreshIntervalMs);

    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "visible") {
        fetchLatestPayload(refreshUrl);
      }
    });
  }
})();
