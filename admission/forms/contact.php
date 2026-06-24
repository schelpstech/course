<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-green">
            <i class="bi bi-envelope-paper"></i>
        </div>
        <div>
            <span class="section-kicker">Section 2</span>
            <h5>Contact Information</h5>
            <p>Provide reliable contact details for admission updates and screening communication.</p>
        </div>
    </div>

    <form class="ajax-form admission-step-form" data-endpoint="../api/admission/save-step.php" data-step-key="contact">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="step" value="contact">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" value="<?= h($full['contact_email'] ?? $full['applicant_email'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input class="form-control" name="phone" value="<?= h($full['contact_phone'] ?? $full['applicant_phone'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-12">
                <label class="form-label">Residential Address</label>
                <textarea class="form-control" name="address" rows="4" required <?= $isLocked ? 'disabled' : '' ?>><?= h($full['address'] ?? '') ?></textarea>
            </div>
        </div>

        <?php if (!$isLocked) form_action_buttons('academicPane'); ?>
    </form>
</div>
