<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_POST['content'])) {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $post_id, $content]);
    }
}

header("Location: index.php");
exit();
?>