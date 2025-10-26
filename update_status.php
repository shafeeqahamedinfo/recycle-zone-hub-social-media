<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $user_id = $_SESSION['user_id'];
    
    if ($status === 'online') {
        $result = setUserOnline($user_id);
    } else {
        $result = setUserOffline($user_id);
    }
    
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>