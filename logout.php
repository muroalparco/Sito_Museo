<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUtente();
header('Location: ' . SITE_URL . '/index.php');
exit;
