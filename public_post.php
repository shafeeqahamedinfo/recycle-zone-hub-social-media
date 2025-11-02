<?php
require_once 'config.php';

// Get post ID from URL
$post_id = $_GET['id'] ?? 0;

// Get the specific public post
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture, u.full_name,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ? 
    AND p.is_deleted = FALSE 
    AND p.privacy = 'public'  -- Only show public posts
");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// If post doesn't exist or is not public, redirect
if (!$post) {
    header("Location: public.php");
    exit();
}

// Get comments for this post
$comment_stmt = $pdo->prepare("
    SELECT c.*, u.username, u.profile_picture 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    AND c.is_deleted = FALSE
    ORDER BY c.created_at ASC
");
$comment_stmt->execute([$post_id]);
$comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['username']); ?>'s Post - SocialConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .post-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        
        .post-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #6366f1;
        }
        
        .public-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container py-4">
        <div class="post-container">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="public.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Public Feed
                </a>
            </div>

            <!-- Main Post Card -->
            <div class="post-card p-4 p-md-5">
                <!-- Public Badge -->
                <div class="public-badge d-inline-flex align-items-center mb-4">
                    <i class="fas fa-globe-americas me-2"></i>
                    Public Post - Visible to Everyone
                </div>

                <!-- Post Header -->
                <div class="d-flex align-items-center mb-4">
                    <img src="uploads/<?php echo $post['profile_picture']; ?>" 
                         class="profile-pic me-3" 
                         alt="<?php echo htmlspecialchars($post['username']); ?>"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($post['username']); ?>&background=6366f1&color=fff'">
                    <div class="flex-grow-1">
                        <h4 class="mb-1"><?php echo htmlspecialchars($post['full_name'] ?? $post['username']); ?></h4>
                        <p class="text-muted mb-0">
                            @<?php echo htmlspecialchars($post['username']); ?> • 
                            <?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Post Content -->
                <?php if (!empty($post['content'])): ?>
                <div class="post-content mb-4">
                    <p style="font-size: 1.2rem; line-height: 1.7; color: #333;">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Post Media -->
                <?php if ($post['post_type'] === 'image' && $post['image']): ?>
                <div class="post-media mb-4 text-center">
                    <img src="uploads/<?php echo $post['image']; ?>" 
                         class="img-fluid rounded-3" 
                         alt="Post image"
                         style="max-height: 500px; object-fit: contain;">
                </div>
                <?php endif; ?>
                
                <?php if ($post['post_type'] === 'video' && $post['image']): ?>
                <div class="post-media mb-4">
                    <div class="ratio ratio-16x9">
                        <video controls class="rounded-3">
                            <source src="uploads/<?php echo $post['image']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($post['post_type'] === 'location' && $post['latitude']): ?>
                <div class="location-card bg-light p-4 rounded-3 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-map-marker-alt text-danger fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="mb-2"><?php echo htmlspecialchars($post['location_name']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-crosshairs me-2"></i>
                                Latitude: <?php echo $post['latitude']; ?>, Longitude: <?php echo $post['longitude']; ?>
                            </p>
                            <a href="https://maps.google.com/?q=<?php echo $post['latitude']; ?>,<?php echo $post['longitude']; ?>" 
                               target="_blank" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i>Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Post Stats -->
                <div class="post-stats d-flex justify-content-between align-items-center border-top border-bottom py-3 mb-4">
                    <div class="d-flex gap-4">
                        <span class="text-muted">
                            <i class="fas fa-heart text-danger me-1"></i>
                            <?php echo $post['like_count']; ?> likes
                        </span>
                        <span class="text-muted">
                            <i class="fas fa-comment me-1"></i>
                            <?php echo $post['comment_count']; ?> comments
                        </span>
                    </div>
                    <span class="text-muted">
                        <i class="fas fa-eye me-1"></i>
                        Public Post
                    </span>
                </div>

                <!-- Comments Section -->
                <div class="comments-section">
                    <h5 class="mb-3">
                        <i class="fas fa-comments me-2"></i>
                        Comments (<?php echo count($comments); ?>)
                    </h5>
                    
                    <?php if ($comments): ?>
                    <div class="comments-list mb-4">
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-item d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <img src="uploads/<?php echo $comment['profile_picture']; ?>" 
                                 class="rounded-circle me-3" 
                                 width="40" 
                                 height="40"
                                 alt="<?php echo htmlspecialchars($comment['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($comment['username']); ?>&background=6366f1&color=fff'">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo date('M j, g:i A', strtotime($comment['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($comment['content']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-comment-slash fa-2x mb-2"></i>
                        <p>No comments yet. Be the first to comment!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Login Prompt -->
                <div class="login-prompt bg-primary text-white p-4 rounded-3 text-center">
                    <h5 class="mb-3">
                        <i class="fas fa-lock-open me-2"></i>
                        Want to join the conversation?
                    </h5>
                    <p class="mb-3">Login to like, comment, and share this post with your friends!</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="login.php" class="btn btn-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Now
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join Free
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>