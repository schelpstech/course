<div class="application-form-card" id="documents">
    <div class="form-card-header">
        <div class="section-icon section-teal">
            <i class="bi bi-folder2-open"></i>
        </div>
        <div>
            <span class="section-kicker">Section 6</span>
            <h5>Document Uploads</h5>
            <p>Upload all required credentials. Passport photograph must be JPG or PNG and not more than 15KB.</p>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($requiredDocuments as $type): $doc = $documents[$type] ?? null; ?>
            <div class="col-xl-6">
                <form class="upload-form document-upload-card h-100" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <input type="hidden" name="document_type" value="<?= h($type) ?>">

                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h6 class="mb-1"><?= h(doc_label($type)) ?></h6>
                            <small class="text-muted">
                                <?= $type === 'passport' ? 'JPG/PNG, max 15KB' : 'PDF/JPG/PNG, max 2MB' ?>
                            </small>
                        </div>
                        <?= document_status_badge($doc) ?>
                    </div>

                    <div class="mt-3">
                        <input type="file" class="form-control" name="document" <?= $isLocked ? 'disabled' : '' ?> required>
                    </div>

                    <?php if ($doc): ?>
                        <div class="uploaded-file mt-3">
                            <i class="bi bi-paperclip"></i>
                            <span><?= h($doc['original_name']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isLocked): ?>
                        <button class="btn btn-outline-primary btn-sm mt-3" type="submit">
                            <i class="bi bi-upload me-1"></i>
                            Upload Document
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$isLocked): ?>
        <div class="form-actions">
            <?php if (!empty($completion['documents'])): ?>
                <button class="btn btn-primary advance-only" type="button" data-next-pane="previewPane">
                    Continue to Preview
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>
            <?php else: ?>
                <span class="text-muted">Upload all required documents to continue.</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
