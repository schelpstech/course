$(document).ready(function () {
  loadPaymentClearanceList();
});

/**
 * LOAD TABLE
 */
function loadPaymentClearanceList() {
  $.getJSON(
    "../api/admin/ajax/clearance/getPaymentClearanceList.php",
    function (res) {
      let rows = "";

      (res.data || []).forEach((item, index) => {
        let eligibilityBadge = "";
        let clearanceBadge = "";
        let actionButton = "";

        if (item.clearance_status === "approved") {
          eligibilityBadge = '<span class="badge bg-primary">Cleared</span>';

          clearanceBadge = '<span class="badge bg-primary">Cleared</span>';

          actionButton = `
        <span class="badge bg-success">
            Clearance Complete
        </span>
    `;
        } else {
          eligibilityBadge =
            item.eligible == 1
              ? '<span class="badge bg-success">Eligible</span>'
              : '<span class="badge bg-danger">Not Eligible</span>';

          clearanceBadge = '<span class="badge bg-warning">Pending</span>';

          actionButton = `
        <button
            class="btn btn-info btn-sm reviewBtn"
            data-id="${item.semester_registration_id}">
            Review
        </button>
    `;
        }
        let paidColor =
          item.eligible == 1 ? "text-success fw-bold" : "text-danger fw-bold";

        rows += `
                    <tr>

                        <td>${index + 1}</td>

                        <td>${item.student_name}</td>

                        <td>${item.matric_no}</td>

                        <td>${item.department_name}</td>

                        <td>${item.level_name}</td>

                        <td>${item.semester_name} Semester ${item.session_name}</td>

                        <td>₦${Number(item.required_amount).toLocaleString()}</td>

                        <td class="${paidColor}">
    ₦${Number(item.amount_paid).toLocaleString()}
</td>

                        <td>${eligibilityBadge}</td>

                        <td>${clearanceBadge}</td>

                        <td>

                            <button
                                class="btn btn-info btn-sm reviewBtn"
                                data-id="${item.semester_registration_id}">

                                Review

                            </button>

                        </td>

                    </tr>
                `;
      });

      if ($.fn.DataTable.isDataTable("#paymentClearanceTable")) {
        $("#paymentClearanceTable").DataTable().destroy();
      }

      $("#paymentClearanceTable tbody").html(rows);

      $("#paymentClearanceTable").DataTable({
        responsive: true,

        pageLength: 25,

        order: [[0, "asc"]],

        dom:
          "<'row mb-3'<'col-md-6'B><'col-md-6'f>>" +
          "<'row'<'col-12'tr>>" +
          "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",

        buttons: [
          {
            extend: "excelHtml5",
            text: '<i class="ph ph-file-xls"></i> Excel',
            className: "btn btn-success",
          },

          {
            extend: "pdfHtml5",
            text: '<i class="ph ph-file-pdf"></i> PDF',
            className: "btn btn-danger",
            orientation: "landscape",
            pageSize: "A4",
          },

          {
            extend: "print",
            text: '<i class="ph ph-printer"></i> Print',
            className: "btn btn-primary",
          },
        ],
      });
    },
  );
}

/**
 * REVIEW PAYMENT
 */
$(document).on("click", ".reviewBtn", function () {
  let id = $(this).data("id");

  $.post(
    "../api/admin/ajax/clearance/getPaymentClearanceDetails.php",
    { semester_registration_id: id },
    function (res) {
      if (res.status !== "success") {
        Swal.fire("Error", res.message, "error");

        return;
      }

      let d = res.data;

      $("#semester_registration_id").val(d.semester_registration_id);

      $("#clearance_type_id").val(d.clearance_type_id);

      $("#student_name").text(d.student_name);

      $("#matric_no").text(d.matric_no);

      $("#institution_name").text(d.institution_name);

      $("#department_name").text(d.department_name);

      $("#level_name").text(d.level_name);

      $("#school_fee").text("₦" + Number(d.school_fee).toLocaleString());

      $("#required_percent").text(d.min_percent + "%");

      $("#required_amount").text(
        "₦" + Number(d.required_amount).toLocaleString(),
      );

      $("#amount_paid").text("₦" + Number(d.amount_paid).toLocaleString());

      if (d.eligible == 1) {
        $("#eligibilityBox").html(`
                    <div class="alert alert-success">
                        Student is eligible for payment clearance.
                    </div>
                `);

        $("#approveClearanceBtn").prop("disabled", false);
      } else {
        $("#eligibilityBox").html(`
                    <div class="alert alert-danger">
                        Student has not met the minimum payment requirement.
                    </div>
                `);

        $("#approveClearanceBtn").prop("disabled", true);
      }

      let rows = "";

      (d.payments || []).forEach((p) => {
        rows += `
                    <tr>

                        <td>${p.paymentReference}</td>

                        <td>
                            ₦${Number(p.amount_paid).toLocaleString()}
                        </td>

                        <td>${p.payment_date}</td>

                        <td>${p.payment_mode}</td>

                        <td>${p.status}</td>

                    </tr>
                `;
      });

      $("#paymentHistoryBody").html(rows);

      $("#paymentReviewModal").modal("show");
    },
    "json",
  );
});

/**
 * APPROVE CLEARANCE
 */
$("#approveClearanceBtn").click(function () {
  let data = {
    semester_registration_id: $("#semester_registration_id").val(),

    clearance_type_id: $("#clearance_type_id").val(),

    remark: $("#clearance_remark").val(),
  };

  $.post(
    "../api/admin/ajax/clearance/approvePaymentClearance.php",
    data,
    function (res) {
      if (res.status === "success") {
        $("#paymentReviewModal").modal("hide");

        loadPaymentClearanceList();

        Swal.fire({
          icon: "success",
          title: "Payment Clearance Approved",
          text: res.message,
        });
      } else {
        $("#paymentReviewModal").modal("hide");
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  );
});
