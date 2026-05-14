let regTable;

// ✅ Load filters first
function loadFilters() {
  $.getJSON("../api/admin/ajax/semester/getFilters.php", function (res) {
    let sessionOpt = '<option value="">Select Session</option>';
    let semesterOpt = '<option value="">Select Semester</option>';

    (res.sessions || []).forEach((s) => {
      sessionOpt += `<option value="${s.id}">${s.name}</option>`;
    });

    (res.semesters || []).forEach((s) => {
      semesterOpt += `<option value="${s.id}">${s.name}</option>`;
    });

    $("#sessionFilter").html(sessionOpt);
    $("#semesterFilter").html(semesterOpt);
  }).fail(function () {
    console.error("Failed to load filters");
  });
}

// ✅ Initialize DataTable
function initTable() {
  regTable = $("#regTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/semester/getSemesterRegistrations.php",
      data: function (d) {
        d.session_id = $("#sessionFilter").val();
        d.semester_id = $("#semesterFilter").val();
      },
      dataSrc: "data",
    },

    dom: "Bfrtip", // ✅ THIS enables buttons layout

    buttons: [
      {
        extend: "excelHtml5",
        title: "Students Report",
        exportOptions: {
          columns: ":not(:last-child)", // exclude actions column
        },
      },
      {
        extend: "pdfHtml5",
        title: "Students Report",
        exportOptions: {
          columns: ":not(:last-child)",
        },
      },
    ],
    columns: [
      // ✅ S/N
      {
        data: null,
        render: (data, type, row, meta) => meta.row + 1,
      },

      // ✅ Fullname + Matric
      {
        data: null,
        render: (d) => `
                    <div>
                        <strong>${d.name}</strong><br>
                        <small>${d.matric_no || "-"}</small>
                    </div>
                `,
      },

      // ✅ Department block
      {
        data: null,
        render: (d) => `
                    <div>
                        <strong>${d.level || "-"}</strong><br>
                        <span>${d.program || "-"}</span><br>
                        <small>${d.institution || "-"}</small>
                    </div>
                `,
      },

      // ✅ Receipt Uploaded
      {
        data: "receipt_uploaded",
        render: (d) =>
          d == 1
            ? '<span class="text-success fw-bold">✔</span>'
            : '<span class="text-danger fw-bold">✖</span>',
      },

      // ✅ Payment Confirmed
      {
        data: "payment_confirmed",
        render: (d) =>
          d == 1
            ? '<span class="text-success fw-bold">✔</span>'
            : '<span class="text-danger fw-bold">✖</span>',
      },

      // ✅ Course Fee Paid
      {
        data: "course_fee_paid",
        render: (d) =>
          d == 1
            ? '<span class="text-success fw-bold">✔</span>'
            : '<span class="text-danger fw-bold">✖</span>',
      },

      // ✅ Course Registration
      {
        data: "courses_registered",
        render: (d) =>
          d == 1
            ? '<span class="text-success fw-bold">✔</span>'
            : '<span class="text-danger fw-bold">✖</span>',
      },

      // ✅ Overall Status
      {
        data: "status",
        render: (d) => {
          let color = "secondary";

          if (d === "Completed") color = "success";
          else if (d.includes("Awaiting")) color = "warning";
          else if (d === "Not Started") color = "danger";

          return `<span class="badge bg-${color}">${d}</span>`;
        },
      },
    ],
  });
}

// ✅ Only reload when BOTH filters are selected
function reloadTableIfReady() {
  let session = $("#sessionFilter").val();
  let semester = $("#semesterFilter").val();

  if (session && semester) {
    regTable.ajax.reload();
  }
}

// ✅ Event listeners
$("#sessionFilter, #semesterFilter").on("change", function () {
  reloadTableIfReady();
});

// ✅ Initialize properly (order matters!)
$(document).ready(function () {
  loadFilters();
  initTable();
});
