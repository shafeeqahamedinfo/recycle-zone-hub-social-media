<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_id'])) {
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    
    error_log("Delete comment attempt - Comment ID: $comment_id, User ID: $user_id");
    
    // Check if comment exists and user has permission
    $stmt = $pdo->prepare("SELECT c.user_id, p.user_id as post_owner_id 
                           FROM comments c 
                           JOIN posts p ON c.post_id = p.id 
                           WHERE c.id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        $_SESSION['error'] = "Comment not found";
        error_log("Comment not found: $comment_id");
    } elseif ($comment['user_id'] != $user_id && $comment['post_owner_id'] != $user_id) {
        $_SESSION['error'] = "You can only delete your own comments or comments on your posts";
        error_log("User $user_id doesn't have permission to delete comment $comment_id");
    } else {
        // Delete the comment
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        if ($stmt->execute([$comment_id])) {
            $_SESSION['success'] = "Comment deleted successfully";
            error_log("Comment $comment_id deleted successfully");
        } else {
            $_SESSION['error'] = "Failed to delete comment";
            error_log("Failed to delete comment $comment_id");
        }
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

header("Location: " . ($_POST['redirect'] ?? 'index.php'));
exit();
?>