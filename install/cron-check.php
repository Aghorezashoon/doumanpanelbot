<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Tehran');

require_once __DIR__ . '/checks.php';

douman_install_probe_record();

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo 'DOUMAN_CRON_PROBE_OK';
