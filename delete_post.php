<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Debug info
    error_log("Delete post attempt - Post ID: $post_id, User ID: $user_id");
    
    // Check if post belongs to user
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        $_SESSION['error'] = "Post not found";
        error_log("Post not found: $post_id");
    } elseif ($post['user_id'] != $user_id) {
        $_SESSION['error'] = "You can only delete your own posts";
        error_log("User $user_id tried to delete post $post_id owned by {$post['user_id']}");
    } else {
        // Simple delete - just remove from database
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        if ($stmt->execute([$post_id])) {
            $_SESSION['success'] = "Post deleted successfully";
            error_log("Post $post_id deleted successfully");
            
            // Also delete associated likes and comments
            $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$post_id]);
            $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$post_id]);
        } else {
            $_SESSION['error'] = "Failed to delete post";
            error_log("Failed to delete post $post_id");
        }
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

// Redirect back
header("Location: " . ($_POST['redirect'] ?? 'index.php'));
exit();
?>