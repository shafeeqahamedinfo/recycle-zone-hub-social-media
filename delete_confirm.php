<?php
require_once 'config.php';
requireLogin();

$type = $_GET['type'] ?? ''; // 'post' or 'comment'
$id = $_GET['id'] ?? 0;
$redirect = $_GET['redirect'] ?? 'index.php';

// Validate parameters
if (!in_array($type, ['post', 'comment']) || !$id) {
    $_SESSION['error'] = "Invalid request";
    header("Location: index.php");
    exit();
}

// Get item details based on type
$item = null;
$title = '';
$content = '';

if ($type === 'post') {
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        $title = "Delete Post";
        $content = $item['content'];
        // Check ownership
        if ($item['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "You can only delete your own posts";
            header("Location: $redirect");
            exit();
        }
    }
} else { // comment
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, p.user_id as post_owner_id 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN posts p ON c.post_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        $title = "Delete Comment";
        $content = $item['content'];
        // Check permissions
        if ($item['user_id'] != $_SESSION['user_id'] && $item['post_owner_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "You can only delete your own comments or comments on your posts";
            header("Location: $redirect");
            exit();
        }
    }
}

if (!$item) {
    $_SESSION['error'] = ucfirst($type) . " not found";
    header("Location: $redirect");
    exit();
}
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $title; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Debug Info (remove in production) -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        Type: <?php echo $type; ?><br>
                        ID: <?php echo $id; ?><br>
                        User ID: <?php echo $_SESSION['user_id']; ?><br>
                        Item User ID: <?php echo $item['user_id']; ?>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Are you sure?
                        </h5>
                        <p class="mb-0">This action cannot be undone. The <?php echo $type; ?> will be permanently deleted.</p>
                    </div>

                    <!-- Item Preview -->
                    <div class="preview-card mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong>
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($item['username']); ?>
                                </strong>
                                <small class="text-muted ms-2">
                                    <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($content)); ?></p>
                                
                                <?php if ($type === 'post' && $item['post_type'] === 'image' && $item['image']): ?>
                                <div class="mt-3">
                                    <img src="uploads/<?php echo $item['image']; ?>" class="img-fluid rounded" alt="Post image" style="max-height: 200px;">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Options -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label text-danger fw-bold" for="confirmDelete">
                                Yes, I want to delete this <?php echo $type; ?>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <form method="POST" action="delete_handler.php" id="deleteForm">
                        <input type="hidden" name="type" value="<?php echo $type; ?>">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg" id="deleteBtn" disabled>
                                <i class="fas fa-trash me-2"></i>
                                Delete <?php echo ucfirst($type); ?>
                            </button>
                            <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-4">
                            <div class="text-muted small">Posts</div>
                            <div class="h5 mb-0">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Comments</div>
                            <div class="h5 mb-0">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Likes</div>
                            <div class="h5 mb-0">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirmDelete');
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteForm = document.getElementById('deleteForm');
    
    // Enable/disable delete button based on checkbox
    confirmCheckbox.addEventListener('change', function() {
        deleteBtn.disabled = !this.checked;
    });
    
    // Add loading state to delete button
    deleteForm.addEventListener('submit', function() {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    });
});
</script>

<?php include 'footer.php'; ?>