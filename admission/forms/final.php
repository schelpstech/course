<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-success">
            <i class="bi bi-send-check"></i>
        </div>
        <div>
            <span class="section-kicker">Section 8</span>
            <h5>Final Submission</h5>
            <p>Submit only after confirming that every section and uploaded credential is correct.</p>
        </div>
    </div>

    <div class="final-submit-panel">
        <div>
            <h6>Application Lock Notice</h6>
            <p>
                After final submission, your admission form will be locked and moved to the screening queue.
                You will still be able to print your application slip and track your status.
            </p>
        </div>

        <div class="final-status">
            <span>Current Status</span>
            <strong><?= h($application['form_status']) ?></strong>
        </div>
    </div>

    <?php if (!$isLocked && can_open_step($completion, 'final')): ?>
        <form class="ajax-form final-submit-form mt-4" data-endpoint="../api/admission/submit-application.php">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
            <button class="btn btn-success btn-lg" type="submit">
                <i class="bi bi-send-check me-1"></i>
                Submit Application
            </button>
        </form>
    <?php elseif (!$isLocked): ?>
        <div class="alert alert-warning mt-4 mb-0">Complete all sections and required uploads before final submission.</div>
    <?php else: ?>
        <div class="alert alert-info mt-4 mb-0">This application has been submitted and is now locked.</div>
    <?php endif; ?>
</div>
