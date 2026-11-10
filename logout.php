<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
session_start();
$_SESSION['flash_success'] = "You have been securely logged out.";
header("Location: " . BASE_URL . "/login.php");
exit;

