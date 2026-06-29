let lecturerClassListTable;
let activeAllocationId = "";
let activeSheet = null;
let autosaveTimers = {};

function lecturerEscape(value) {
  return $("<div>").text(value || "").html();
}

function lecturerSetOptions(selector, rows, selected = "") {
  let html = "<option value=''>Select allocated course</option>";

  (rows || []).forEach((row) => {
    const selectedAttr = String(row.id) === String(selected) ? "selected" : "";
    html += `<option value="${row.id}" ${selectedAttr}>${lecturerEscape(row.name)}</option>`;
  });

  $(selector).html(html);
}

function lecturerUpdateCsrf(action, token) {
  if (token && window.lecturerScoresheetConfig && window.lecturerScoresheetConfig.csrf) {
    window.lecturerScoresheetConfig.csrf[action] = token;
  }
}

function loadLecturerAllocations(callback = null) {
  $.getJSON("../api/admin/ajax/results/lecturerAssignedCourses.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load assigned courses", "error");
      return;
    }

    lecturerSetOptions("#scoresheet_allocation_id", res.options || []);

    const preselected = sessionStorage.getItem("selectedAllocationId");
    if (preselected) {
      $("#scoresheet_allocation_id").val(preselected);
      sessionStorage.removeItem("selectedAllocationId");
    }

    if (callback) callback();
  });
}

function renderCourseInfo(details) {
  const allocation = details.allocation || {};
  const config = details.config || {};
  const sheet = details.sheet || {};

  activeSheet = sheet;

  $("#scoresheetCourseInfo").html(`
    <div class="row g-3">
      <div class="col-md-3"><strong>Course Code</strong><br>${lecturerEscape(allocation.course_code)}</div>
      <div class="col-md-5"><strong>Course Title</strong><br>${lecturerEscape(allocation.course_title)}</div>
      <div class="col-md-4"><strong>Lecturer</strong><br>${lecturerEscape(allocation.lecturer_name)}</div>
      <div class="col-md-3"><strong>Institution</strong><br>${lecturerEscape(allocation.institution_name)}</div>
      <div class="col-md-3"><strong>Programme</strong><br>${lecturerEscape(allocation.programme_name)}</div>
      <div class="col-md-3"><strong>Department</strong><br>${lecturerEscape(allocation.department_name)}</div>
      <div class="col-md-3"><strong>Level</strong><br>${lecturerEscape(allocation.level_name)}</div>
      <div class="col-md-3"><strong>Semester</strong><br>${lecturerEscape(allocation.semester_name)}</div>
      <div class="col-md-3"><strong>Academic Session</strong><br>${lecturerEscape(allocation.session_name)}</div>
      <div class="col-md-3"><strong>Registered Students</strong><br>${details.registered_students || 0}</div>
      <div class="col-md-3"><strong>Sheet Status</strong><br>CA: ${lecturerEscape(sheet.ca_status)} | Exam: ${lecturerEscape(sheet.exam_status)}</div>
    </div>
  `);

  $("#caMaxScore").text(config.ca_max_score || "0");
  $("#examMaxScore").text(config.exam_max_score || "0");
  updateDownloadState();
}

function updateDownloadState() {
  if (!activeAllocationId || !activeSheet) return;

  const canDownload = ["submitted", "approved"].includes(activeSheet.ca_status) &&
    ["submitted", "approved"].includes(activeSheet.exam_status);

  const href = `../api/admin/ajax/results/downloadScoresheet.php?allocation_id=${encodeURIComponent(activeAllocationId)}`;
  $("#downloadScoresheetBtn")
    .attr("href", canDownload ? href : "#")
    .toggleClass("disabled", !canDownload);

  $("#downloadScoresheetNotice")
    .toggleClass("alert-info", !canDownload)
    .toggleClass("alert-success", canDownload)
    .text(canDownload ? "Scoresheet is ready for download." : "Download becomes available after both CA and Exam scores have been submitted.");
}

function loadCourseDetails() {
  activeAllocationId = $("#scoresheet_allocation_id").val();

  if (!activeAllocationId) {
    Swal.fire("Required", "Select an allocated course first.", "warning");
    return;
  }

  $.getJSON("../api/admin/ajax/results/lecturerAllocationDetails.php", { allocation_id: activeAllocationId }, function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load scoresheet", "error");
      return;
    }

    $("#scoresheetWorkspace").removeClass("d-none");
    renderCourseInfo(res);
    loadClassList();
    loadScoreComponent("ca");
    loadScoreComponent("exam");
  });
}

function loadClassList() {
  const tableSelector = "#lecturerClassListTable";

  if ($.fn.DataTable.isDataTable(tableSelector)) {
    $(tableSelector).DataTable().clear().destroy();
    lecturerClassListTable = null;
  }

  $(`${tableSelector} tbody`).empty();

  lecturerClassListTable = $(tableSelector).DataTable({
    ajax: {
      url: "../api/admin/ajax/results/lecturerClassList.php",
      data: { allocation_id: activeAllocationId },
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Class List", exportOptions: { columns: ":not(:first-child)" } },
      { extend: "pdfHtml5", title: "Class List", exportOptions: { columns: ":not(:first-child)" } },
      "print",
    ],
    columns: [
      { data: "passport", orderable: false, searchable: false },
      { data: "matric_no" },
      { data: "name" },
      { data: "gender" },
      { data: "programme" },
      { data: "department" },
      { data: "level" },
      { data: "registration_status" },
    ],
  });
}

function scoreTableBody(component, rows, maxScore, readOnly) {
  let html = "";
  const readonlyAttr = readOnly ? "readonly" : "";

  (rows || []).forEach((row) => {
    const score = row.score === null || row.score === undefined ? "" : row.score;
    html += `
      <tr>
        <td>${row.matric_no}</td>
        <td>${row.name}</td>
        <td>
          <input type="number"
            class="form-control form-control-sm score-input"
            data-student-id="${row.student_id}"
            data-component="${component}"
            min="0"
            max="${maxScore}"
            step="0.01"
            value="${score}"
            ${readonlyAttr}>
        </td>
        <td>${row.total_score || ""}</td>
        <td>${row.grade || ""}</td>
        <td>${row.remark || ""}</td>
      </tr>
    `;
  });

  return html || "<tr><td colspan='6' class='text-center'>No registered students found</td></tr>";
}

function loadScoreComponent(component) {
  $.getJSON(
    "../api/admin/ajax/results/lecturerScoresheetData.php",
    { allocation_id: activeAllocationId, component },
    function (res) {
      if (!res.status) {
        $(`#${component}ScoresTable tbody`).html(`<tr><td colspan="6" class="text-center text-danger">${lecturerEscape(res.message || "Unable to load scores")}</td></tr>`);
        return;
      }

      const tableId = component === "ca" ? "#caScoresTable" : "#examScoresTable";
      $(tableId + " tbody").html(scoreTableBody(component, res.data || [], res.max_score, res.read_only));
      $(`.saveScoresBtn[data-component="${component}"], .submitScoresBtn[data-component="${component}"]`).prop("disabled", !!res.read_only);

      if (component === "ca") {
        $("#caMaxScore").text(res.max_score);
      } else {
        $("#examMaxScore").text(res.max_score);
      }
    }
  );
}

function collectScores(component) {
  const scores = {};
  let valid = true;
  const maxScore = parseFloat(component === "ca" ? $("#caMaxScore").text() : $("#examMaxScore").text()) || 0;

  $(`.score-input[data-component="${component}"]`).each(function () {
    const input = $(this);
    const value = input.val();

    input.removeClass("is-invalid");

    if (value !== "") {
      const numeric = parseFloat(value);
      if (Number.isNaN(numeric) || numeric < 0 || numeric > maxScore) {
        input.addClass("is-invalid");
        valid = false;
      }
    }

    scores[input.data("student-id")] = value;
  });

  if (!valid) {
    Swal.fire("Invalid score", `${component.toUpperCase()} scores must be between 0 and ${maxScore}.`, "error");
    return null;
  }

  return scores;
}

function saveScores(component, submit = false, silent = false) {
  const scores = collectScores(component);
  if (!scores) return;

  const url = submit
    ? "../api/admin/ajax/results/submitLecturerScores.php"
    : "../api/admin/ajax/results/saveLecturerScores.php";
  const action = submit ? "submit" : "save";

  if (!silent) {
    Swal.fire({
      title: submit ? "Submitting..." : "Saving...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });
  }

  $.post(
    url,
    {
      allocation_id: activeAllocationId,
      component,
      scores,
      csrf_token: window.lecturerScoresheetConfig.csrf[action],
    },
    function (res) {
      lecturerUpdateCsrf(action, res.csrf_token);

      if (!silent) {
        Swal.close();
      }

      if (res.status) {
        const stamp = `Last saved ${new Date().toLocaleTimeString()}`;
        $(`#${component}LastSaved`).text(stamp);
        loadCourseDetails();

        if (!silent) {
          Swal.fire("Success", res.message, "success");
        }
      } else if (!silent) {
        Swal.fire("Error", res.message || "Unable to save scores", "error");
      }
    },
    "json"
  ).fail(function () {
    if (!silent) {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    }
  });
}

$(document).ready(function () {
  loadLecturerAllocations(function () {
    if ($("#scoresheet_allocation_id").val()) {
      loadCourseDetails();
    }
  });
});

$(document).on("click", "#loadScoresheetBtn", loadCourseDetails);

$(document).on("click", ".saveScoresBtn", function () {
  saveScores($(this).data("component"), false);
});

$(document).on("click", ".submitScoresBtn", function () {
  const component = $(this).data("component");

  Swal.fire({
    title: `Submit ${component.toUpperCase()} scores?`,
    text: "After final submission, this scoresheet becomes read-only until it is returned or reopened.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Submit",
  }).then((result) => {
    if (result.isConfirmed) {
      saveScores(component, true);
    }
  });
});

$(document).on("input", ".score-input", function () {
  const component = $(this).data("component");
  clearTimeout(autosaveTimers[component]);
  autosaveTimers[component] = setTimeout(function () {
    saveScores(component, false, true);
  }, 2000);
});
