<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Get user's posts with full details
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_posts,
        SUM((SELECT COUNT(*) FROM likes WHERE post_id = posts.id)) as total_likes,
        SUM((SELECT COUNT(*) FROM comments WHERE post_id = posts.id)) as total_comments
    FROM posts 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">My Posts</h1>
                    <p class="text-muted">Manage your posts and content</p>
                </div>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Post
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $stats['total_posts'] ?? 0; ?></h3>
                            <p class="mb-0">Total Posts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $stats['total_likes'] ?? 0; ?></h3>
                            <p class="mb-0">Total Likes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $stats['total_comments'] ?? 0; ?></h3>
                            <p class="mb-0">Total Comments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Posts (<?php echo count($posts); ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No posts yet</h4>
                        <p class="text-muted mb-4">Start sharing your thoughts with the world</p>
                        <a href="index.php" class="btn btn-primary btn-lg">Create Your First Post</a>
                    </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                        <div class="post-item p-4 border-bottom">
                            <div class="row align-items-start">
                                <!-- Post Content -->
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-<?php 
                                            switch($post['post_type']) {
                                                case 'image': echo 'info'; break;
                                                case 'video': echo 'warning'; break;
                                                case 'location': echo 'success'; break;
                                                default: echo 'secondary';
                                            }
                                        ?> me-2">
                                            <i class="fas fa-<?php 
                                                switch($post['post_type']) {
                                                    case 'image': echo 'image'; break;
                                                    case 'video': echo 'video'; break;
                                                    case 'location': echo 'map-marker-alt'; break;
                                                    default: echo 'file-alt';
                                                }
                                            ?> me-1"></i>
                                            <?php echo ucfirst($post['post_type']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                        </small>
                                    </div>

                                    <?php if (!empty($post['content'])): ?>
                                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php endif; ?>

                                    <?php if ($post['post_type'] === 'image' && $post['image']): ?>
                                    <div class="mb-3">
                                        <img src="uploads/<?php echo $post['image']; ?>" class="img-fluid rounded" style="max-height: 200px;" alt="Post image">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Stats and Actions -->
                                <div class="col-md-4">
                                    <div class="d-flex flex-column align-items-end h-100">
                                        <!-- Stats -->
                                        <div class="mb-3 text-end">
                                            <div class="d-flex justify-content-end gap-3 mb-2">
                                                <span class="text-muted">
                                                    <i class="fas fa-heart text-danger me-1"></i>
                                                    <?php echo $post['like_count']; ?> likes
                                                </span>
                                                <span class="text-muted">
                                                    <i class="fas fa-comment me-1"></i>
                                                    <?php echo $post['comment_count']; ?> comments
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="d-flex gap-2 mt-auto">
                                            <a href="index.php?post=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <a href="delete_confirm.php?type=post&id=<?php echo $post['id']; ?>&redirect=<?php echo urlencode('my_posts.php'); ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               title="Delete post">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyPostLink(postId) {
    const link = window.location.origin + '/index.php?post=' + postId;
    navigator.clipboard.writeText(link).then(() => {
        alert('Post link copied to clipboard!');
    });
}
</script>

<?php include 'footer.php'; ?>