<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-indigo">
            <i class="bi bi-mortarboard"></i>
        </div>
        <div>
            <span class="section-kicker">Section 5</span>
            <h5>Programme Selection</h5>
            <p>Choose your admission pathway and preferred institution, programme, and department.</p>
        </div>
    </div>

    <?php if (!empty($full['history'])): ?>
        <div class="section-subcard mb-4">
            <h6 class="mb-2">Academic History Reference</h6>
            <div class="row g-2">
                <?php foreach ($full['history'] as $historyRow): ?>
                    <div class="col-md-6">
                        <div class="reference-pill">
                            <i class="bi bi-building"></i>
                            <span><?= h($historyRow['institution_name']) ?></span>
                            <small><?= h($historyRow['certificate_obtained']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form class="ajax-form admission-step-form" data-endpoint="../api/admission/save-step.php" data-step-key="programme">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="step" value="programme">

        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Mode of Entry</label>
                <select class="form-select" name="mode_of_entry" id="modeOfEntry" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Mode</option>
                    <option value="JAMB UTME" <?= selected($full['mode_of_entry'] ?? '', 'JAMB UTME') ?>>JAMB UTME</option>
                    <option value="Direct Entry" <?= selected($full['mode_of_entry'] ?? '', 'Direct Entry') ?>>Direct Entry</option>
                    <option value="Remedial" <?= selected($full['mode_of_entry'] ?? '', 'Remedial') ?>>Remedial</option>
                </select>
            </div>

            <div class="col-lg-4 jamb-field">
                <label class="form-label">JAMB Registration Number</label>
                <input class="form-control" name="jamb_registration_number" value="<?= h($full['jamb_registration_number'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-lg-4 jamb-field">
                <label class="form-label">JAMB Score</label>
                <input class="form-control" name="jamb_score" value="<?= h($full['jamb_score'] ?? '') ?>" inputmode="numeric" <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-lg-4">
                <label class="form-label">Institution</label>
                <select class="form-select linked-select" name="institution_id" id="institutionSelect" data-selected="<?= h($full['institution_id'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Institution</option>
                    <?php foreach ($institutions as $institution): ?>
                        <option value="<?= h($institution['id']) ?>" <?= selected($full['institution_id'] ?? '', $institution['id']) ?>>
                            <?= h($institution['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-4">
                <label class="form-label">Programme</label>
                <select class="form-select linked-select" name="programme_id" id="programmeSelect" data-selected="<?= h($full['programme_id'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Institution First</option>
                </select>
            </div>

            <div class="col-lg-4">
                <label class="form-label">Department</label>
                <select class="form-select" name="department_id" id="departmentSelect" data-selected="<?= h($full['department_id'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Programme First</option>
                </select>
            </div>
        </div>

        <?php if (!$isLocked) form_action_buttons('documentsPane'); ?>
    </form>
</div>
