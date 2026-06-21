$(function () {
    const toast = (icon, title) => Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 3500
    });

    $('#admissionSessionForm').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: '../api/admin/ajax/admission/saveSession.php',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success(response) {
                toast(response.status ? 'success' : 'error', response.message);
                if (response.status) {
                    setTimeout(() => window.location.reload(), 700);
                }
            },
            error(xhr) {
                const response = xhr.responseJSON || {};
                toast('error', response.message || 'Unable to save admission session');
            }
        });
    });

    $(document).on('click', '.editAdmissionSession', function () {
        $('#admissionSessionId').val($(this).data('id'));
        $('#admissionAcademicSession').val($(this).data('session'));
        $('#admissionApplicationFee').val($(this).data('application-fee'));
        $('#admissionAcceptanceFee').val($(this).data('acceptance-fee'));
        $('#admissionStartDate').val($(this).data('start'));
        $('#admissionEndDate').val($(this).data('end'));
        $('#admissionStatus').val($(this).data('status'));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    let applicationsTable = null;
    function loadApplications() {
        if (!$('.admissionApplicationsTable').length) {
            return;
        }

        const ajaxConfig = {
            url: '../api/admin/ajax/admission/fetchApplications.php',
            data() {
                return {
                    status: $('#admissionStatusFilter').val(),
                    session_id: $('#admissionSessionFilter').val()
                };
            }
        };

        if (applicationsTable) {
            applicationsTable.ajax.reload();
            return;
        }

        applicationsTable = $('.admissionApplicationsTable').DataTable({
            ajax: ajaxConfig,
            columns: [
                { data: 'applicant' },
                { data: 'numbers' },
                { data: 'programme' },
                { data: 'session' },
                { data: 'status' },
                { data: 'submitted' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [[5, 'desc']]
        });
    }

    $('#reloadAdmissionApplications').on('click', loadApplications);
    loadApplications();

    $(document).on('click', '.admissionAction', async function () {
        const applicationId = $(this).data('id');
        const action = $(this).data('action');
        const csrf = $('#admissionAdminCsrf').val();

        const prompt = await Swal.fire({
            title: action.charAt(0).toUpperCase() + action.slice(1) + ' Application',
            input: 'textarea',
            inputLabel: 'Screening remarks',
            inputPlaceholder: 'Enter remarks',
            showCancelButton: true,
            confirmButtonText: 'Submit'
        });

        if (!prompt.isConfirmed) {
            return;
        }

        $.ajax({
            url: '../api/admin/ajax/admission/screeningAction.php',
            method: 'POST',
            data: {
                csrf_token: csrf,
                application_id: applicationId,
                action,
                remarks: prompt.value || ''
            },
            success(response) {
                toast(response.status ? 'success' : 'error', response.message);
                if (response.status && applicationsTable) {
                    applicationsTable.ajax.reload();
                }
            },
            error(xhr) {
                const response = xhr.responseJSON || {};
                toast('error', response.message || 'Unable to update application');
            }
        });
    });
});
