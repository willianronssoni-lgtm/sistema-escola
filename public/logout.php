<?php
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csfr_verify($_POST['csrf_token'] ?? '')) {
        logout();
    }
}
redirect('/biblioteca/public/index.php');