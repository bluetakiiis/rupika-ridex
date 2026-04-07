/**
 * Purpose: Render the admin dashboard line chart for sales by vehicle category.
 * Website Section: Admin Dashboard.
 */

(function () {
  "use strict";

  var root = window;
  root.RidexCharts = root.RidexCharts || {};

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

  root.RidexCharts.renderSalesVehicleCategoryChart = function (config) {
    var safeConfig = config && typeof config === "object" ? config : {};
    var chartData = normalizeSalesData(safeConfig.charts);
    var setUnavailableState =
      typeof safeConfig.setUnavailableState === "function"
        ? safeConfig.setUnavailableState
        : function () {};

    if (
      typeof root.Chart !== "function" ||
      chartData.labels.length === 0 ||
      chartData.datasets.length === 0
    ) {
      setUnavailableState("salesVehicleCategory", true);
      if (
        safeConfig.currentChart &&
        typeof safeConfig.currentChart.destroy === "function"
      ) {
        safeConfig.currentChart.destroy();
      }
      return null;
    }

    setUnavailableState("salesVehicleCategory", false);
    var canvasId =
      typeof safeConfig.canvasId === "string" && safeConfig.canvasId !== ""
        ? safeConfig.canvasId
        : "admin-sales-vehicle-category-chart";
    var canvas = document.getElementById(canvasId);
    if (!canvas) {
      return safeConfig.currentChart || null;
    }

    if (safeConfig.currentChart) {
      safeConfig.currentChart.data.labels = chartData.labels;
      safeConfig.currentChart.data.datasets = chartData.datasets;
      safeConfig.currentChart.update("none");
      return safeConfig.currentChart;
    }

    return new root.Chart(canvas, {
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
})();
