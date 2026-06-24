<?php
$historyRows = $full['history'] ?? [];
$formHistoryRows = $historyRows ?: [[
    'institution_name' => '',
    'certificate_obtained' => '',
    'location' => '',
    'start_year' => '',
    'end_year' => ''
]];
?>

<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-orange">
            <i class="bi bi-building-check"></i>
        </div>
        <div>
            <span class="section-kicker">Section 3</span>
            <h5>Academic History</h5>
            <p>Add every previous institution you attended. These records will be reused later in the workflow.</p>
        </div>
    </div>

    <form class="ajax-form admission-step-form" id="academicHistoryForm" data-endpoint="../api/admission/save-step.php" data-step-key="academic">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="step" value="academic">

        <div id="academicHistoryBlocks" class="dynamic-block-list">
            <?php foreach ($formHistoryRows as $index => $row): ?>
                <div class="dynamic-entry academic-entry" data-entry-index="<?= (int) $index ?>">
                    <div class="entry-toolbar">
                        <strong>Institution <span class="entry-number"><?= (int) $index + 1 ?></span></strong>
                        <?php if (!$isLocked): ?>
                            <button class="btn btn-sm btn-outline-danger remove-academic-entry" type="button">
                                <i class="bi bi-trash"></i>
                                Remove
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">Institution Name</label>
                            <input class="form-control" name="history[<?= (int) $index ?>][institution_name]" value="<?= h($row['institution_name'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Certificate Obtained</label>
                            <input class="form-control" name="history[<?= (int) $index ?>][certificate_obtained]" value="<?= h($row['certificate_obtained'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Location</label>
                            <input class="form-control" name="history[<?= (int) $index ?>][location]" value="<?= h($row['location'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                        </div>

                        <div class="col-sm-6 col-lg-1">
                            <label class="form-label">Start</label>
                            <input class="form-control" name="history[<?= (int) $index ?>][start_year]" value="<?= h($row['start_year'] ?? '') ?>" inputmode="numeric" pattern="\d{4}" maxlength="4" required <?= $isLocked ? 'disabled' : '' ?>>
                        </div>

                        <div class="col-sm-6 col-lg-1">
                            <label class="form-label">End</label>
                            <input class="form-control" name="history[<?= (int) $index ?>][end_year]" value="<?= h($row['end_year'] ?? '') ?>" inputmode="numeric" pattern="\d{4}" maxlength="4" <?= $isLocked ? 'disabled' : '' ?>>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$isLocked): ?>
            <button class="btn btn-soft-primary mt-3" id="addAcademicEntry" type="button">
                <i class="bi bi-plus-circle me-1"></i>
                Add Another Institution
            </button>

            <?php form_action_buttons('olevelPane'); ?>
        <?php endif; ?>
    </form>

    <div class="section-subcard mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h6 class="mb-1">Saved Institutions</h6>
                <small class="text-muted">Previously entered institutions load here automatically.</small>
            </div>
            <span class="badge rounded-pill bg-primary-subtle text-primary"><?= count($historyRows) ?> record<?= count($historyRows) === 1 ? '' : 's' ?></span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle saved-record-table mb-0">
                <thead>
                    <tr>
                        <th>Institution</th>
                        <th>Certificate</th>
                        <th>Location</th>
                        <th>Years</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyRows as $row): ?>
                        <tr>
                            <td><?= h($row['institution_name']) ?></td>
                            <td><?= h($row['certificate_obtained']) ?></td>
                            <td><?= h($row['location']) ?></td>
                            <td><?= h($row['start_year']) ?> - <?= h($row['end_year'] ?: 'Present') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$historyRows): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No academic history has been saved yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="academicEntryTemplate">
    <div class="dynamic-entry academic-entry" data-entry-index="__INDEX__">
        <div class="entry-toolbar">
            <strong>Institution <span class="entry-number">__NUMBER__</span></strong>
            <button class="btn btn-sm btn-outline-danger remove-academic-entry" type="button">
                <i class="bi bi-trash"></i>
                Remove
            </button>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Institution Name</label>
                <input class="form-control" name="history[__INDEX__][institution_name]" required>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Certificate Obtained</label>
                <input class="form-control" name="history[__INDEX__][certificate_obtained]" required>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Location</label>
                <input class="form-control" name="history[__INDEX__][location]" required>
            </div>
            <div class="col-sm-6 col-lg-1">
                <label class="form-label">Start</label>
                <input class="form-control" name="history[__INDEX__][start_year]" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
            </div>
            <div class="col-sm-6 col-lg-1">
                <label class="form-label">End</label>
                <input class="form-control" name="history[__INDEX__][end_year]" inputmode="numeric" pattern="\d{4}" maxlength="4">
            </div>
        </div>
    </div>
</template>
