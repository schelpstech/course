<?php
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function selected($actual, $expected): string
{
    return (string) $actual === (string) $expected ? 'selected' : '';
}

function grade_options($selected = ''): string
{
    $grades = ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9', 'ABS', 'AR'];
    $html = '<option value="">Grade</option>';
    foreach ($grades as $grade) {
        $html .= '<option value="' . h($grade) . '" ' . selected($selected, $grade) . '>' . h($grade) . '</option>';
    }

    return $html;
}

function exam_type_options($selected = ''): string
{
    $examTypes = ['WAEC SSCE', 'NECO SSCE', 'NABTEB', 'WAEC GCE', 'NECO GCE'];
    $html = '<option value="">Select Exam Type</option>';

    foreach ($examTypes as $type) {
        $html .= '<option value="' . h($type) . '" ' . selected($selected, $type) . '>' . h($type) . '</option>';
    }

    return $html;
}

function subject_options($selected = ''): string
{
    $subjects = [
        'English Language',
        'Mathematics',
        'Biology',
        'Chemistry',
        'Physics',
        'Agricultural Science',
        'Economics',
        'Government',
        'Literature in English',
        'Christian Religious Studies',
        'Islamic Religious Studies',
        'Geography',
        'History',
        'Commerce',
        'Financial Accounting',
        'Civic Education',
        'Computer Studies',
        'Further Mathematics',
        'Yoruba',
        'Igbo',
        'Hausa'
    ];

    $html = '<option value="">Select Subject</option>';
    foreach ($subjects as $subject) {
        $html .= '<option value="' . h($subject) . '" ' . selected($selected, $subject) . '>' . h($subject) . '</option>';
    }

    return $html;
}

function doc_label(string $type): string
{
    return ucwords(str_replace('_', ' ', $type));
}

function document_map(array $full): array
{
    $docs = [];
    foreach ($full['documents'] ?? [] as $doc) {
        $docs[$doc['document_type']] = $doc;
    }

    return $docs;
}

function document_status_badge(?array $document): string
{
    if (!$document) {
        return '<span class="badge rounded-pill bg-light text-muted">Not uploaded</span>';
    }

    $status = $document['validation_status'] ?? 'pending';
    $class = match ($status) {
        'valid' => 'bg-success',
        'rejected' => 'bg-danger',
        default => 'bg-warning text-dark'
    };

    return '<span class="badge rounded-pill ' . $class . '">' . h(ucfirst($status)) . '</span>';
}

function form_action_buttons(string $nextPane = '', string $saveLabel = 'Save Draft', string $continueLabel = 'Save & Continue'): void
{
    ?>
    <div class="form-actions">
        <button class="btn btn-outline-secondary" type="submit" data-save-mode="draft">
            <i class="bi bi-cloud-check me-1"></i>
            <?= h($saveLabel) ?>
        </button>

        <button class="btn btn-primary" type="submit" data-save-mode="continue" data-next-pane="<?= h($nextPane) ?>">
            <?= h($continueLabel) ?>
            <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
    <?php
}

function can_open_step(array $completion, string $step): bool
{
    $requirements = [
        'bio' => ['application_fee_paid'],
        'contact' => ['application_fee_paid', 'bio'],
        'academic' => ['application_fee_paid', 'bio', 'contact'],
        'olevel' => ['application_fee_paid', 'bio', 'contact', 'academic'],
        'programme' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel'],
        'documents' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme'],
        'preview' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme', 'documents'],
        'final' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme', 'documents']
    ];

    foreach ($requirements[$step] ?? [] as $required) {
        if (empty($completion[$required])) {
            return false;
        }
    }

    return true;
}
