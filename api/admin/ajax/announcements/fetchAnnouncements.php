<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_announcements');

try {
    $rows = $announcementService->adminList();
    $data = [];
    $now = time();

    foreach ($rows as $row) {
        $starts = strtotime($row['start_date']);
        $ends = strtotime($row['end_date']);
        $isActive = (int)$row['is_active'] === 1;
        $timeline = 'Scheduled';
        $timelineClass = 'warning';

        if ($isActive && $starts <= $now && $ends >= $now) {
            $timeline = 'Live';
            $timelineClass = 'success';
        } elseif ($ends < $now) {
            $timeline = 'Expired';
            $timelineClass = 'secondary';
        } elseif (!$isActive) {
            $timeline = 'Disabled';
            $timelineClass = 'danger';
        }

        $toggleLabel = $isActive ? 'Disable' : 'Enable';
        $toggleClass = $isActive ? 'btn-outline-danger' : 'btn-outline-success';
        $bodyPreview = substr(trim(preg_replace('/\s+/', ' ', $row['body'] ?? '')), 0, 140);

        $data[] = [
            'title' => '<strong>' . htmlspecialchars($row['title']) . '</strong><br><small class="text-muted">' . htmlspecialchars($bodyPreview) . '</small>',
            'window' => htmlspecialchars(date('M d, Y h:i A', $starts) . ' - ' . date('M d, Y h:i A', $ends)),
            'visibility' => '<span class="badge bg-light text-dark">' . htmlspecialchars(ucfirst($row['visibility'])) . '</span>',
            'target' => htmlspecialchars($announcementService->targetLabel($row)),
            'read_count' => (int)$row['read_count'],
            'status' => '<span class="badge bg-' . $timelineClass . '">' . $timeline . '</span>',
            'must_read' => (int)$row['must_read'] === 1 ? '<span class="badge bg-primary">Required</span>' : '<span class="badge bg-secondary">Optional</span>',
            'actions' => '
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-primary editAnnouncement" data-id="' . (int)$row['id'] . '">
                        <i class="ph ph-pencil-simple"></i>
                    </button>
                    <button type="button" class="btn ' . $toggleClass . ' toggleAnnouncement" data-id="' . (int)$row['id'] . '">
                        ' . $toggleLabel . '
                    </button>
                </div>'
        ];
    }

    echo json_encode([
        'status' => true,
        'data' => $data
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
exit;
