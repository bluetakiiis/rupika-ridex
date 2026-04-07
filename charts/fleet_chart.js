/**
 * Purpose: Render the admin dashboard pie chart for most-rented vehicle category.
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

  root.RidexCharts.renderMostRentedFleetChart = function (config) {
    var safeConfig = config && typeof config === "object" ? config : {};
    var charts =
      safeConfig.charts && typeof safeConfig.charts === "object"
        ? safeConfig.charts
        : {};
    var rentData = charts.mostRentedVehicleCategory || {};
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

    var setUnavailableState =
      typeof safeConfig.setUnavailableState === "function"
        ? safeConfig.setUnavailableState
        : function () {};

    if (
      typeof root.Chart !== "function" ||
      labels.length === 0 ||
      values.length === 0
    ) {
      setUnavailableState("mostRentedVehicleCategory", true);
      if (
        safeConfig.currentChart &&
        typeof safeConfig.currentChart.destroy === "function"
      ) {
        safeConfig.currentChart.destroy();
      }
      return null;
    }

    setUnavailableState("mostRentedVehicleCategory", false);
    var canvasId =
      typeof safeConfig.canvasId === "string" && safeConfig.canvasId !== ""
        ? safeConfig.canvasId
        : "admin-most-rented-category-chart";
    var canvas = document.getElementById(canvasId);
    if (!canvas) {
      return safeConfig.currentChart || null;
    }

    var backgroundColor = Array.isArray(dataset.backgroundColor)
      ? dataset.backgroundColor
      : ["#f75b7a", "#f6a340", "#f4ca55"];

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

    if (safeConfig.currentChart) {
      safeConfig.currentChart.data = chartData;
      safeConfig.currentChart.update("none");
      return safeConfig.currentChart;
    }

    return new root.Chart(canvas, {
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
})();
