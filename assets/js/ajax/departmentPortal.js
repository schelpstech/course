let departmentStudentsTable;
let departmentCourseFormsTable;
let departmentCoursesTable;
let departmentResultSheetsTable;
let departmentCourseModal;
let departmentCourseFormModal;
let departmentStudentModal;
let departmentResultSheetModal;

const departmentPortalState = {
  bootstrapped: false,
  sessions: [],
  semesters: [],
  levels: [],
};

function departmentEscape(value) {
  return $("<div>").text(value == null ? "" : value).html();
}

function departmentStatusMessage(res, fallback) {
  return (res && res.message) || fallback || "Unable to complete request.";
}

function departmentSetOptions(selector, rows, selected = "", placeholder = "Select", labelBuilder = null) {
  let html = placeholder === null ? "" : `<option value="">${departmentEscape(placeholder)}</option>`;

  (rows || []).forEach((row) => {
    const label = labelBuilder ? labelBuilder(row) : row.name;
    const isSelected = String(row.id) === String(selected) ? "selected" : "";
    html += `<option value="${departmentEscape(row.id)}" ${isSelected}>${departmentEscape(label)}</option>`;
  });

  $(selector).html(html);
}

function departmentSessionName(sessionId) {
  const session = departmentPortalState.sessions.find((row) => String(row.id) === String(sessionId));
  return session ? session.name : "";
}

function departmentSemesterLabel(semester) {
  const sessionName = departmentSessionName(semester.session_id);
  return sessionName ? `${sessionName} - ${semester.name}` : semester.name;
}

function departmentLoadBootstrap(callback = null) {
  if (departmentPortalState.bootstrapped) {
    if (callback) callback();
    return;
  }

  $.getJSON("../api/admin/ajax/departmentPortal/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", departmentStatusMessage(res, "Unable to load department setup."), "error");
      return;
    }

    departmentPortalState.bootstrapped = true;
    departmentPortalState.sessions = res.sessions || [];
    departmentPortalState.semesters = res.semesters || [];
    departmentPortalState.levels = res.levels || [];

    if (callback) callback();
  });
}

function departmentFilterSemesters(sessionId, selected = "", selector = "#departmentCourseFormSemester", placeholder = "All Semesters") {
  const rows = sessionId
    ? departmentPortalState.semesters.filter((semester) => String(semester.session_id) === String(sessionId))
    : departmentPortalState.semesters;

  departmentSetOptions(selector, rows, selected, placeholder, departmentSemesterLabel);
}

function departmentRenderStudent(student, forms) {
  const name = [student.first_name, student.other_name, student.last_name].filter(Boolean).join(" ");
  const rows = (forms || [])
    .map(
      (form) => `
        <tr>
          <td>${departmentEscape(form.session_name || "")}</td>
          <td>${departmentEscape(form.semester_name || "")}</td>
          <td>${departmentEscape(form.courses_count || 0)}</td>
          <td>${departmentEscape(form.total_units || 0)}</td>
          <td><span class="badge bg-info">${departmentEscape(String(form.approval_status || "").toUpperCase())}</span></td>
          <td>${departmentEscape(form.created_at || "")}</td>
        </tr>
      `
    )
    .join("");

  return `
    <div class="row g-3 mb-3">
      <div class="col-md-4"><strong>Name</strong><br>${departmentEscape(name)}</div>
      <div class="col-md-4"><strong>Matric No</strong><br>${departmentEscape(student.matric_no || "")}</div>
      <div class="col-md-4"><strong>Email</strong><br>${departmentEscape(student.email || "")}</div>
      <div class="col-md-4"><strong>Institution</strong><br>${departmentEscape(student.institution_name || "")}</div>
      <div class="col-md-4"><strong>Programme</strong><br>${departmentEscape(student.programme_name || "")}</div>
      <div class="col-md-4"><strong>Department / Level</strong><br>${departmentEscape(student.department_name || "")} / ${departmentEscape(student.level_name || "")}</div>
    </div>
    <h6>Course Forms</h6>
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>Session</th>
            <th>Semester</th>
            <th>Courses</th>
            <th>Units</th>
            <th>Status</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>${rows || '<tr><td colspan="6" class="text-center text-muted">No course forms found.</td></tr>'}</tbody>
      </table>
    </div>
  `;
}

function departmentUpdateCourseCsrf(key, token) {
  if (token && window.departmentCourseConfig && window.departmentCourseConfig.csrf) {
    window.departmentCourseConfig.csrf[key] = token;
  }
}

function departmentUpdateModerationCsrf(token) {
  if (token && window.departmentModerationConfig && window.departmentModerationConfig.csrf) {
    window.departmentModerationConfig.csrf.moderate = token;
  }
}

function departmentResetCourseForm() {
  $("#departmentCourseForm")[0].reset();
  $("#department_course_id").val("");
  $("#departmentCourseModalTitle").text("Add Course");
  departmentSetOptions("#department_course_level_id", departmentPortalState.levels, "", "Select Level");
  departmentSetOptions(
    "#department_course_semester_id",
    departmentPortalState.semesters,
    "",
    "Select Semester",
    departmentSemesterLabel
  );
  $("#department_course_type").val("core");
}

function initDepartmentStudents() {
  if (!$("#departmentStudentsTable").length) return;

  departmentStudentModal = new bootstrap.Modal(document.getElementById("departmentStudentModal"));
  departmentStudentsTable = $("#departmentStudentsTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/departmentPortal/fetchStudents.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Department Students", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Department Students", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "name" },
      { data: "matric" },
      { data: "programme" },
      { data: "level" },
      { data: "status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });
}

function initDepartmentCourseForms() {
  if (!$("#departmentCourseFormsTable").length) return;

  departmentCourseFormModal = new bootstrap.Modal(document.getElementById("departmentCourseFormModal"));

  departmentLoadBootstrap(function () {
    departmentSetOptions("#departmentCourseFormSession", departmentPortalState.sessions, "", "All Sessions");
    departmentFilterSemesters("", "", "#departmentCourseFormSemester", "All Semesters");
  });

  departmentCourseFormsTable = $("#departmentCourseFormsTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/departmentPortal/fetchCourseForms.php",
      data: function (d) {
        d.session_id = $("#departmentCourseFormSession").val() || "";
        d.semester_id = $("#departmentCourseFormSemester").val() || "";
      },
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Department Course Forms", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Department Course Forms", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "student" },
      { data: "level" },
      { data: "courses" },
      { data: "status" },
      { data: "created_at" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });
}

function initDepartmentCourses() {
  if (!$("#departmentCoursesTable").length) return;

  departmentCourseModal = new bootstrap.Modal(document.getElementById("departmentCourseModal"));
  departmentLoadBootstrap();

  departmentCoursesTable = $("#departmentCoursesTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/departmentPortal/fetchCourses.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Department Courses", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Department Courses", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "code" },
      { data: "title" },
      { data: "unit" },
      { data: "level" },
      { data: "semester" },
      { data: "type" },
      { data: "status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });
}

function initDepartmentModeration() {
  if (!$("#departmentResultSheetsTable").length) return;

  departmentResultSheetModal = new bootstrap.Modal(document.getElementById("departmentResultSheetModal"));
  departmentResultSheetsTable = $("#departmentResultSheetsTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/departmentPortal/fetchResultSheets.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Department Result Sheets", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Department Result Sheets", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "course" },
      { data: "lecturer" },
      { data: "level" },
      { data: "session" },
      { data: "semester" },
      { data: "students" },
      { data: "submitted" },
      { data: "pass_rate" },
      { data: "status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });
}

$(document).ready(function () {
  initDepartmentStudents();
  initDepartmentCourseForms();
  initDepartmentCourses();
  initDepartmentModeration();
});

$(document).on("click", ".viewDepartmentStudent", function () {
  const id = $(this).data("id");
  $("#departmentStudentModalBody").html('<div class="text-center text-muted">Loading...</div>');
  departmentStudentModal.show();

  $.getJSON("../api/admin/ajax/departmentPortal/getStudent.php", { id }, function (res) {
    if (!res.status) {
      $("#departmentStudentModalBody").html(`<div class="alert alert-danger">${departmentEscape(departmentStatusMessage(res))}</div>`);
      return;
    }

    $("#departmentStudentModalBody").html(departmentRenderStudent(res.student || {}, res.forms || []));
  });
});

$(document).on("change", "#departmentCourseFormSession", function () {
  departmentFilterSemesters($(this).val(), "", "#departmentCourseFormSemester", "All Semesters");
  if (departmentCourseFormsTable) {
    departmentCourseFormsTable.ajax.reload();
  }
});

$(document).on("change", "#departmentCourseFormSemester", function () {
  if (departmentCourseFormsTable) {
    departmentCourseFormsTable.ajax.reload();
  }
});

$(document).on("click", ".viewDepartmentCourseForm", function () {
  const id = $(this).data("id");
  $("#departmentCourseFormModalBody").html('<div class="text-center text-muted">Loading...</div>');
  departmentCourseFormModal.show();

  $.get("../api/admin/ajax/departmentPortal/viewCourseForm.php", { id }, function (html) {
    $("#departmentCourseFormModalBody").html(html);
  }).fail(function () {
    $("#departmentCourseFormModalBody").html('<div class="alert alert-danger">Unable to load course form.</div>');
  });
});

$(document).on("change", ".departmentCourseFormStatus", function () {
  const status = $(this).val();
  const id = $(this).data("id");

  if (!status) return;

  Swal.fire({
    title: "Update course form?",
    text: `Set this course form to ${status}.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Update",
  }).then((result) => {
    if (!result.isConfirmed) {
      $(this).val("");
      return;
    }

    $.post("../api/admin/ajax/departmentPortal/updateCourseFormStatus.php", { id, status }, function (res) {
      if (res.status) {
        Swal.fire("Updated", res.message, "success");
        departmentCourseFormsTable.ajax.reload(null, false);
      } else {
        Swal.fire("Error", departmentStatusMessage(res), "error");
      }
    }, "json").fail(function () {
      Swal.fire("Error", "Server error occurred.", "error");
    });
  });
});

$(document).on("click", "#addDepartmentCourseBtn", function () {
  departmentLoadBootstrap(function () {
    departmentResetCourseForm();
    departmentCourseModal.show();
  });
});

$(document).on("click", ".editDepartmentCourse", function () {
  const button = $(this);

  departmentLoadBootstrap(function () {
    departmentResetCourseForm();
    $("#departmentCourseModalTitle").text("Edit Course");
    $("#department_course_id").val(button.data("id") || "");
    $("#department_course_level_id").val(button.data("level") || "");
    $("#department_course_semester_id").val(button.data("semester") || "");
    $("#department_course_code").val(button.data("code") || "");
    $("#department_course_unit").val(button.data("unit") || "");
    $("#department_course_title").val(button.data("title") || "");
    $("#department_course_type").val(button.data("type") || "core");
    departmentCourseModal.show();
  });
});

$(document).on("submit", "#departmentCourseForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.departmentCourseConfig.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/departmentPortal/saveCourse.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      departmentUpdateCourseCsrf("save", res.csrf_token);
      Swal.close();

      if (res.status) {
        departmentCourseModal.hide();
        departmentCoursesTable.ajax.reload(null, false);
        Swal.fire("Success", res.message, "success");
      } else {
        Swal.fire("Error", departmentStatusMessage(res, "Unable to save course."), "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred.", "error");
    },
  });
});

$(document).on("click", ".toggleDepartmentCourse", function () {
  const id = $(this).data("id");

  Swal.fire({
    title: "Change course status?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Continue",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/departmentPortal/toggleCourse.php",
      {
        id,
        csrf_token: window.departmentCourseConfig.csrf.toggle,
      },
      function (res) {
        departmentUpdateCourseCsrf("toggle", res.csrf_token);

        if (res.status) {
          Swal.fire("Updated", res.message, "success");
          departmentCoursesTable.ajax.reload(null, false);
        } else {
          Swal.fire("Error", departmentStatusMessage(res, "Unable to update course."), "error");
        }
      },
      "json"
    ).fail(function () {
      Swal.fire("Error", "Server error occurred.", "error");
    });
  });
});

$(document).on("click", ".reviewResultSheet", function () {
  const id = $(this).data("id");
  $("#departmentResultSheetBody").html('<div class="text-center text-muted">Loading...</div>');
  $("#departmentModerationRemarks").val("");
  departmentResultSheetModal.show();

  $.get("../api/admin/ajax/departmentPortal/resultSheetDetails.php", { id }, function (html) {
    $("#departmentResultSheetBody").html(html);
  }).fail(function () {
    $("#departmentResultSheetBody").html('<div class="alert alert-danger">Unable to load result sheet.</div>');
  });
});

$(document).on("click", ".moderateResultSheet", function () {
  const id = $("#activeDepartmentResultSheetId").val();
  const action = $(this).data("action");
  const remarks = $("#departmentModerationRemarks").val().trim();

  if (!id) {
    Swal.fire("Error", "Open a result sheet before moderating.", "error");
    return;
  }

  if ((action === "return" || action === "reject") && !remarks) {
    Swal.fire("Remarks required", "Add remarks before returning or rejecting a result sheet.", "warning");
    return;
  }

  Swal.fire({
    title: `${String(action).charAt(0).toUpperCase()}${String(action).slice(1)} result sheet?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Continue",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/departmentPortal/moderateResultSheet.php",
      {
        id,
        action,
        remarks,
        csrf_token: window.departmentModerationConfig.csrf.moderate,
      },
      function (res) {
        departmentUpdateModerationCsrf(res.csrf_token);

        if (res.status) {
          departmentResultSheetModal.hide();
          departmentResultSheetsTable.ajax.reload(null, false);
          Swal.fire("Updated", res.message, "success");
        } else {
          Swal.fire("Error", departmentStatusMessage(res, "Unable to moderate result sheet."), "error");
        }
      },
      "json"
    ).fail(function () {
      Swal.fire("Error", "Server error occurred.", "error");
    });
  });
});
