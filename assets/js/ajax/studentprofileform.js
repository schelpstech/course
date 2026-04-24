// Institution → Programmes
document.getElementById("institution").addEventListener("change", function () {
  let id = this.value;

  fetch("../api/ajax/profile-fetcher.php?action=getProgrammes&institution_id=" + id)
    .then((res) => res.json())
    .then((data) => {
      let programme = document.getElementById("programme");
      programme.innerHTML = '<option value="">Select Programme</option>';

      data.forEach((item) => {
        programme.innerHTML += `<option value="${item.id}">${item.name}</option>`;
      });

      document.getElementById("department").innerHTML =
        '<option value="">Select Department</option>';
    });
});

// Programme → Departments + Levels
document.getElementById("programme").addEventListener("change", function () {
  let id = this.value;

  fetch("../api/ajax/profile-fetcher.php?action=getProgrammeMeta&programme_id=" + id)
    .then((res) => res.json())
    .then((data) => {

      // Populate Departments
      let department = document.getElementById("department");
      department.innerHTML = '<option value="">Select Department</option>';

      data.departments.forEach((item) => {
        department.innerHTML += `<option value="${item.id}">${item.name}</option>`;
      });

      // Populate Levels
      let level = document.querySelector("select[name='level_id']");
      level.innerHTML = '<option value="">Select Level</option>';

      data.levels.forEach((item) => {
        level.innerHTML += `<option value="${item.id}">${item.name}</option>`;
      });
    });
});
