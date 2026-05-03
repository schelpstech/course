let paymentTable;

$(document).ready(function () {
  initPaymentTable();
  bindPaymentEvents();

  // Auto refresh without breaking pagination
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
});

/**
 * ----------------------------------------
 * INIT TABLE
 * ----------------------------------------
 */
function initPaymentTable() {
  paymentTable = $("#paymentTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/payment/fetchPayments.php",
      dataSrc: "data",
      error: function (xhr) {
        console.error(xhr.responseText);
        Swal.fire("Error", "Failed to load payments", "error");
      },
    },

    dom: "Bfrtip",

    buttons: [
      {
        extend: "excelHtml5",
        title: "Payments Report",
        exportOptions: {
          columns: ":not(:last-child)",
        },
      },
      {
        extend: "pdfHtml5",
        title: "Payments Report",
        exportOptions: {
          columns: ":not(:last-child)",
        },
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
 * ----------------------------------------
 * EVENTS
 * ----------------------------------------
 */
function bindPaymentEvents() {
  // OPEN REVIEW MODAL
  $(document).on("click", ".reviewPaymentBtn", function () {
    const id = $(this).data("id");
    const proof = $(this).data("proof");
    const ref = $(this).data("ref");

    $("#payment_id").val(id);
    $("#payment_ref").text(ref);

    if (proof) {
      const fileUrl = `../${proof}`;
      const ext = proof.split(".").pop().toLowerCase();

      if (ext === "pdf") {
        $("#proofBox").html(`
          <iframe src="${fileUrl}" width="100%" height="500px"></iframe>
        `);
      } else {
        $("#proofBox").html(`
          <img src="${fileUrl}" class="img-fluid">
        `);
      }
    } else {
      $("#proofBox").html("<p class='text-muted'>No proof uploaded</p>");
    }

    $("#admin_note").val("");
    $("#paymentModal").modal("show");
  });

  // ✅ APPROVE (DELEGATED)
  $(document).on("click", "#approveBtn", function () {
    updatePaymentStatus("successful");
  });

  // ✅ REJECT (DELEGATED + VALIDATION)
  $(document).on("click", "#rejectBtn", function () {
    if (!$("#admin_note").val()) {
      Swal.fire("Required", "Please enter a remark", "warning");
      return;
    }

    updatePaymentStatus("failed");
  });
}
/**
 * ----------------------------------------
 * APPROVE / REJECT HANDLER
 * ----------------------------------------
 */
function updatePaymentStatus(status) {
  const payload = {
    id: $("#payment_id").val(),
    status: status,
    note: $("#admin_note").val(),
  };

  console.log("Sending:", payload); // DEBUG

  $.ajax({
    url: "../api/admin/ajax/payment/updatePaymentStatus.php",
    type: "POST",
    data: payload,
    dataType: "json",

    success: function (res) {
      console.log("Response:", res); // DEBUG

      if (res.status === "success") {
        Swal.fire("Success", res.message, "success");
        $("#paymentModal").modal("hide");
        paymentTable.ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message || "Unknown error", "error");
      }
    },

    error: function (xhr) {
      console.error("AJAX ERROR:", xhr.responseText); // 🔥 CRITICAL
      Swal.fire("Error", "Server error. Check console.", "error");
    },
  });
}
