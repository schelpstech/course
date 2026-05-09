let paymentTable;

$(document).ready(function () {
  initPaymentTable();
  bindPaymentEvents();
  setupAutoRefresh();
  resetModalOnClose();
});

/**
 * ===============================
 * INIT DATATABLE
 * ===============================
 */
function initPaymentTable() {
  paymentTable = $("#paymentTable").DataTable({
    processing: true,
    responsive: true,

    ajax: {
      url: "../api/admin/ajax/payment/fetchPayments.php",
      dataSrc: "data",
      error: function (xhr) {
        console.error("Fetch Error:", xhr.responseText);
        Swal.fire("Error", "Failed to load payments", "error");
      },
    },

    dom: "Bfrtip",

    buttons: [
      {
        extend: "excelHtml5",
        title: "Payments Report",
        exportOptions: { columns: ":not(:last-child)" },
      },
      {
        extend: "pdfHtml5",
        title: "Payments Report",
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
}

/**
 * ===============================
 * AUTO REFRESH TABLE
 * ===============================
 */
function setupAutoRefresh() {
  let isReloading = false;

  setInterval(() => {
    if (!$.fn.DataTable.isDataTable("#paymentTable")) return;

    if (!isReloading) {
      isReloading = true;

      paymentTable.ajax.reload(() => {
        isReloading = false;
      }, false);
    }
  }, 15000);
}

/**
 * ===============================
 * EVENT BINDINGS
 * ===============================
 */
function bindPaymentEvents() {
  /**
   * -------------------------------
   * OPEN REVIEW MODAL
   * -------------------------------
   */
  $(document).on("click", ".reviewPaymentBtn", function () {
    const btn = $(this);

    const id = btn.data("id");
    const proof = btn.data("proof");
    const ref = btn.data("ref");

    const expected = btn.data("expected") || 0;
    const paid = btn.data("paid") || 0;
    const percentage = btn.data("percentage") || 0;
    const message = btn.data("message") || "";
    const canApprove = btn.data("canapprove");

    // Populate hidden + labels
    $("#payment_id").val(id);
    $("#payment_ref").text(ref);

    // Format currency safely
    const formatMoney = (val) => "₦" + Number(val || 0).toLocaleString();

    $("#institution_percentage").text(percentage + "%");
    $("#semester_fee").text(formatMoney(expected));
    $("#amount_paid").text(formatMoney(paid));

    /**
     * -------------------------------
     * PAYMENT MESSAGE ALERT
     * -------------------------------
     */
    let alertClass = "alert-success";

    if (message.includes("Below")) {
      alertClass = "alert-danger";
    } else if (message.includes("No fee")) {
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
      $("#rejectBtn").show(); // always allow rejection
    }

    /**
     * -------------------------------
     * LOAD PROOF (PDF / IMAGE)
     * -------------------------------
     */
    if (proof) {
      const fileUrl = `../${proof}`;
      const ext = proof.split(".").pop().toLowerCase();

      if (ext === "pdf") {
        $("#proofBox").html(`
          <iframe src="${fileUrl}" width="100%" height="500px"></iframe>
        `);
      } else {
        $("#proofBox").html(`
          <img src="${fileUrl}" class="img-fluid rounded shadow">
        `);
      }

      $("#downloadProofBtn").attr("href", fileUrl).show();
    } else {
      $("#proofBox").html("<p class='text-muted'>No proof uploaded</p>");
      $("#downloadProofBtn").hide();
    }

    $("#admin_note").val("");
    $("#paymentModal").modal("show");
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
  // Prevent double clicks
  $("#approveBtn, #rejectBtn").prop("disabled", true);

  const payload = {
    id: $("#payment_id").val(),
    status: status,
    note: $("#admin_note").val(),
  };

  $.ajax({
    url: "../api/admin/ajax/payment/updatePaymentStatus.php",
    type: "POST",
    data: payload,
    dataType: "json",

    success: function (res) {
      $("#approveBtn, #rejectBtn").prop("disabled", false);

      if (res.status === "success") {
        closeModalSafely();

        // Delay slightly to allow modal animation finish
        setTimeout(() => {
          Swal.fire("Success", res.message, "success");
        }, 300);

        paymentTable.ajax.reload(null, false);
      } else {
        // ❗ Keep modal open for error
        Swal.fire({
          icon: "error",
          title: "Error",
          text: res.message || "Unknown error",
          target: document.getElementById("paymentModal"), // 🔥 attach to modal
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
 * SAFE MODAL CLOSE (EDGE FIX)
 * ===============================
 */
function closeModalSafely() {
  if (document.activeElement) {
    document.activeElement.blur();
  }

  const modalEl = document.getElementById("paymentModal");
  const modal = bootstrap.Modal.getInstance(modalEl);

  if (modal) {
    modal.hide();
  } else {
    $("#paymentModal").modal("hide");
  }
}

/**
 * ===============================
 * RESET MODAL AFTER CLOSE
 * ===============================
 */
function resetModalOnClose() {
  $("#paymentModal").on("hidden.bs.modal", function () {
    $("#proofBox").html("");
    $("#payment_message").html("");
    $("#admin_note").val("");
  });
}
