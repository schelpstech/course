<?php

require_once __DIR__ . '/../start.inc.php';

set_time_limit(900);

try {
    if (!isset($utility)) {
        throw new Exception("Utility class not initialized");
    }

    $utility->backupFiles();

    file_put_contents(
        __DIR__ . '/logs/backup.log',
        date('Y-m-d H:i:s') . " - File backup successful\n",
        FILE_APPEND
    );

} catch (Throwable $e) {

    error_log("File Backup Error: " . $e->getMessage());

    file_put_contents(
        __DIR__ . '/logs/backup_error.log',
        date('Y-m-d H:i:s') . " - File backup failed: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
}