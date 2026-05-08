<?php
$auditLogs = $model->getRows('user_logs_users', [
    'select' => '
        user_logs_users.*,
        users.name,
        users.email
    ',
    'join' => [
        'users' => ' ON users.email = user_logs_users.user_id'
    ],
    'order_by' => 'user_logs_users.id DESC'
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