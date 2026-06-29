<?php
$announcementRows = [];

try {
    $announcementRows = $announcementService->adminList();
} catch (Throwable $e) {
    $announcementRows = [];
}

$activeAnnouncements = 0;
$scheduledAnnouncements = 0;
$expiredAnnouncements = 0;
$now = time();

foreach ($announcementRows as $announcementRow) {
    $starts = strtotime($announcementRow['start_date']);
    $ends = strtotime($announcementRow['end_date']);

    if ((int)$announcementRow['is_active'] === 1 && $starts <= $now && $ends >= $now) {
        $activeAnnouncements++;
    } elseif ($starts > $now) {
        $scheduledAnnouncements++;
    } elseif ($ends < $now) {
        $expiredAnnouncements++;
    }
}

$announcementTokens = [
    'save' => $utility->generateCsrf('announcement_save'),
    'toggle' => $utility->generateCsrf('announcement_toggle')
];
?>

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Total Notices</span>
                <h3 class="mb-0"><?= (int)count($announcementRows); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Live</span>
                <h3 class="mb-0"><?= (int)$activeAnnouncements; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Scheduled</span>
                <h3 class="mb-0"><?= (int)$scheduledAnnouncements; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Expired</span>
                <h3 class="mb-0"><?= (int)$expiredAnnouncements; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Announcements</h5>
                <button type="button" class="btn btn-primary" id="addAnnouncementBtn">
                    <i class="ph ph-plus"></i> New Notice
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="announcementsTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Notice</th>
                                <th>Window</th>
                                <th>Visibility</th>
                                <th>Target</th>
                                <th>Reads</th>
                                <th>Status</th>
                                <th>Acknowledgement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="announcementForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="announcementModalTitle">New Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="announcement_id" name="id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcement_title" name="title" maxlength="180" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Visibility</label>
                            <select class="form-control" id="announcement_visibility" name="visibility" required>
                                <option value="all">All Students</option>
                                <option value="institution">Institution</option>
                                <option value="programme">Programme</option>
                                <option value="department">Department</option>
                                <option value="level">Level</option>
                            </select>
                        </div>

                        <div class="col-md-6 announcement-target-field" data-target-level="institution">
                            <label class="form-label">Institution</label>
                            <select class="form-control" id="announcement_institution_id" name="institution_id"></select>
                        </div>
                        <div class="col-md-6 announcement-target-field" data-target-level="programme">
                            <label class="form-label">Programme</label>
                            <select class="form-control" id="announcement_programme_id" name="programme_id"></select>
                        </div>
                        <div class="col-md-6 announcement-target-field" data-target-level="department">
                            <label class="form-label">Department</label>
                            <select class="form-control" id="announcement_department_id" name="department_id"></select>
                        </div>
                        <div class="col-md-6 announcement-target-field" data-target-level="level">
                            <label class="form-label">Level</label>
                            <select class="form-control" id="announcement_level_id" name="level_id"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" id="announcement_start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" id="announcement_end_date" name="end_date" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="announcement_is_active" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="announcement_must_read" name="must_read" value="1" checked>
                                <label class="form-check-label" for="announcement_must_read">Requires acknowledgement</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Notice</label>
                            <textarea class="form-control" id="announcement_body" name="body" rows="6" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.announcementConfig = {
        csrf: <?= json_encode($announcementTokens); ?>
    };
</script>
