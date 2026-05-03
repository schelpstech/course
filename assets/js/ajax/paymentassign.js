let feeTable;

$(document).ready(function () {
  initDataTable();
  init();
});

/**
 * ----------------------------------------
 * GLOBAL AJAX WRAPPER
 * ----------------------------------------
 */
function api(url, method = "GET", data = {}) {
  return $.ajax({
    url,
    method,
    data,
    dataType: "json",
  });
}

/**
 * ----------------------------------------
 * LOADER (simple hook)
 * ----------------------------------------
 */
function setLoading(state = true) {
  if (state) {
    $("body").addClass("loading");
  } else {
    $("body").removeClass("loading");
  }
}

/**
 * ----------------------------------------
 * DATATABLE INIT
 * ----------------------------------------
 */
function initDataTable() {
  feeTable = $("#feeTable").DataTable({
    dom: "Bfrtip",
    buttons: ["excelHtml5", "pdfHtml5", "print"],
    pageLength: 10,
    responsive: true,
  });
}

/**
 * ----------------------------------------
 * INIT
 * ----------------------------------------
 */
function init() {
  loadFees();
  loadSessions();
  loadInstitutions();

  bindEvents();
}

/**
 * ----------------------------------------
 * EVENT BINDINGS
 * ----------------------------------------
 */
function bindEvents() {
  $("#addFeeBtn").on("click", openAddModal);

  $("#institution_id").on("change", loadProgrammes);
  $("#programme_id").on("change", loadDepartments);
  $("#department_id").on("change", loadLevels);

  $("#feeForm").on("submit", saveFee);

  $(document).on("click", ".editBtn", editFee);
  $(document).on("click", ".toggleStatusBtn", toggleStatus);
}

/**
 * ----------------------------------------
 * LOAD DROPDOWNS (SAFE)
 * ----------------------------------------
 */
async function loadSessions(callback = null) {
  const res = await api("../api/admin/ajax/endpoint/getSessions.php");

  let opt = "<option value=''>Select Session</option>";
  (res.data || []).forEach((s) => {
    opt += `<option value="${s.id}">${s.name}</option>`;
  });

  $("#session_id").html(opt);
  if (callback) callback();
}

async function loadInstitutions(callback = null) {
  const res = await api("../api/admin/ajax/endpoint/getInstitution.php");

  let opt = "<option value=''>Select Institution</option>";
  (res.data || []).forEach((i) => {
    opt += `<option value="${i.id}">${i.name}</option>`;
  });

  $("#institution_id").html(opt);
  if (callback) callback();
}

/**
 * ----------------------------------------
 * CASCADING DROPDOWNS (ASYNC SAFE)
 * ----------------------------------------
 */
async function loadProgrammes() {
  let institution_id = $("#institution_id").val();

  resetSelect("#programme_id", "Select Programme");
  resetSelect("#department_id", "Select Department");
  resetSelect("#level_id", "Select Level");

  if (!institution_id) return;

  const res = await api(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    "GET",
    { institution_id }
  );

  fillSelect("#programme_id", res.data, "Select Programme");
}

async function loadDepartments() {
  let programme_id = $("#programme_id").val();

  resetSelect("#department_id", "Select Department");
  resetSelect("#level_id", "Select Level");

  if (!programme_id) return;

  const res = await api(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    "GET",
    { programme_id }
  );

  fillSelect("#department_id", res.data, "Select Department");
}

async function loadLevels() {
  let department_id = $("#department_id").val();

  resetSelect("#level_id", "Select Level");

  if (!department_id) return;

  const res = await api(
    "../api/admin/ajax/endpoint/getLevelsByDepartment.php",
    "GET",
    { department_id }
  );

  fillSelect("#level_id", res.data, "Select Level");
}

/**
 * ----------------------------------------
 * HELPERS
 * ----------------------------------------
 */
function resetSelect(selector, label) {
  $(selector).html(`<option value=''>${label}</option>`);
}

function fillSelect(selector, data, label) {
  let opt = `<option value=''>${label}</option>`;
  (data || []).forEach((d) => {
    opt += `<option value="${d.id}">${d.name}</option>`;
  });
  $(selector).html(opt);
}

/**
 * ----------------------------------------
 * LOAD FEES TABLE (OPTIMIZED)
 * ----------------------------------------
 */
async function loadFees() {
  setLoading(true);

  const res = await api("../api/admin/ajax/payment/getFees.php");

  let dataSet = [];

  (res.data || []).forEach((item, index) => {
    const amount = Number(item.amount || 0);

    dataSet.push([
      index + 1,
      item.session_name,
      item.department_name,
      item.level_name,
      "₦" + amount.toLocaleString(),
      `<span class="badge ${
        item.status == 1 ? "bg-success" : "bg-danger"
      }">
        ${item.status == 1 ? "Active" : "Inactive"}
      </span>`,
      `
        <button class="btn btn-sm btn-warning editBtn" data-id="${item.id}">
          Edit
        </button>
        <hr>
        <button class="btn btn-sm btn-secondary toggleStatusBtn"
          data-id="${item.id}" data-status="${item.status}">
          ${item.status == 1 ? "Deactivate" : "Activate"}
        </button>
      `,
    ]);
  });

  feeTable.clear().rows.add(dataSet).draw(false);

  setLoading(false);
}

/**
 * ----------------------------------------
 * OPEN ADD MODAL
 * ----------------------------------------
 */
function openAddModal() {
  $("#feeForm")[0].reset();
  $("#fee_id").val("");

  $("#feeModalTitle").text("Assign School Fee");

  loadSessions();
  loadInstitutions();

  resetSelect("#programme_id", "Select Programme");
  resetSelect("#department_id", "Select Department");
  resetSelect("#level_id", "Select Level");

  $("#feeModal").modal("show");
}

/**
 * ----------------------------------------
 * EDIT
 * ----------------------------------------
 */
async function editFee() {
  let id = $(this).data("id");

  const res = await api("../api/admin/ajax/payment/getSingleFee.php", "POST", {
    id,
  });

  if (res.status !== "success") {
    return Swal.fire("Error", res.message, "error");
  }

  const d = res.data;

  $("#fee_id").val(d.id);
  $("#amount").val(d.amount);
  $("#status").val(d.status);

  await populateEdit(d);

  $("#feeModalTitle").text("Edit School Fee");
  $("#feeModal").modal("show");
}

/**
 * ----------------------------------------
 * EDIT POPULATION (CLEAN FLOW)
 * ----------------------------------------
 */
async function populateEdit(data) {
  await loadSessions();
  $("#session_id").val(data.session_id);

  await loadInstitutions();
  $("#institution_id").val(data.institution_id);

  const prog = await api(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    "GET",
    { institution_id: data.institution_id }
  );
  fillSelect("#programme_id", prog.data, "Select Programme");
  $("#programme_id").val(data.programme_id);

  const dept = await api(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    "GET",
    { programme_id: data.programme_id }
  );
  fillSelect("#department_id", dept.data, "Select Department");
  $("#department_id").val(data.department_id);

  const lvl = await api(
    "../api/admin/ajax/endpoint/getLevelsByDepartment.php",
    "GET",
    { department_id: data.department_id }
  );
  fillSelect("#level_id", lvl.data, "Select Level");
  $("#level_id").val(data.level_id);
}

/**
 * ----------------------------------------
 * SAVE
 * ----------------------------------------
 */
async function saveFee(e) {
  e.preventDefault();

  const payload = {
    id: $("#fee_id").val(),
    session_id: $("#session_id").val(),
    department_id: $("#department_id").val(),
    level_id: $("#level_id").val(),
    amount: $("#amount").val(),
    status: $("#status").val(),
  };

  const res = await api(
    "../api/admin/ajax/payment/saveFee.php",
    "POST",
    payload
  );

  if (res.status === "success") {
    Swal.fire("Success", res.message, "success");
    $("#feeModal").modal("hide");
    loadFees();
  } else {
    Swal.fire("Error", res.message, "error");
  }
}

/**
 * ----------------------------------------
 * TOGGLE STATUS
 * ----------------------------------------
 */
async function toggleStatus() {
  let id = $(this).data("id");
  let status = $(this).data("status");

  const res = await api(
    "../api/admin/ajax/payment/toggleFeeStatus.php",
    "POST",
    {
      id,
      status: status == 1 ? 0 : 1,
    }
  );

  if (res.status === "success") {
    Swal.fire("Success", res.message, "success");
    loadFees();
  } else {
    Swal.fire("Error", res.message, "error");
  }
}