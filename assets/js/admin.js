// Admin Dashboard Charts
function initDashboardCharts() {
  // Student Statistics Chart
  const studentCtx = document.getElementById("studentChart");
  if (studentCtx) {
    new Chart(studentCtx, {
      type: "bar",
      data: {
        labels: ["S Class", "A Class", "B Class"],
        datasets: [
          {
            label: "Students per Room Type",
            data: studentData,
            backgroundColor: [
              "rgba(255, 99, 132, 0.2)",
              "rgba(54, 162, 235, 0.2)",
              "rgba(255, 206, 86, 0.2)",
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
            ],
            borderWidth: 1,
          },
        ],
      },
      options: {
        scales: {
          y: {
            beginAtZero: true,
          },
        },
      },
    });
  }

  // Fee Collection Chart
  const feeCtx = document.getElementById("feeChart");
  if (feeCtx) {
    new Chart(feeCtx, {
      type: "pie",
      data: {
        labels: ["Collected", "Pending"],
        datasets: [
          {
            data: feeData,
            backgroundColor: [
              "rgba(75, 192, 192, 0.2)",
              "rgba(255, 99, 132, 0.2)",
            ],
            borderColor: ["rgba(75, 192, 192, 1)", "rgba(255, 99, 132, 1)"],
            borderWidth: 1,
          },
        ],
      },
    });
  }
}

// Room Occupancy Update
function updateRoomOccupancy(roomId, status) {
  fetch("/admin/rooms/update-status.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      roomId: roomId,
      status: status,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        alert(data.message || "Error updating room status");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred");
    });
}

// Bulk Actions
function bulkAction(action) {
  const selectedItems = document.querySelectorAll(
    'input[name="bulk_items[]"]:checked'
  );
  const ids = Array.from(selectedItems).map((item) => item.value);

  if (ids.length === 0) {
    alert("Please select items to perform action");
    return;
  }

  if (confirm(`Are you sure you want to ${action} selected items?`)) {
    // Perform bulk action
    fetch(`/admin/bulk-actions.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: action,
        ids: ids,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert(data.message || "Error performing bulk action");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred");
      });
  }
}

// Initialize admin features
document.addEventListener("DOMContentLoaded", function () {
  // Initialize dashboard charts
  initDashboardCharts();

  // Toggle sidebar
  const sidebarToggle = document.getElementById("sidebarToggle");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
      document.body.classList.toggle("sidebar-collapsed");
    });
  }

  // Bulk selection
  const bulkSelectAll = document.getElementById("bulkSelectAll");
  if (bulkSelectAll) {
    bulkSelectAll.addEventListener("change", function () {
      const checkboxes = document.querySelectorAll(
        'input[name="bulk_items[]"]'
      );
      checkboxes.forEach((checkbox) => {
        checkbox.checked = this.checked;
      });
    });
  }
});
