<?php
require_once '../includes/config.php';

// Destroy the session
session_destroy();

// Redirect to home page
redirect(SITE_URL . '/index.php');
?> 