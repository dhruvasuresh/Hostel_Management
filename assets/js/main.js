// Document Ready Function
document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize datepicker
  const dateInputs = document.querySelectorAll(".datepicker");
  dateInputs.forEach((input) => {
    new Datepicker(input, {
      format: "yyyy-mm-dd",
    });
  });

  // Form validation
  const forms = document.querySelectorAll(".needs-validation");
  forms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });

  // Delete confirmation
  const deleteButtons = document.querySelectorAll(".delete-btn");
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Are you sure you want to delete this item?")) {
        e.preventDefault();
      }
    });
  });

  // Search functionality
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("keyup", function () {
      const searchTerm = this.value.toLowerCase();
      const tableRows = document.querySelectorAll("tbody tr");

      tableRows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? "" : "none";
      });
    });
  }

  // Print functionality
  const printButtons = document.querySelectorAll(".print-btn");
  printButtons.forEach((button) => {
    button.addEventListener("click", function () {
      window.print();
    });
  });
});

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
  }).format(amount);
}

// Format date
function formatDate(date) {
  return new Date(date).toLocaleDateString("en-IN");
}

// Show/Hide Password
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const icon = document.querySelector(`[data-target="${inputId}"]`);

  if (input.type === "password") {
    input.type = "text";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}

// File upload preview
function previewImage(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById(previewId).src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// AJAX form submission
function submitForm(formId, successCallback) {
  const form = document.getElementById(formId);
  const formData = new FormData(form);

  fetch(form.action, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        successCallback(data);
      } else {
        alert(data.message || "An error occurred");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred");
    });
}
