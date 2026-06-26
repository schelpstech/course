let courseAllocationTable;
let courseAllocationModal;
let allocationSemesters = [];

function allocationEscape(value) {
  return $("<div>").text(value || "").html();
}

function allocationSetOptions(selector, rows, selected = "") {
  let html = "<option value=''>Select</option>";
  (rows || []).forEach((row) => {
    const isSelected = String(row.id) === String(selected) ? "selected" : "";
    html += `<option value="${row.id}" ${isSelected}>${allocationEscape(row.name)}</option>`;
  });
  $(selector).html(html);
}

function allocationUpdateCsrf(token) {
  if (token && window.courseAllocationConfig) {
    window.courseAllocationConfig.csrf.save = token;
  }
}

function allocationUpdateDisableCsrf(token) {
  if (token && window.courseAllocationConfig) {
    window.courseAllocationConfig.csrf.disable = token;
  }
}

function allocationLoadBootstrap(callback = null) {
  $.getJSON("../api/admin/ajax/results/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load allocation setup", "error");
      return;
    }

    allocationSemesters = res.semesters || [];
    allocationSetOptions("#allocation_session_id", res.sessions || []);
    allocationSetOptions("#allocation_institution_id", res.institutions || []);
    allocationSetOptions("#allocation_semester_id", []);

    if (callback) callback();
  });
}

function allocationFilterSemesters(sessionId, selected = "") {
  const rows = allocationSemesters.filter((semester) => String(semester.session_id) === String(sessionId));
  allocationSetOptions("#allocation_semester_id", rows, selected);
}

function allocationLoadProgrammes(institutionId, selected = "", callback = null) {
  allocationSetOptions("#allocation_programme_id", []);
  allocationSetOptions("#allocation_department_id", []);
  allocationSetOptions("#allocation_level_id", []);
  allocationSetOptions("#allocation_course_id", []);
  allocationSetOptions("#allocation_lecturer_id", []);

  if (!institutionId) {
    if (callback) callback();
    return;
  }

  $.getJSON("../api/admin/ajax/endpoint/getProgrammesByInstitution.php", { institution_id: institutionId }, function (res) {
    allocationSetOptions("#allocation_programme_id", res.data || [], selected);
    if (callback) callback();
  });
}

function allocationLoadDepartments(programmeId, selected = "", callback = null) {
  allocationSetOptions("#allocation_department_id", []);
  allocationSetOptions("#allocation_level_id", []);
  allocationSetOptions("#allocation_course_id", []);
  allocationSetOptions("#allocation_lecturer_id", []);

  if (!programmeId) {
    if (callback) callback();
    return;
  }

  $.getJSON("../api/admin/ajax/endpoint/getDepartmentsByProgramme.php", { programme_id: programmeId }, function (res) {
    allocationSetOptions("#allocation_department_id", res.data || [], selected);
    if (callback) callback();
  });
}

function allocationLoadLevels(departmentId, selected = "", callback = null) {
  allocationSetOptions("#allocation_level_id", []);

  if (!departmentId) {
    if (callback) callback();
    return;
  }

  $.getJSON("../api/admin/ajax/endpoint/getLevelsByDepartment.php", { department_id: departmentId }, function (res) {
    allocationSetOptions("#allocation_level_id", res.data || [], selected);
    if (callback) callback();
  });
}

function allocationLoadCourses(levelId, departmentId, selected = "", callback = null) {
  allocationSetOptions("#allocation_course_id", []);

  if (!levelId && !departmentId) {
    if (callback) callback();
    return;
  }

  const sessionId = $("#allocation_session_id").val() || "";
  const semesterId = $("#allocation_semester_id").val() || "";
  const allocationId = $("#allocation_id").val() || 0;

  if (!sessionId || !semesterId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/results/getCoursesByLevel.php",
    {
      level_id: levelId || 0,
      department_id: departmentId || 0,
      session_id: sessionId,
      semester_id: semesterId,
      exclude_allocation_id: allocationId,
    },
    function (res) {
      allocationSetOptions("#allocation_course_id", res.data || [], selected);
      if (callback) callback();
    }
  );
}

function allocationLoadLecturers(departmentId, selected = "", callback = null) {
  allocationSetOptions("#allocation_lecturer_id", []);

  if (!departmentId) {
    if (callback) callback();
    return;
  }

  $.getJSON("../api/admin/ajax/results/getLecturersByDepartment.php", { department_id: departmentId }, function (res) {
    allocationSetOptions("#allocation_lecturer_id", res.data || [], selected);
    if (callback) callback();
  });
}

function resetAllocationForm() {
  $("#courseAllocationForm")[0].reset();
  $("#allocation_id").val("");
  allocationSetOptions("#allocation_semester_id", []);
  allocationSetOptions("#allocation_programme_id", []);
  allocationSetOptions("#allocation_department_id", []);
  allocationSetOptions("#allocation_level_id", []);
  allocationSetOptions("#allocation_course_id", []);
  allocationSetOptions("#allocation_lecturer_id", []);
  $("#allocation_status").val("active");
  $("#courseAllocationModalTitle").text("Allocate Course");
}

$(document).ready(function () {
  courseAllocationModal = new bootstrap.Modal(document.getElementById("courseAllocationModal"));

  courseAllocationTable = $("#courseAllocationTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/results/fetchCourseAllocations.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Course Allocations", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Course Allocations", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "session" },
      { data: "semester" },
      { data: "course" },
      { data: "department" },
      { data: "lecturer" },
      { data: "status" },
      { data: "allocated_at" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });

  $("#addAllocationBtn").on("click", function () {
    allocationLoadBootstrap(function () {
      resetAllocationForm();
      courseAllocationModal.show();
    });
  });
});

$(document).on("change", "#allocation_session_id", function () {
  allocationFilterSemesters($(this).val());
  allocationSetOptions("#allocation_course_id", []);
});

$(document).on("change", "#allocation_semester_id", function () {
  allocationLoadCourses($("#allocation_level_id").val(), $("#allocation_department_id").val());
});

$(document).on("change", "#allocation_institution_id", function () {
  allocationLoadProgrammes($(this).val());
});

$(document).on("change", "#allocation_programme_id", function () {
  allocationLoadDepartments($(this).val());
});

$(document).on("change", "#allocation_department_id", function () {
  const departmentId = $(this).val();
  allocationLoadLevels(departmentId);
  allocationLoadLecturers(departmentId);
  allocationLoadCourses("", departmentId);
});

$(document).on("change", "#allocation_level_id", function () {
  allocationLoadCourses($(this).val(), $("#allocation_department_id").val());
});

$(document).on("click", ".editAllocation", function () {
  const id = $(this).data("id");

  $.getJSON("../api/admin/ajax/results/getCourseAllocation.php", { id }, function (res) {
    if (!res.status) {
      Swal.fire("Error", "Unable to load allocation", "error");
      return;
    }

    allocationLoadBootstrap(function () {
      resetAllocationForm();
      const allocation = res.allocation || {};

      $("#allocation_id").val(allocation.id || "");
      $("#allocation_session_id").val(allocation.academic_session_id || "");
      allocationFilterSemesters(allocation.academic_session_id, allocation.semester_id);
      $("#allocation_institution_id").val(allocation.institution_id || "");
      $("#courseAllocationModalTitle").text("Reallocate Course");

      allocationLoadProgrammes(allocation.institution_id, allocation.programme_id, function () {
        allocationLoadDepartments(allocation.programme_id, allocation.department_id, function () {
          allocationLoadLevels(allocation.department_id, allocation.level_id, function () {
            allocationLoadLecturers(allocation.department_id, allocation.lecturer_id);
            allocationLoadCourses(allocation.level_id, allocation.department_id, allocation.course_id, function () {
              $("#allocation_status").val(allocation.status || "active");
              courseAllocationModal.show();
            });
          });
        });
      });
    });
  });
});

$(document).on("click", ".disableAllocation", function () {
  const id = $(this).data("id");

  Swal.fire({
    title: "Disable this allocation?",
    text: "The lecturer will no longer be able to continue this allocation until it is reallocated.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Disable",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/results/disableCourseAllocation.php",
      {
        id,
        csrf_token: window.courseAllocationConfig.csrf.disable,
      },
      function (res) {
        allocationUpdateDisableCsrf(res.csrf_token);

        if (res.status) {
          courseAllocationTable.ajax.reload(null, false);
          Swal.fire("Disabled", res.message, "success");
        } else {
          Swal.fire("Error", res.message || "Unable to disable allocation", "error");
        }
      },
      "json"
    ).fail(function () {
      Swal.fire("Error", "Server error occurred", "error");
    });
  });
});

$(document).on("submit", "#courseAllocationForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.courseAllocationConfig.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/results/saveCourseAllocation.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      allocationUpdateCsrf(res.csrf_token);
      Swal.close();

      if (res.status) {
        courseAllocationModal.hide();
        courseAllocationTable.ajax.reload(null, false);
        Swal.fire("Success", res.message, "success");
      } else {
        Swal.fire("Error", res.message || "Unable to save allocation", "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});
