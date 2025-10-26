<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    
    // Set user offline
    setUserOffline($_SESSION['user_id']);
}

session_destroy();
header("Location: login.php");
exit();
?>