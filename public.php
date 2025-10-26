<?php
require_once 'config.php';

// Get public posts (most popular/latest)
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture, u.full_name,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.is_deleted = FALSE
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats for public display
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM users) as user_count,
        (SELECT COUNT(*) FROM posts WHERE is_deleted = FALSE) as post_count,
        (SELECT COUNT(*) FROM likes) as like_count,
        (SELECT COUNT(*) FROM comments WHERE is_deleted = FALSE) as comment_count
");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialConnect - Public Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary) !important;
        }

        .hero-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            margin-top: 2rem;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .post-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .profile-pic {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .hero-section {
                margin-top: 1rem;
                border-radius: 15px;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .profile-pic {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container py-4">
        <!-- Hero Section -->
        <div class="hero-section p-4 p-md-5 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Connect with Friends & Share Your World
                    </h1>
                    <p class="lead mb-4">
                        Join our community of <?php echo number_format($stats['user_count']); ?>+ users sharing 
                        <?php echo number_format($stats['post_count']); ?>+ posts and 
                        <?php echo number_format($stats['like_count']); ?>+ likes!
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                        <a href="#public-feed" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-eye me-2"></i>View Public Feed
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/1006/1006771.png" 
                         alt="Social Connection" 
                         class="img-fluid"
                         style="max-height: 300px;">
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="row mb-5">
            <div class="col-md-3 col-6 mb-3">
                <div class="stats-card text-center p-4">
                    <i class="fas fa-users feature-icon"></i>
                    <h3 class="text-primary"><?php echo number_format($stats['user_count']); ?></h3>
                    <p class="text-muted mb-0">Active Users</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stats-card text-center p-4">
                    <i class="fas fa-newspaper feature-icon"></i>
                    <h3 class="text-success"><?php echo number_format($stats['post_count']); ?></h3>
                    <p class="text-muted mb-0">Total Posts</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stats-card text-center p-4">
                    <i class="fas fa-heart feature-icon"></i>
                    <h3 class="text-danger"><?php echo number_format($stats['like_count']); ?></h3>
                    <p class="text-muted mb-0">Likes Given</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stats-card text-center p-4">
                    <i class="fas fa-comments feature-icon"></i>
                    <h3 class="text-warning"><?php echo number_format($stats['comment_count']); ?></h3>
                    <p class="text-muted mb-0">Comments Made</p>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="fw-bold text-white">Why Join SocialConnect?</h2>
                <p class="lead text-white">Discover amazing features that make social networking fun and engaging</p>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="stats-card p-4 text-center h-100">
                    <i class="fas fa-share-alt fa-2x text-primary mb-3"></i>
                    <h4>Share Moments</h4>
                    <p class="text-muted">Post updates, photos, videos, and locations with your friends and followers</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="stats-card p-4 text-center h-100">
                    <i class="fas fa-comments fa-2x text-success mb-3"></i>
                    <h4>Real-time Chat</h4>
                    <p class="text-muted">Message your friends instantly and see who's online in real-time</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="stats-card p-4 text-center h-100">
                    <i class="fas fa-mobile-alt fa-2x text-info mb-3"></i>
                    <h4>Mobile Friendly</h4>
                    <p class="text-muted">Access your social network from any device, anywhere, anytime</p>
                </div>
            </div>
        </div>

        <!-- Public Feed Section -->
        <div id="public-feed" class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-white">
                        <i class="fas fa-globe-americas me-2"></i>Public Feed
                    </h2>
                    <div class="text-white">
                        <small>Showing latest <?php echo count($posts); ?> posts</small>
                    </div>
                </div>

                <?php if (empty($posts)): ?>
                <div class="stats-card text-center p-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No posts yet</h4>
                    <p class="text-muted">Be the first to join and start sharing!</p>
                    <a href="register.php" class="btn btn-primary">Join Now</a>
                </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <div class="post-card p-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="uploads/<?php echo $post['profile_picture']; ?>" 
                                 class="profile-pic me-3" 
                                 alt="<?php echo htmlspecialchars($post['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($post['username']); ?>&background=6366f1&color=fff'">
                            <div class="flex-grow-1">
                                <h5 class="mb-1"><?php echo htmlspecialchars($post['full_name'] ?? $post['username']); ?></h5>
                                <small class="text-muted">
                                    @<?php echo htmlspecialchars($post['username']); ?> • 
                                    <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="login.php">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login to Interact
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Post Content -->
                        <?php if (!empty($post['content'])): ?>
                        <p class="post-content mb-3" style="font-size: 1.1rem; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Post Media -->
                        <?php if ($post['post_type'] === 'image' && $post['image']): ?>
                        <div class="post-media mb-3">
                            <img src="uploads/<?php echo $post['image']; ?>" 
                                 class="img-fluid rounded" 
                                 alt="Post image"
                                 style="max-height: 400px; object-fit: cover;">
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($post['post_type'] === 'video' && $post['image']): ?>
                        <div class="post-media mb-3">
                            <div class="ratio ratio-16x9">
                                <video controls class="rounded">
                                    <source src="uploads/<?php echo $post['image']; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($post['post_type'] === 'location' && $post['latitude']): ?>
                        <div class="location-card bg-light p-3 rounded mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt text-danger fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($post['location_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-crosshairs me-1"></i>
                                        <?php echo $post['latitude']; ?>, <?php echo $post['longitude']; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Post Stats -->
                        <div class="post-stats d-flex justify-content-between align-items-center text-muted">
                            <div class="d-flex gap-4">
                                <span>
                                    <i class="fas fa-heart text-danger me-1"></i>
                                    <?php echo $post['like_count']; ?> likes
                                </span>
                                <span>
                                    <i class="fas fa-comment me-1"></i>
                                    <?php echo $post['comment_count']; ?> comments
                                </span>
                            </div>
                            <small>
                                <i class="fas fa-share me-1"></i>
                                Share
                            </small>
                        </div>
                        
                        <!-- Login Prompt -->
                        <div class="login-prompt bg-light p-3 rounded mt-3 text-center">
                            <p class="mb-2">
                                <i class="fas fa-lock me-2 text-muted"></i>
                                Want to like, comment, or share this post?
                            </p>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <a href="login.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </a>
                                <a href="register.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-user-plus me-1"></i>Join Free
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Load More Section -->
                <?php if (!empty($posts)): ?>
                <div class="text-center mt-4">
                    <div class="stats-card p-4">
                        <h4 class="text-muted mb-3">Want to see more?</h4>
                        <p class="text-muted mb-3">
                            Join our community to access unlimited posts, connect with friends, and share your own content!
                        </p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Join Now - It's Free!
                            </a>
                            <a href="login.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Existing User? Login
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-users me-2"></i>SocialConnect
                    </h5>
                    <p class="text-muted">
                        Connect with friends, share your moments, and discover amazing content in our social community.
                    </p>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h6 class="mb-3">Platform</h6>
                    <ul class="list-unstyled">
                        <li><a href="public.php" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="#public-feed" class="text-muted text-decoration-none">Public Feed</a></li>
                        <li><a href="login.php" class="text-muted text-decoration-none">Login</a></li>
                        <li><a href="register.php" class="text-muted text-decoration-none">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h6 class="mb-3">Features</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Posts</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Messages</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Friends</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Photos</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="mb-3">Get Started</h6>
                    <p class="text-muted mb-3">
                        Ready to join our growing community? Sign up now and start connecting!
                    </p>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-rocket me-2"></i>Start Your Journey
                    </a>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; 2024 SocialConnect. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex gap-3 justify-content-md-end">
                        <a href="#" class="text-muted"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer> -->
    <!-- In the post loop, add this after the post content: -->
<div class="text-end mt-3">
    <a href="public_post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-external-link-alt me-1"></i>View Post
    </a>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation to buttons
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.href) {
                        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                    }
                });
            });
        });
    </script>
</body>
</html>