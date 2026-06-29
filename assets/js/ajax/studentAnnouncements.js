function studentAnnouncementUpdateCsrf(token) {
  if (token && window.studentAnnouncementConfig) {
    window.studentAnnouncementConfig.csrf = token;
  }
}

$(document).on("click", ".markAnnouncementRead", function () {
  const button = $(this);
  const announcementId = button.data("id");
  const row = button.closest("[data-announcement-row]");

  button.prop("disabled", true).text("Saving...");

  $.post(
    "../api/student/acknowledgeAnnouncement.php",
    {
      announcement_id: announcementId,
      csrf_token: window.studentAnnouncementConfig.csrf,
    },
    function (res) {
      studentAnnouncementUpdateCsrf(res.csrf_token);

      if (res.status) {
        row.removeClass("is-unread");
        row.find(".announcement-read-state").html('<span class="badge bg-success">Read</span>');
        button.remove();
      } else {
        button.prop("disabled", false).text("Mark as Read");
        Swal.fire("Error", res.message || "Unable to mark announcement as read", "error");
      }
    },
    "json"
  ).fail(function () {
    button.prop("disabled", false).text("Mark as Read");
    Swal.fire("Error", "Server error occurred", "error");
  });
});
