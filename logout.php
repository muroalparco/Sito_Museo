<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

logoutUtente();
header('Location: ' . SITE_URL . '/index.php');
exit;
