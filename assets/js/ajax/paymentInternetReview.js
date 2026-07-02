let paymentInternetTable;

$(document).ready(function () {
  initPaymentInternetTable();
  bindPaymentEvents();
  setupAutoRefresh();
  resetModalOnClose();
});

/**
 * ===============================
 * INIT DATATABLE
 * ===============================
 */
function initPaymentInternetTable() {
  paymentInternetTable = $("#paymentInternetTable").DataTable({
    processing: true,
    responsive: false,
    scrollX: true,
    autoWidth: false,

    ajax: {
      url: "../api/admin/ajax/payment/internet/fetchPayments.php",
      dataSrc: "data",
      error: function (xhr) {
        console.error("Fetch Error:", xhr.responseText);
        Swal.fire("Error", "Failed to load clearance records", "error");
      },
    },

    dom: "Bfrtip",

    buttons: [
      {
        extend: "excelHtml5",
        title: "Course Registration Clearance Report",
        exportOptions: { columns: ":not(:last-child)" },
      },
      {
        extend: "pdfHtml5",
        title: "Course Registration Clearance Report",
        exportOptions: { columns: ":not(:last-child)" },
      },
    ],

    columns: [
      { data: null },
      { data: "student_name" },
      { data: "matric" },
      { data: "paymentReference" },
      { data: "payment_type" },
      { data: "amount_paid" },
      { data: "payment_mode" },
      { data: "status" },
      { data: "payment_date" },
      { data: "actions" },
    ],

    columnDefs: [
      {
        targets: 0,
        render: (d, t, r, m) => m.row + 1,
      },
    ],
  });

  paymentInternetTable.columns.adjust();
}

/**
 * ===============================
 * AUTO REFRESH TABLE
 * ===============================
 */
function setupAutoRefresh() {
  let isReloading = false;

  setInterval(() => {
    if (!$.fn.DataTable.isDataTable("#paymentInternetTable")) return;

    if (!isReloading) {
      isReloading = true;

      paymentInternetTable.ajax.reload(() => {
        isReloading = false;
      }, false);
    }
  }, 60000);
}

/**
 * ===============================
 * EVENT BINDINGS
 * ===============================
 */
function bindPaymentEvents() {
  /**
   * -------------------------------
   * OPEN REVIEW MODAL (NO PROOF)
   * -------------------------------
   */
  $(document).on("click", ".reviewPaymentBtn", function () {
    const btn = $(this);

    const id = btn.data("id");
    const ref = btn.data("ref");

    const expected = btn.data("expected") || 0;
    const paid = btn.data("paid") || 0;
    const message = btn.data("message") || "";
    const canApprove = btn.data("canapprove");

    // Populate modal fields
    $("#payment_id").val(id);
    $("#payment_ref").text(ref);

    // Currency formatter
    const formatMoney = (val) => "₦" + Number(val || 0).toLocaleString();

    $("#semester_fee").text(formatMoney(expected));
    $("#amount_paid").text(formatMoney(paid));

    /**
     * -------------------------------
     * SYSTEM MESSAGE
     * -------------------------------
     */
    let alertClass = "alert-info";

    if (message.includes("No fee")) {
      alertClass = "alert-warning";
    }

    $("#payment_message").html(`
      <div class="alert ${alertClass}">
        ${message}
      </div>
    `);

    /**
     * -------------------------------
     * APPROVE / REJECT CONTROL
     * -------------------------------
     */
    if (canApprove == 1 || canApprove === true) {
      $("#approveBtn").show();
      $("#rejectBtn").show();
    } else {
      $("#approveBtn").hide();
      $("#rejectBtn").show();
    }

    // Clear previous note
    $("#admin_note").val("");

    // 🔥 NO PROOF SECTION ANYMORE
    $("#proofBox").html(""); // just in case old UI still exists
    $("#downloadProofBtn").hide();

    $("#paymentInternetModal").modal("show");
  });

  /**
   * -------------------------------
   * APPROVE
   * -------------------------------
   */
  $(document).on("click", "#approveBtn", function () {
    updatePaymentStatus("successful");
  });

  /**
   * -------------------------------
   * REJECT
   * -------------------------------
   */
  $(document).on("click", "#rejectBtn", function () {
    if (!$("#admin_note").val()) {
      Swal.fire("Required", "Please enter a remark", "warning");
      return;
    }

    updatePaymentStatus("failed");
  });
}

/**
 * ===============================
 * UPDATE PAYMENT STATUS
 * ===============================
 */
function updatePaymentStatus(status) {
  $("#approveBtn, #rejectBtn").prop("disabled", true);

  const payload = {
    id: $("#payment_id").val(),
    status: status,
    note: $("#admin_note").val(),
  };

  $.ajax({
    url: "../api/admin/ajax/payment/internet/updatePaymentStatus.php",
    type: "POST",
    data: payload,
    dataType: "json",

    success: function (res) {
      $("#approveBtn, #rejectBtn").prop("disabled", false);

      if (res.status === "success") {
        closeModalSafely();

        setTimeout(() => {
          Swal.fire("Success", res.message, "success");
        }, 300);

        paymentInternetTable.ajax.reload(null, false);
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: res.message || "Unknown error",
          target: document.getElementById("paymentInternetModal"),
        });
      }
    },

    error: function (xhr) {
      console.error("AJAX ERROR:", xhr.responseText);

      $("#approveBtn, #rejectBtn").prop("disabled", false);

      Swal.fire("Error", "Server error", "error");
    },
  });
}

/**
 * ===============================
 * SAFE MODAL CLOSE
 * ===============================
 */
function closeModalSafely() {
  if (document.activeElement) {
    document.activeElement.blur();
  }

  const modalEl = document.getElementById("paymentInternetModal");
  const modal = bootstrap.Modal.getInstance(modalEl);

  if (modal) {
    modal.hide();
  } else {
    $("#paymentInternetModal").modal("hide");
  }
}

/**
 * ===============================
 * RESET MODAL
 * ===============================
 */
function resetModalOnClose() {
  $("#paymentInternetModal").on("hidden.bs.modal", function () {
    $("#payment_message").html("");
    $("#admin_note").val("");
  });
}
