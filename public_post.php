<?php
require_once 'config.php';

$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    header("Location: public.php");
    exit();
}

// Get specific post
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture, u.full_name,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND p.is_deleted = FALSE
");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: public.php");
    exit();
}

// Get similar posts
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture, u.full_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id != ? AND p.is_deleted = FALSE
    ORDER BY p.created_at DESC
    LIMIT 6
");
$stmt->execute([$post_id]);
$similar_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['full_name'] ?? $post['username']); ?>'s Post - SocialConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        .post-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .profile-pic {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="public.php">
                <i class="fas fa-users me-2"></i>SocialConnect
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="public.php">
                    <i class="fas fa-arrow-left me-1"></i> Back to Feed
                </a>
                <a class="nav-link" href="login.php">Login</a>
                <a class="nav-link" href="register.php">Register</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <!-- Main Post -->
                <div class="post-card p-4 mb-4">
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

                    <?php if (!empty($post['content'])): ?>
                    <div class="post-content mb-4">
                        <p style="font-size: 1.2rem; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($post['post_type'] === 'image' && $post['image']): ?>
                    <div class="post-media mb-4">
                        <img src="uploads/<?php echo $post['image']; ?>" 
                             class="img-fluid rounded" 
                             alt="Post image"
                             style="max-height: 500px; object-fit: cover;">
                    </div>
                    <?php endif; ?>

                    <div class="post-stats border-top pt-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <h5 class="text-primary"><?php echo $post['like_count']; ?></h5>
                                <small class="text-muted">Likes</small>
                            </div>
                            <div class="col-4">
                                <h5 class="text-success"><?php echo $post['comment_count']; ?></h5>
                                <small class="text-muted">Comments</small>
                            </div>
                            <div class="col-4">
                                <h5 class="text-info"><?php echo rand(10, 100); ?></h5>
                                <small class="text-muted">Views</small>
                            </div>
                        </div>
                    </div>

                    <div class="login-prompt bg-light p-4 rounded mt-4 text-center">
                        <h5><i class="fas fa-lock me-2"></i>Join the Conversation</h5>
                        <p class="mb-3">Login to like, comment, and share this post with your friends!</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i>Login to Interact
                            </a>
                            <a href="register.php" class="btn btn-success">
                                <i class="fas fa-user-plus me-1"></i>Join Free
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Similar Posts -->
                <?php if (!empty($similar_posts)): ?>
                <div class="similar-posts">
                    <h4 class="mb-4">More Posts You Might Like</h4>
                    <div class="row">
                        <?php foreach ($similar_posts as $similar_post): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <div class="post-card p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <img src="uploads/<?php echo $similar_post['profile_picture']; ?>" 
                                         class="profile-pic me-2" 
                                         style="width: 40px; height: 40px;"
                                         alt="<?php echo htmlspecialchars($similar_post['username']); ?>">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($similar_post['full_name'] ?? $similar_post['username']); ?></h6>
                                        <small class="text-muted"><?php echo date('M j', strtotime($similar_post['created_at'])); ?></small>
                                    </div>
                                </div>
                                <p class="mb-2 text-truncate">
                                    <?php echo htmlspecialchars($similar_post['content']); ?>
                                </p>
                                <a href="public_post.php?id=<?php echo $similar_post['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                    View Post
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>