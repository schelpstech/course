$(document).ready(function () {

    loadPaymentTerms();
    loadInstitutions();

});


/**
 * ----------------------------------------
 * LOAD INSTITUTIONS
 * ----------------------------------------
 */
function loadInstitutions(callback = null) {

    $.getJSON("../api/admin/ajax/endpoint/getInstitution.php", function (res) {

        let opt = "<option value=''>Select Institution</option>";

        (res.data || []).forEach((inst) => {
            opt += `<option value="${inst.id}">${inst.name}</option>`;
        });

        $("#institution_id").html(opt);

        if (typeof callback === "function") {
            callback();
        }
    });
}


/**
 * ----------------------------------------
 * LOAD PAYMENT TERMS TABLE
 * ----------------------------------------
 */
function loadPaymentTerms() {

    $.getJSON("../api/admin/ajax/payment/getPaymentTerms.php", function (res) {

        let rows = "";

        (res.data || []).forEach((item, index) => {

            rows += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.institution_name}</td>
                    <td>${item.min_percent}%</td>
                    <td>
                        <span class="badge ${item.status == 1 ? 'bg-success' : 'bg-danger'}">
                            ${item.status == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning editBtn" data-id="${item.id}">
                            Edit
                        </button>
                    </td>
                </tr>
            `;
        });

        $("#paymentTable tbody").html(rows);
    });
}


/**
 * ----------------------------------------
 * OPEN MODAL (ADD)
 * ----------------------------------------
 */
$("#addPaymentBtn").on("click", function () {

    $("#paymentForm")[0].reset();

    $("#payment_id").val("");

    $("#paymentModalTitle").text("Add Payment Terms");

    loadInstitutions();

    $("#paymentModal").modal("show");
});


/**
 * ----------------------------------------
 * EDIT PAYMENT TERMS
 * ----------------------------------------
 */
$(document).on("click", ".editBtn", function () {

    let id = $(this).data("id");

    $.post("../api/admin/ajax/payment/getSinglePaymentTerm.php", { id: id }, function (res) {

        if (res.status === "success") {

            let data = res.data;

            $("#paymentForm")[0].reset();

            $("#payment_id").val(data.id);
            $("#min_percent").val(data.min_percent);
            $("#status").val(data.status);

            $("#paymentModalTitle").text("Edit Payment Terms");

            loadInstitutions(function () {
                $("#institution_id").val(data.institution_id);
            });

            $("#paymentModal").modal("show");

        } else {
            Swal.fire("Error", res.message, "error");
        }

    }, "json")
    .fail(function () {
        Swal.fire("Error", "Failed to fetch record", "error");
    });
});


/**
 * ----------------------------------------
 * SUBMIT FORM (ADD / EDIT)
 * ----------------------------------------
 */
$("#paymentForm").submit(function (e) {
    e.preventDefault();

    let data = {
        id: $("#payment_id").val(),
        institution_id: $("#institution_id").val(),
        min_percent: $("#min_percent").val(),
        status: $("#status").val()
    };

    $.post("../api/admin/ajax/payment/savePaymentTerms.php", data, function (res) {

        if (res.status === "success") {

            Swal.fire("Success", res.message, "success");

            $("#paymentModal").modal("hide");

            $("#paymentForm")[0].reset();

            loadPaymentTerms();

        } else {
            Swal.fire("Error", res.message, "error");
        }

    }, "json")
    .fail(function () {
        Swal.fire("Error", "Request failed. Please try again.", "error");
    });
});