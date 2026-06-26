$(function () {
    const toast = (icon, title) => Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 3500
    });

    const csrf = () => $('#admissionAdminCsrf').val() || $('[name="csrf_token"]').first().val();
    const adminAdmissionBase = '../api/admin/ajax/admission/';
    const applicantAdmissionBase = '../api/admission/';

    function ajaxError(xhr, fallback) {
        const response = xhr.responseJSON || {};
        return response.message || fallback;
    }

    function showModal(selector) {
        const element = document.querySelector(selector);
        if (!element) return;

        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(element).show();
            return;
        }

        if ($.fn.modal) {
            $(element).modal('show');
            return;
        }

        $(element).addClass('show').show().attr('aria-modal', 'true').removeAttr('aria-hidden');
        $('body').addClass('modal-open');
    }

    function hideModal(selector) {
        const element = document.querySelector(selector);
        if (!element) return;

        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getInstance(element)?.hide();
            return;
        }

        if ($.fn.modal) {
            $(element).modal('hide');
            return;
        }

        $(element).removeClass('show').hide().attr('aria-hidden', 'true').removeAttr('aria-modal');
        $('body').removeClass('modal-open');
    }

    function setOptions(selector, rows, selected, placeholder) {
        const field = $(selector);
        field.html(`<option value="">${placeholder}</option>`);
        (rows || []).forEach((row) => {
            const option = $('<option>').val(row.id).text(row.name);
            if (String(row.id) === String(selected || '')) {
                option.prop('selected', true);
            }
            field.append(option);
        });
    }

    function loadProgrammes(targetSelector, institutionId, selected = '', placeholder = 'Select Programme') {
        setOptions(targetSelector, [], '', placeholder);
        if (!institutionId) {
            return $.Deferred().resolve().promise();
        }

        return $.getJSON(applicantAdmissionBase + 'get-programmes.php', { institution_id: institutionId })
            .done((response) => {
                setOptions(targetSelector, response.data || [], selected, placeholder);
                if (selected) {
                    $(targetSelector).val(String(selected)).trigger('change.select2');
                }
            })
            .fail((xhr) => {
                toast('error', ajaxError(xhr, 'Unable to load programmes'));
            });
    }

    function loadDepartments(targetSelector, programmeId, selected = '', placeholder = 'Select Department') {
        setOptions(targetSelector, [], '', placeholder);
        if (!programmeId) {
            return $.Deferred().resolve().promise();
        }

        return $.getJSON(applicantAdmissionBase + 'get-departments.php', { programme_id: programmeId })
            .done((response) => {
                setOptions(targetSelector, response.data || [], selected, placeholder);
                if (selected) {
                    $(targetSelector).val(String(selected)).trigger('change.select2');
                }
            })
            .fail((xhr) => {
                toast('error', ajaxError(xhr, 'Unable to load departments'));
            });
    }

    $('#admissionSessionForm').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: adminAdmissionBase + 'saveSession.php',
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
                toast('error', ajaxError(xhr, 'Unable to save admission session'));
            }
        });
    });

    $(document).on('click', '.editAdmissionSession', function () {
        $('#admissionSessionId').val($(this).data('id'));
        $('#admissionAcademicSession').val($(this).data('session'));
        $('#admissionApplicationFee').val($(this).data('application-fee'));
        $('#admissionAcceptanceFee').val($(this).data('acceptance-fee'));
        $('.institution-acceptance-fee').val('');
        const institutionFees = $(this).data('institution-fees') || {};
        Object.entries(institutionFees).forEach(([institutionId, amount]) => {
            $(`.institution-acceptance-fee[data-institution-id="${institutionId}"]`).val(amount);
        });
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
            url: adminAdmissionBase + 'fetchApplications.php',
            data() {
                return {
                    status: $('#admissionStatusFilter').val(),
                    session_id: $('#admissionSessionFilter').val(),
                    institution_id: $('#admissionInstitutionFilter').val(),
                    programme_id: $('#admissionProgrammeFilter').val(),
                    department_id: $('#admissionDepartmentFilter').val(),
                    gender: $('#admissionGenderFilter').val(),
                    state: $('#admissionStateFilter').val(),
                    mode_of_entry: $('#admissionModeFilter').val(),
                    payment_status: $('#admissionPaymentFilter').val(),
                    search: $('#admissionSearchFilter').val()
                };
            }
        };

        if (applicationsTable) {
            applicationsTable.ajax.reload();
            return;
        }

        applicationsTable = $('.admissionApplicationsTable').DataTable({
            ajax: ajaxConfig,
            autoWidth: false,
            scrollX: false,
            columns: [
                { data: 'applicant', width: '18%' },
                { data: 'numbers', width: '14%' },
                { data: 'programme', width: '24%' },
                { data: 'session', width: '12%' },
                { data: 'status', width: '14%' },
                { data: 'submitted', width: '10%' },
                { data: 'actions', orderable: false, searchable: false, width: '8%', className: 'text-end' }
            ],
            pageLength: 10,
            order: [[5, 'desc']],
            drawCallback() {
                $('.admissionApplicationsTable .dropdown-toggle').dropdown?.();
            }
        });
    }

    $('#reloadAdmissionApplications').on('click', loadApplications);
    $('#admissionSearchFilter').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadApplications();
        }
    });

    $('#admissionInstitutionFilter').on('change', function () {
        loadProgrammes('#admissionProgrammeFilter', $(this).val(), '', 'All Programmes');
        setOptions('#admissionDepartmentFilter', [], '', 'All Departments');
    });

    $('#admissionProgrammeFilter').on('change', function () {
        loadDepartments('#admissionDepartmentFilter', $(this).val(), '', 'All Departments');
    });

    if ($('#admissionInstitutionFilter').val()) {
        loadProgrammes('#admissionProgrammeFilter', $('#admissionInstitutionFilter').val(), '', 'All Programmes');
    }

    loadApplications();

    function submitAdmissionDecision(applicationId, action, remarks, rejectionReason = '', afterSuccess = null) {
        $.ajax({
            url: adminAdmissionBase + 'screeningAction.php',
            method: 'POST',
            data: {
                csrf_token: csrf(),
                application_id: applicationId,
                action,
                remarks,
                rejection_reason: rejectionReason
            },
            success(response) {
                toast(response.status ? 'success' : 'error', response.message);
                if (response.status && applicationsTable) {
                    applicationsTable.ajax.reload();
                }
                if (response.status && typeof afterSuccess === 'function') {
                    afterSuccess();
                }
            },
            error(xhr) {
                toast('error', ajaxError(xhr, 'Unable to update application'));
            }
        });
    }

    function refreshApplicationModal(applicationId) {
        if (!$('#admissionApplicationModalBody').length) return;

        $('#admissionApplicationModalBody').html('<div class="text-center text-muted py-5">Refreshing applicant details...</div>');
        $.getJSON(adminAdmissionBase + 'applicationDetails.php', { application_id: applicationId })
            .done((response) => {
                if (!response.status) {
                    toast('error', response.message || 'Unable to refresh applicant');
                    return;
                }
                $('#admissionApplicationModalTitle').text(response.title || 'Applicant Details');
                $('#admissionApplicationModalBody').html(response.html);
            })
            .fail((xhr) => {
                $('#admissionApplicationModalBody').html('<div class="alert alert-danger mb-0">' + ajaxError(xhr, 'Unable to refresh applicant') + '</div>');
            });
    }

    function migrateAdmissionApplicant(applicationId) {
        if (!applicationId) {
            toast('error', 'Application record not found.');
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Migrate applicant?',
            text: 'This will create or update the student user record and generate the matric number.',
            showCancelButton: true,
            confirmButtonText: 'Yes, migrate'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: adminAdmissionBase + 'migrateApplicant.php',
                method: 'POST',
                data: {
                    csrf_token: csrf(),
                    application_id: applicationId
                },
                success(response) {
                    toast(response.status ? 'success' : 'error', response.message);
                    if (!response.status) return;

                    if (applicationsTable) {
                        applicationsTable.ajax.reload();
                    }

                    if (String($('#modalApplicationId').val() || '') === String(applicationId)) {
                        refreshApplicationModal(applicationId);
                    }
                },
                error(xhr) {
                    toast('error', ajaxError(xhr, 'Unable to migrate applicant'));
                }
            });
        });
    }

    $(document).on('click', '.admissionAction', async function () {
        const applicationId = $(this).data('id');
        const action = $(this).data('action');
        const actionLabels = {
            pending: 'Mark Pending Review',
            review: 'Move Under Review',
            recommend: 'Recommend',
            approve: 'Offer Admission',
            offer: 'Offer Admission',
            accept: 'Mark Accepted',
            reverse: 'Reverse Decision',
            allow_edit: 'Allow Applicant Edit',
            reject: 'Reject',
            remark: 'Add Remark'
        };

        const prompt = await Swal.fire({
            title: (actionLabels[action] || 'Update') + ' Application',
            input: 'textarea',
            inputLabel: 'Remarks visible to applicant',
            inputPlaceholder: 'Enter remarks',
            showCancelButton: true,
            confirmButtonText: 'Submit'
        });

        if (!prompt.isConfirmed) {
            return;
        }

        submitAdmissionDecision(applicationId, action, prompt.value || '');
    });

    $(document).on('click', '.migrateAdmissionApplicant, #submitModalStudentMigration', function () {
        const applicationId = $(this).data('id') || $('#modalApplicationId').val();
        migrateAdmissionApplicant(applicationId);
    });

    $(document).on('click', '.viewAdmissionApplication', function () {
        const applicationId = $(this).data('id');
        $('#admissionApplicationModalTitle').text('Applicant Details');
        $('#admissionApplicationModalBody').html('<div class="text-center text-muted py-5">Loading applicant details...</div>');
        showModal('#admissionApplicationModal');

        $.getJSON(adminAdmissionBase + 'applicationDetails.php', { application_id: applicationId })
            .done((response) => {
                if (!response.status) {
                    toast('error', response.message || 'Unable to load applicant');
                    return;
                }
                $('#admissionApplicationModalTitle').text(response.title || 'Applicant Details');
                $('#admissionApplicationModalBody').html(response.html);
            })
            .fail((xhr) => {
                $('#admissionApplicationModalBody').html('<div class="alert alert-danger mb-0">' + ajaxError(xhr, 'Unable to load applicant') + '</div>');
            });
    });

    $(document).on('click', '#submitModalAdmissionDecision', function () {
        submitAdmissionDecision(
            $('#modalApplicationId').val(),
            $('#modalAdmissionAction').val(),
            $('#modalAdmissionRemarks').val(),
            $('#modalRejectionReason').val(),
            () => refreshApplicationModal($('#modalApplicationId').val())
        );
    });

    $(document).on('click', '.admissionDocumentPreview', function (event) {
        event.preventDefault();
        $('#admissionDocumentLightboxImage').attr('src', $(this).attr('href'));
        showModal('#admissionDocumentLightbox');
    });

    let criteriaTable = null;
    function loadCriteria() {
        if (!$('.admissionCriteriaTable').length) {
            return;
        }

        const ajaxConfig = {
            url: adminAdmissionBase + 'fetchCriteria.php',
            data() {
                return {
                    institution_id: $('#criteriaFilterInstitution').val(),
                    status: $('#criteriaFilterStatus').val()
                };
            }
        };

        if (criteriaTable) {
            criteriaTable.ajax.reload();
            return;
        }

        criteriaTable = $('.admissionCriteriaTable').DataTable({
            ajax: ajaxConfig,
            autoWidth: false,
            scrollX: false,
            columns: [
                { data: 'institution' },
                { data: 'programme' },
                { data: 'olevel' },
                { data: 'jamb' },
                { data: 'documents' },
                { data: 'status' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            pageLength: 10
        });
    }

    function resetCriteriaForm() {
        $('#admissionCriteriaForm')[0]?.reset();
        $('#criteriaId').val('');
        setOptions('#criteriaProgramme', [], '', 'Select Programme');
        $('.criteria-document-option').prop('checked', false);
        $('.criteria-document-option[value="passport"], .criteria-document-option[value="birth_certificate"], .criteria-document-option[value="olevel_result"]').prop('checked', true);
        $('#criteriaJambRequired').prop('checked', true);
    }

    $('#criteriaInstitution').on('change', function () {
        loadProgrammes('#criteriaProgramme', $(this).val());
    });

    $('#duplicateCriteriaInstitution').on('change', function () {
        loadProgrammes('#duplicateCriteriaProgramme', $(this).val());
    });

    if ($('#criteriaInstitution').val()) {
        loadProgrammes('#criteriaProgramme', $('#criteriaInstitution').val(), $('#criteriaProgramme').data('selected') || '');
    }

    $('#reloadAdmissionCriteria').on('click', loadCriteria);
    $('#resetCriteriaForm').on('click', resetCriteriaForm);

    $('#admissionCriteriaForm').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: adminAdmissionBase + 'saveCriteria.php',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success(response) {
                toast(response.status ? 'success' : 'error', response.message);
                if (response.status) {
                    resetCriteriaForm();
                    loadCriteria();
                }
            },
            error(xhr) {
                toast('error', ajaxError(xhr, 'Unable to save criteria'));
            }
        });
    });

    $(document).on('click', '.editAdmissionCriteria', function () {
        const record = JSON.parse($(this).attr('data-criteria'));
        $('#criteriaId').val(record.id);
        $('#criteriaInstitution').val(record.institution_id);
        loadProgrammes('#criteriaProgramme', record.institution_id, record.programme_id)
            .done(() => $('#criteriaProgramme').val(String(record.programme_id)));
        $('#criteriaMinimumCredits').val(record.minimum_credits);
        $('#criteriaMaximumSittings').val(record.maximum_sittings);
        $('#criteriaCompulsorySubjects').val((record.compulsory_subjects || []).join(', '));
        $('#criteriaAcceptableSubjects').val((record.acceptable_subjects || []).join(', '));
        $('#criteriaMinimumJambScore').val(record.minimum_jamb_score);
        $('#criteriaStatus').val(record.status);
        $('#criteriaJambRequired').prop('checked', Number(record.jamb_registration_required) === 1);

        const standardKeys = $('.criteria-document-option').map(function () { return this.value; }).get();
        $('.criteria-document-option').prop('checked', false);
        const customDocuments = [];
        (record.required_documents || []).forEach((key) => {
            const checkbox = $(`.criteria-document-option[value="${key}"]`);
            if (checkbox.length) {
                checkbox.prop('checked', true);
            } else if (!standardKeys.includes(key)) {
                customDocuments.push((record.document_labels && record.document_labels[key]) || key.replaceAll('_', ' '));
            }
        });
        $('#criteriaAdditionalDocuments').val(customDocuments.join('\n'));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    $(document).on('click', '.toggleAdmissionCriteria', function () {
        $.ajax({
            url: adminAdmissionBase + 'toggleCriteria.php',
            method: 'POST',
            data: {
                csrf_token: csrf(),
                id: $(this).data('id'),
                status: $(this).data('status')
            },
            success(response) {
                toast(response.status ? 'success' : 'error', response.message);
                if (response.status) loadCriteria();
            },
            error(xhr) {
                toast('error', ajaxError(xhr, 'Unable to update criteria'));
            }
        });
    });

    $(document).on('click', '.duplicateAdmissionCriteria', function () {
        $('#duplicateCriteriaForm')[0]?.reset();
        $('#duplicateCriteriaSourceId').val($(this).data('id'));
        setOptions('#duplicateCriteriaProgramme', [], '', 'Select Programme');
        showModal('#duplicateCriteriaModal');
    });

    $('#duplicateCriteriaForm').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: adminAdmissionBase + 'duplicateCriteria.php',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success(response) {
                toast(response.status ? 'success' : 'error', response.message);
                if (response.status) {
                    hideModal('#duplicateCriteriaModal');
                    loadCriteria();
                }
            },
            error(xhr) {
                toast('error', ajaxError(xhr, 'Unable to duplicate criteria'));
            }
        });
    });

    function chartSeries(rows) {
        return {
            labels: (rows || []).map((row) => row.label || 'Unknown'),
            totals: (rows || []).map((row) => Number(row.total || 0))
        };
    }

    function renderFallbackChart(selector, rows) {
        const target = $(selector);
        if (!target.length || target.data('rendered')) return;

        const items = (rows || []).filter((row) => Number(row.total || 0) > 0);
        if (!items.length) {
            target
                .data('rendered', true)
                .html('<div class="text-center text-muted py-5">No admission data available yet.</div>');
            return;
        }

        const max = Math.max(...items.map((row) => Number(row.total || 0)), 1);
        const html = items.map((row) => {
            const total = Number(row.total || 0);
            const width = Math.max(8, Math.round((total / max) * 100));
            return `
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>${row.label || 'Unknown'}</span>
                        <strong>${total}</strong>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: ${width}%"></div>
                    </div>
                </div>
            `;
        }).join('');

        target.data('rendered', true).html(`<div class="py-3">${html}</div>`);
    }

    function renderApexChart(selector, options, fallbackRows) {
        const element = document.querySelector(selector);
        if (!element || $(element).data('rendered')) return;

        renderFallbackChart(selector, fallbackRows);
    }

    function renderAdmissionDashboardCharts() {
        if (!window.admissionDashboardData) {
            return;
        }

        const data = window.admissionDashboardData;
        const institution = chartSeries(data.institution);
        const programme = chartSeries(data.programme);
        const status = chartSeries(data.status);
        const trend = chartSeries(data.trend);

        renderApexChart('#admissionInstitutionChart', {
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                series: [{ name: 'Applications', data: institution.totals }],
                xaxis: { categories: institution.labels },
                plotOptions: { bar: { borderRadius: 4, horizontal: true } },
                dataLabels: { enabled: false }
            }, data.institution);

        renderApexChart('#admissionStatusChart', {
                chart: { type: 'donut', height: 300 },
                labels: status.labels,
                series: status.totals,
                legend: { position: 'bottom' }
            }, data.status);

        renderApexChart('#admissionProgrammeChart', {
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                series: [{ name: 'Applications', data: programme.totals }],
                xaxis: { categories: programme.labels },
                plotOptions: { bar: { borderRadius: 4 } },
                dataLabels: { enabled: false }
            }, data.programme);

        renderApexChart('#admissionTrendChart', {
                chart: { type: 'area', height: 300, toolbar: { show: false } },
                series: [{ name: 'Applications', data: trend.totals }],
                xaxis: { categories: trend.labels },
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: { enabled: false }
            }, data.trend);
    }

    loadCriteria();
    renderAdmissionDashboardCharts();
    setTimeout(renderAdmissionDashboardCharts, 300);
    $(window).on('load', renderAdmissionDashboardCharts);
});
