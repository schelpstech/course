<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-blue">
            <i class="bi bi-person-vcard"></i>
        </div>
        <div>
            <span class="section-kicker">Section 1</span>
            <h5>Biodata</h5>
            <p>Enter your personal details exactly as they appear on your credentials.</p>
        </div>
    </div>

    <form class="ajax-form admission-step-form" data-endpoint="../api/admission/save-step.php" data-step-key="bio">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="step" value="bio">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Surname</label>
                <input class="form-control" name="surname" value="<?= h($full['surname'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input class="form-control" name="first_name" value="<?= h($full['first_name'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-4">
                <label class="form-label">Other Name</label>
                <input class="form-control" name="other_name" value="<?= h($full['other_name'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-4">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="date_of_birth" value="<?= h($full['date_of_birth'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Gender</option>
                    <option value="Male" <?= selected($full['gender'] ?? '', 'Male') ?>>Male</option>
                    <option value="Female" <?= selected($full['gender'] ?? '', 'Female') ?>>Female</option>
                    <option value="Other" <?= selected($full['gender'] ?? '', 'Other') ?>>Other</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Religion</label>
                <select class="form-select" name="religion" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Religion</option>
                    <option value="Christianity" <?= selected($full['religion'] ?? '', 'Christianity') ?>>Christianity</option>
                    <option value="Islam" <?= selected($full['religion'] ?? '', 'Islam') ?>>Islam</option>
                    <option value="Traditional" <?= selected($full['religion'] ?? '', 'Traditional') ?>>Traditional</option>
                    <!-- Add more religions as needed -->
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nationality</label>
                <select class="form-select" name="nationality" required <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Select Nationality</option>
                    <option value="Nigeria" <?= selected($full['nationality'] ?? '', 'Nigeria') ?>>Nigeria</option>
                    <option value="Non-Nigerian" <?= selected($full['nationality'] ?? '', 'Non-Nigerian') ?>>Non-Nigerian</option>
                    <!-- Add more nationalities as needed -->
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">State of Origin</label>
                <input class="form-control" name="state_of_origin" value="<?= h($full['state_of_origin'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-4">
                <label class="form-label">Local Government</label>
                <input class="form-control" name="local_government" value="<?= h($full['local_government'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>
        </div>

        <?php if (!$isLocked) form_action_buttons('contactPane'); ?>
    </form>
</div>
