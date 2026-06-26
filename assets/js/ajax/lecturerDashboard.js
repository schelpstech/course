let lecturerDashboardCoursesTable;

$(document).ready(function () {
  lecturerDashboardCoursesTable = $("#lecturerDashboardCoursesTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/results/lecturerAssignedCourses.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Assigned Courses", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Assigned Courses", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "course" },
      { data: "department" },
      { data: "level" },
      { data: "session" },
      { data: "semester" },
      { data: "students" },
      { data: "ca_status" },
      { data: "exam_status" },
      { data: "moderation_status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });
});

$(document).on("click", ".openScoresheet", function () {
  sessionStorage.setItem("selectedAllocationId", $(this).data("id"));
  window.location.href = window.lecturerDashboardConfig.scoresheetUrl;
});
