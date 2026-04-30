<?php
$auditLogs = $model->getRows('user_logs', [
    'select' => '
        user_logs.*,
        admins.fullname,
        admins.email
    ',
    'join' => [
        'admins' => ' ON admins.email = user_logs.user_id'
    ],
    'order_by' => 'user_logs.id DESC'
]);

$auditLogs = is_array($auditLogs) ? $auditLogs : [];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header table-card-header">
                <h5>Portal Audit Trail</h5>
                <small>Track all system activities</small>
            </div>

            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <table id="auditTable" class="table table-striped table-bordered dataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($auditLogs)): ?>
                                <?php $i = 1;
                                foreach ($auditLogs as $row):

                                    $name  = $row['fullname'] ?? 'Guest';
                                    $email = $row['email']  ?? '';

                                ?>
                                    <tr>

                                        <td><?= $i++; ?></td>

                                        <!-- USER -->
                                        <td>
                                            <?= htmlspecialchars($name); ?><br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($email); ?>
                                            </small>
                                        </td>

                                        <!-- ACTION -->
                                        <td>
                                            <?php
                                            $action = strtoupper($row['action'] ?? 'UNKNOWN');

                                            $badge = 'secondary';

                                            if (str_contains($action, 'CREATE')) $badge = 'success';
                                            elseif (str_contains($action, 'UPDATE')) $badge = 'info';
                                            elseif (str_contains($action, 'DELETE')) $badge = 'danger';
                                            elseif (str_contains($action, 'LOGIN')) $badge = 'primary';

                                            echo "<span class='badge bg-{$badge}'>{$action}</span>";
                                            ?>
                                        </td>

                                        <!-- IP -->
                                        <td><?= htmlspecialchars($row['ip_address'] ?? ''); ?></td>


                                        <!-- DATE -->
                                        <td>
                                            <?= date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No audit logs found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .device-col {
        max-width: 250px;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
</style>