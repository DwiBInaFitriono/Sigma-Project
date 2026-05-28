<?php
// Mencegah Laravel memotong prefix "/api" dari URL karena Vercel meletakkan index di folder "api"
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/../public/index.php';