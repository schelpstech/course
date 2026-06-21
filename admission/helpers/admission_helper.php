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

function can_open_step(array $completion, string $step): bool
{
    $requirements = [
        'bio' => ['application_fee_paid'],
        'contact' => ['application_fee_paid', 'bio'],
        'academic' => ['application_fee_paid', 'bio', 'contact'],
        'olevel' => ['application_fee_paid', 'bio', 'contact', 'academic'],
        'programme' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel'],
        'documents' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme'],
        'preview' => ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme', 'documents']
    ];

    foreach ($requirements[$step] ?? [] as $required) {
        if (empty($completion[$required])) {
            return false;
        }
    }

    return true;
}