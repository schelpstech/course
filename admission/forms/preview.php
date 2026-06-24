<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-dark">
            <i class="bi bi-eye"></i>
        </div>
        <div>
            <span class="section-kicker">Section 7</span>
            <h5>Application Preview</h5>
            <p>Review your information carefully before final submission.</p>
        </div>
    </div>

    <div class="preview-grid">
        <div class="preview-item">
            <span>Full Name</span>
            <strong><?= h(trim(($full['surname'] ?? '') . ' ' . ($full['first_name'] ?? '') . ' ' . ($full['other_name'] ?? ''))) ?: 'Not provided' ?></strong>
        </div>
        <div class="preview-item">
            <span>Email</span>
            <strong><?= h($full['contact_email'] ?? $full['applicant_email'] ?? 'Not provided') ?></strong>
        </div>
        <div class="preview-item">
            <span>Phone</span>
            <strong><?= h($full['contact_phone'] ?? $full['applicant_phone'] ?? 'Not provided') ?></strong>
        </div>
        <div class="preview-item">
            <span>Institution</span>
            <strong><?= h($full['institution_name'] ?? 'Not selected') ?></strong>
        </div>
        <div class="preview-item">
            <span>Programme</span>
            <strong><?= h($full['programme_name'] ?? 'Not selected') ?></strong>
        </div>
        <div class="preview-item">
            <span>Department</span>
            <strong><?= h($full['department_name'] ?? 'Not selected') ?></strong>
        </div>
        <div class="preview-item">
            <span>Mode of Entry</span>
            <strong><?= h($full['mode_of_entry'] ?? 'Not selected') ?></strong>
        </div>
        <div class="preview-item">
            <span>Application Status</span>
            <strong><?= h($application['form_status']) ?></strong>
        </div>
    </div>
    <div class="section-subcard mt-4">
        <h6>Saved Sections</h6>
        <div class="completion-check-grid">
            <?php
            $previewChecks = [
                'bio' => 'Biodata',
                'contact' => 'Contact',
                'academic' => 'Academic History',
                'olevel' => "O'Level",
                'programme' => 'Programme',
                'documents' => 'Documents'
            ];
            foreach ($previewChecks as $key => $label):
            ?>
                <div class="completion-check <?= !empty($completion[$key]) ? 'complete' : '' ?>">
                    <i class="bi <?= !empty($completion[$key]) ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                    <span><?= h($label) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
