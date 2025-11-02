<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'social_media');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Get current user data
function getCurrentUser() {
    global $pdo;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}


// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'mov', 'avi', 'webm']);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// File upload function
function handleFileUpload($file, $type = 'image') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Maximum size: 50MB'];
    }

    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    if ($type === 'image' && !in_array($fileExtension, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid image format. Allowed: ' . implode(', ', ALLOWED_IMAGE_TYPES)];
    }
    
    if ($type === 'video' && !in_array($fileExtension, ALLOWED_VIDEO_TYPES)) {
        return ['success' => false, 'error' => 'Invalid video format. Allowed: ' . implode(', ', ALLOWED_VIDEO_TYPES)];
    }

    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $filepath = UPLOAD_DIR . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Generate thumbnail for video
        if ($type === 'video') {
            $thumbnail = generateVideoThumbnail($filepath, $filename);
            return ['success' => true, 'filename' => $filename, 'thumbnail' => $thumbnail];
        }
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Failed to upload file'];
}

// Generate video thumbnail
function generateVideoThumbnail($videoPath, $filename) {
    $thumbnailName = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $thumbnailPath = UPLOAD_DIR . $thumbnailName;
    
    // Use FFmpeg to generate thumbnail (requires FFmpeg installed)
    $command = "ffmpeg -i \"$videoPath\" -ss 00:00:01 -vframes 1 -q:v 2 \"$thumbnailPath\" 2>&1";
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($thumbnailPath)) {
        return $thumbnailName;
    }
    
    // Fallback: return a default video thumbnail
    return 'video_placeholder.jpg';
}

// Get location name from coordinates (using OpenStreetMap Nominatim)
function getLocationName($latitude, $longitude) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude&zoom=18&addressdetails=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SocialMediaApp/1.0');
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['display_name'])) {
        return $data['display_name'];
    }
    
    return "Location: $latitude, $longitude";
}

// ... existing config code ...

// Enhanced profile image upload function with better error handling
function handleProfileImageUpload($file, $type = 'profile') {
    // Check if uploads directory exists and is writable
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return ['success' => false, 'error' => 'Upload directory does not exist and could not be created'];
        }
    }
    
    if (!is_writable(UPLOAD_DIR)) {
        return ['success' => false, 'error' => 'Upload directory is not writable'];
    }

    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file selected'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        return ['success' => false, 'error' => $errorMessages[$file['error']] ?? 'Unknown upload error'];
    }

    // Check file size (max 5MB for profile images)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size: 5MB'];
    }

    if ($file['size'] == 0) {
        return ['success' => false, 'error' => 'File is empty'];
    }

    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid image format. Allowed: ' . implode(', ', $allowedTypes)];
    }

    // Generate unique filename
    $filename = $type . '_' . uniqid() . '_' . time() . '.' . $fileExtension;
    $filepath = UPLOAD_DIR . $filename;

    // Validate image using getimagesize
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['success' => false, 'error' => 'Uploaded file is not a valid image'];
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
        return ['success' => false, 'error' => 'Invalid image MIME type'];
    }

    // Simple file copy for testing - remove GD requirements initially
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        // Try alternative method if move_uploaded_file fails
        if (copy($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'error' => 'Failed to save uploaded file. Check directory permissions.'];
        }
    }
}

// Simple version without GD requirements
function handleProfileImageUploadSimple($file, $type = 'profile') {
    // Check uploads directory
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    if (!is_writable(UPLOAD_DIR)) {
        return ['success' => false, 'error' => 'Upload directory is not writable'];
    }

    // Basic file checks
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
    }

    if ($file['size'] > (5 * 1024 * 1024)) {
        return ['success' => false, 'error' => 'File too large. Maximum size: 5MB'];
    }

    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid image format'];
    }

    // Generate unique filename
    $filename = $type . '_' . uniqid() . '_' . time() . '.' . $fileExtension;
    $filepath = UPLOAD_DIR . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Failed to upload file'];
}

// Delete old profile image
function deleteOldProfileImage($filename) {
    if ($filename && $filename !== 'default.jpg' && file_exists(UPLOAD_DIR . $filename)) {
        // Don't delete if other users might be using it
        if (strpos($filename, 'profile_') === 0 || strpos($filename, 'cover_') === 0) {
            unlink(UPLOAD_DIR . $filename);
            return true;
        }
    }
    return false;
}

// ... existing code ...

// Post deletion function
function deletePost($post_id, $user_id) {
    global $pdo;
    
    // Check if post belongs to user
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post || $post['user_id'] != $user_id) {
        return ['success' => false, 'error' => 'You can only delete your own posts'];
    }
    
    // Soft delete (recommended)
    $stmt = $pdo->prepare("UPDATE posts SET is_deleted = TRUE WHERE id = ?");
    if ($stmt->execute([$post_id])) {
        return ['success' => true, 'message' => 'Post deleted successfully'];
    }
    
    return ['success' => false, 'error' => 'Failed to delete post'];
}

// Hard delete function (permanent deletion)
function hardDeletePost($post_id, $user_id) {
    global $pdo;
    
    // Check if post belongs to user
    $stmt = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post || $post['user_id'] != $user_id) {
        return ['success' => false, 'error' => 'You can only delete your own posts'];
    }
    
    // Delete associated files
    if (!empty($post['image'])) {
        $file_path = UPLOAD_DIR . $post['image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete video thumbnail if exists
        if ($post['post_type'] === 'video') {
            $stmt = $pdo->prepare("SELECT video_thumbnail FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $video_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($video_data['video_thumbnail']) && $video_data['video_thumbnail'] !== 'video_placeholder.jpg') {
                $thumb_path = UPLOAD_DIR . $video_data['video_thumbnail'];
                if (file_exists($thumb_path)) {
                    unlink($thumb_path);
                }
            }
        }
    }
    
    // Delete associated likes and comments first (due to foreign key constraints)
    $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$post_id]);
    
    // Delete the post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$post_id])) {
        return ['success' => true, 'message' => 'Post permanently deleted'];
    }
    
    return ['success' => false, 'error' => 'Failed to delete post'];
}

// Comment deletion function
function deleteComment($comment_id, $user_id) {
    global $pdo;
    
    // Check if comment belongs to user or if user owns the post
    $stmt = $pdo->prepare("SELECT c.user_id, p.user_id as post_owner_id 
                           FROM comments c 
                           JOIN posts p ON c.post_id = p.id 
                           WHERE c.id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        return ['success' => false, 'error' => 'Comment not found'];
    }
    
    // Allow deletion if user is comment author OR post owner
    if ($comment['user_id'] != $user_id && $comment['post_owner_id'] != $user_id) {
        return ['success' => false, 'error' => 'You can only delete your own comments or comments on your posts'];
    }
    
    // Soft delete
    $stmt = $pdo->prepare("UPDATE comments SET is_deleted = TRUE WHERE id = ?");
    if ($stmt->execute([$comment_id])) {
        return ['success' => true, 'message' => 'Comment deleted successfully'];
    }
    
    return ['success' => false, 'error' => 'Failed to delete comment'];
}

// Hard delete comment
function hardDeleteComment($comment_id, $user_id) {
    global $pdo;
    
    // Check permissions
    $stmt = $pdo->prepare("SELECT c.user_id, p.user_id as post_owner_id 
                           FROM comments c 
                           JOIN posts p ON c.post_id = p.id 
                           WHERE c.id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        return ['success' => false, 'error' => 'Comment not found'];
    }
    
    if ($comment['user_id'] != $user_id && $comment['post_owner_id'] != $user_id) {
        return ['success' => false, 'error' => 'You can only delete your own comments or comments on your posts'];
    }
    
    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    if ($stmt->execute([$comment_id])) {
        return ['success' => true, 'message' => 'Comment permanently deleted'];
    }
    
    return ['success' => false, 'error' => 'Failed to delete comment'];
}

// Get posts with deletion check
function getPostsWithDeletionCheck($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_picture, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND is_deleted = FALSE) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.is_deleted = FALSE 
          AND (p.user_id = ? OR p.user_id IN (
            SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
          ))
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get comments with deletion check
function getCommentsWithDeletionCheck($post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_picture 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? AND c.is_deleted = FALSE
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ... existing code ...

// Update user online status
function updateUserOnlineStatus($user_id, $is_online = true) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET is_online = ?, last_activity = NOW() WHERE id = ?");
    return $stmt->execute([$is_online, $user_id]);
}

// Update last seen timestamp
function updateLastSeen($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW(), last_activity = NOW() WHERE id = ?");
    return $stmt->execute([$user_id]);
}

// Check if user is online (within last 5 minutes)
function isUserOnline($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT is_online, last_activity FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) return false;
    
    // Consider user online if they've been active in the last 5 minutes
    $last_activity = strtotime($user['last_activity']);
    $five_minutes_ago = time() - 300; // 5 minutes in seconds
    
    return $user['is_online'] && $last_activity >= $five_minutes_ago;
}

// Get user's last seen time
function getLastSeen($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT last_seen FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user ? $user['last_seen'] : null;
}

// Format last seen time for display
function formatLastSeen($timestamp) {
    if (!$timestamp) return 'Never';
    
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) { // Less than 1 minute
        return 'Just now';
    } elseif ($diff < 3600) { // Less than 1 hour
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) { // Less than 1 day
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) { // Less than 1 week
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Get online friends count
function getOnlineFriendsCount($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as online_count 
        FROM users u 
        WHERE u.id IN (
            SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
        )
        AND u.is_online = TRUE 
        AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$user_id, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['online_count'] : 0;
}

// Get online friends list
function getOnlineFriends($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.profile_picture, u.last_activity
        FROM users u 
        WHERE u.id IN (
            SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
        )
        AND u.is_online = TRUE 
        AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY u.last_activity DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all friends with online status
function getFriendsWithOnlineStatus($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (u.is_online = TRUE AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_currently_online
        FROM users u 
        WHERE u.id IN (
            SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
        )
        ORDER BY 
            (u.is_online = TRUE AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) DESC,
            u.last_activity DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update current user's activity
function updateCurrentUserActivity() {
    if (isset($_SESSION['user_id'])) {
        updateLastSeen($_SESSION['user_id']);
    }
}

// Initialize user online status when they login
function setUserOnline($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET is_online = TRUE, last_activity = NOW(), last_seen = NOW() WHERE id = ?");
    return $stmt->execute([$user_id]);
}

// Set user offline (when they logout)
function setUserOffline($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET is_online = FALSE, last_seen = NOW() WHERE id = ?");
    return $stmt->execute([$user_id]);
}

// Clean up inactive users (mark as offline if inactive for more than 5 minutes)
function cleanupInactiveUsers() {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET is_online = FALSE WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND is_online = TRUE");
    return $stmt->execute();
}

// Call this function on every page load to update activity
updateCurrentUserActivity();


function getPostsWithPrivacyCheck($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.username, 
               u.profile_picture,
               u.full_name,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
               EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
               EXISTS(SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = p.user_id AND status = 'accepted') 
                      OR (user_id = p.user_id AND friend_id = ? AND status = 'accepted')) as is_friend
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE (p.privacy = 'public' 
               OR (p.privacy = 'friends' AND (
                   EXISTS(SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = p.user_id AND status = 'accepted') 
                          OR (user_id = p.user_id AND friend_id = ? AND status = 'accepted'))
                   OR p.user_id = ?
               )))
        ORDER BY p.created_at DESC
    ");
    
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get all public posts for public viewing
function getPublicPosts() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.username, 
               u.profile_picture,
               u.full_name,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.privacy = 'public'
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get total user count
function getTotalUserCount() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE active = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

// Get total post count
function getTotalPostCount() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE privacy = 'public'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}
?>