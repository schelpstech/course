<?php

require_once __DIR__ . '../start.inc.php';

set_time_limit(300);

try {
    if (!isset($utility)) {
        throw new Exception("Utility class not initialized");
    }

    $utility->backupDatabase();

    file_put_contents(
        __DIR__ . '/logs/backup.log',
        date('Y-m-d H:i:s') . " - DB backup successful\n",
        FILE_APPEND
    );

} catch (Throwable $e) {

    error_log("DB Backup Error: " . $e->getMessage());

    file_put_contents(
        __DIR__ . '/logs/backup_error.log',
        date('Y-m-d H:i:s') . " - DB backup failed: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
}