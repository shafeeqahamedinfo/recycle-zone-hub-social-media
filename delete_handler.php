<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? 0;
    $redirect = $_POST['redirect'] ?? 'index.php';
    $user_id = $_SESSION['user_id'];

    error_log("Delete attempt - Type: $type, ID: $id, User: $user_id");

    // Validate input
    if (!in_array($type, ['post', 'comment']) || !$id) {
        $_SESSION['error'] = "Invalid request parameters";
        header("Location: index.php");
        exit();
    }

    if ($type === 'post') {
        // Verify post ownership
        $stmt = $pdo->prepare("SELECT user_id, image, post_type FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $_SESSION['error'] = "Post not found";
        } elseif ($post['user_id'] != $user_id) {
            $_SESSION['error'] = "You can only delete your own posts";
            error_log("Permission denied: User $user_id tried to delete post $id owned by {$post['user_id']}");
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();

                // Delete associated files
                if (!empty($post['image'])) {
                    $file_path = UPLOAD_DIR . $post['image'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                        error_log("Deleted file: $file_path");
                    }

                    // Delete video thumbnail if exists
                    if ($post['post_type'] === 'video') {
                        $thumb_stmt = $pdo->prepare("SELECT video_thumbnail FROM posts WHERE id = ?");
                        $thumb_stmt->execute([$id]);
                        $thumb_data = $thumb_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!empty($thumb_data['video_thumbnail']) && $thumb_data['video_thumbnail'] !== 'video_placeholder.jpg') {
                            $thumb_path = UPLOAD_DIR . $thumb_data['video_thumbnail'];
                            if (file_exists($thumb_path)) {
                                unlink($thumb_path);
                                error_log("Deleted thumbnail: $thumb_path");
                            }
                        }
                    }
                }

                // Delete associated likes
                $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$id]);
                error_log("Deleted likes for post: $id");

                // Delete associated comments
                $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$id]);
                error_log("Deleted comments for post: $id");

                // Delete the post
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $pdo->commit();
                    $_SESSION['success'] = "Post deleted successfully";
                    error_log("Successfully deleted post: $id");
                } else {
                    throw new Exception("Failed to delete post from database");
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to delete post: " . $e->getMessage();
                error_log("Delete post error: " . $e->getMessage());
            }
        }

    } else { // comment
        // Verify comment permissions
        $stmt = $pdo->prepare("
            SELECT c.user_id, p.user_id as post_owner_id 
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            $_SESSION['error'] = "Comment not found";
        } elseif ($comment['user_id'] != $user_id && $comment['post_owner_id'] != $user_id) {
            $_SESSION['error'] = "You can only delete your own comments or comments on your posts";
            error_log("Permission denied for comment: $id");
        } else {
            // Delete the comment
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['success'] = "Comment deleted successfully";
                error_log("Successfully deleted comment: $id");
            } else {
                $_SESSION['error'] = "Failed to delete comment";
                error_log("Failed to delete comment: $id");
            }
        }
    }

} else {
    $_SESSION['error'] = "Invalid request method";
}

header("Location: $redirect");
exit();
?>