<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
/* questoè un redirect semplice semplice per uscire */
logoutUtente();
header('Location: ' . SITE_URL . '/index.php');
exit;
