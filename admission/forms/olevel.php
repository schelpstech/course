<?php
$olevelSittings = $full['sittings'] ?? [];
$selectedSittingCount = max(1, min(2, count($olevelSittings) ?: 1));
$defaultSubjects = ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Economics', 'Government', 'Civic Education', ''];
?>

<div class="application-form-card">
    <div class="form-card-header">
        <div class="section-icon section-purple">
            <i class="bi bi-journal-check"></i>
        </div>
        <div>
            <span class="section-kicker">Section 4</span>
            <h5>O'Level Results</h5>
            <p>Select your number of sittings and enter all subjects with matching grades.</p>
        </div>
    </div>

    <form class="ajax-form admission-step-form" id="olevelForm" data-endpoint="../api/admission/save-step.php" data-step-key="olevel" data-locked="<?= $isLocked ? '1' : '0' ?>">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="step" value="olevel">

        <div class="row g-3 align-items-end mb-4">
            <div class="col-md-4">
                <label class="form-label">Number of Sittings</label>
                <select class="form-select" id="sittingCount" name="sitting_count" <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="1" <?= selected($selectedSittingCount, 1) ?>>1 Sitting</option>
                    <option value="2" <?= selected($selectedSittingCount, 2) ?>>2 Sittings</option>
                </select>
            </div>
        </div>

        <div id="olevelSittingBlocks">
            <?php for ($sittingIndex = 0; $sittingIndex < 2; $sittingIndex++):
                $sitting = $olevelSittings[$sittingIndex] ?? [];
                $results = $sitting['results'] ?? [];
                $rowCount = max(9, count($results));
                $isHidden = $sittingIndex + 1 > $selectedSittingCount;
            ?>
                <div class="olevel-sitting-card <?= $isHidden ? 'd-none' : '' ?>" data-sitting-index="<?= (int) $sittingIndex ?>">
                    <div class="entry-toolbar">
                        <strong><?= $sittingIndex === 0 ? 'First Sitting' : 'Second Sitting' ?></strong>
                        <span class="badge rounded-pill bg-light text-muted"><?= $rowCount ?> subject row<?= $rowCount === 1 ? '' : 's' ?></span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Exam Type</label>
                            <select class="form-select olevel-field" name="sittings[<?= (int) $sittingIndex ?>][exam_type]" required <?= $isLocked ? 'disabled' : '' ?>>
                                <?= exam_type_options($sitting['exam_type'] ?? '') ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Exam Year</label>
                            <input class="form-control olevel-field" name="sittings[<?= (int) $sittingIndex ?>][exam_year]" value="<?= h($sitting['exam_year'] ?? '') ?>" inputmode="numeric" pattern="\d{4}" maxlength="4" required <?= $isLocked ? 'disabled' : '' ?>>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Exam Number</label>
                            <input class="form-control olevel-field" name="sittings[<?= (int) $sittingIndex ?>][exam_number]" value="<?= h($sitting['exam_number'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table result-entry-table mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th style="width: 180px;">Grade</th>
                                    <?php if (!$isLocked): ?><th style="width: 70px;"></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="subjectRows">
                                <?php for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++):
                                    $result = $results[$rowIndex] ?? [];
                                    $subject = $result['subject'] ?? ($defaultSubjects[$rowIndex] ?? '');
                                ?>
                                    <tr class="subject-row">
                                        <td>
                                            <select class="form-select olevel-field subject-select" name="sittings[<?= (int) $sittingIndex ?>][subjects][]" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <?= subject_options($subject) ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select olevel-field grade-select" name="sittings[<?= (int) $sittingIndex ?>][grades][]" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <?= grade_options($result['grade'] ?? '') ?>
                                            </select>
                                        </td>
                                        <?php if (!$isLocked): ?>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-danger remove-subject-row" type="button">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!$isLocked): ?>
                        <button class="btn btn-soft-primary btn-sm mt-3 add-subject-row" type="button">
                            <i class="bi bi-plus-circle me-1"></i>
                            Add Subject
                        </button>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>

        <?php if (!$isLocked) form_action_buttons('programmePane'); ?>
    </form>
</div>

<template id="subjectRowTemplate">
    <tr class="subject-row">
        <td>
            <select class="form-select olevel-field subject-select" name="__SUBJECT_NAME__" required>
                <?= subject_options() ?>
            </select>
        </td>
        <td>
            <select class="form-select olevel-field grade-select" name="__GRADE_NAME__" required>
                <?= grade_options() ?>
            </select>
        </td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-danger remove-subject-row" type="button">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    </tr>
</template>
