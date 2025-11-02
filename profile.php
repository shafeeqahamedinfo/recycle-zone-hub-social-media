<?php
require_once 'config.php';
requireLogin();

// Check if uploads directory exists, create if not
if (!file_exists('uploads')) {
    mkdir('uploads', 0755, true);
}

$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Basic profile info
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    if (!empty($full_name)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, website = ?, location = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $bio, $website, $location, $_SESSION['user_id']])) {
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    
    // Handle profile image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['profile_image'], 'image');
        if ($upload_result['success']) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$upload_result['filename'], $_SESSION['user_id']])) {
                    $success_message = $success_message ? $success_message . " Profile image updated!" : "Profile image updated successfully!";
                }
            } catch (PDOException $e) {
                $error_message = $error_message ? $error_message . " Failed to update profile image." : "Failed to update profile image.";
            }
        } else {
            $error_message = $upload_result['error'];
        }
    }
    
    // Handle cover image
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['cover_image'], 'image');
        if ($upload_result['success']) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET cover_picture = ? WHERE id = ?");
                if ($stmt->execute([$upload_result['filename'], $_SESSION['user_id']])) {
                    $success_message = $success_message ? $success_message . " Cover image updated!" : "Cover image updated successfully!";
                }
            } catch (PDOException $e) {
                $error_message = $error_message ? $error_message . " Failed to update cover image." : "Failed to update cover image.";
            }
        } else {
            $error_message = $error_message ? $error_message . " " . $upload_result['error'] : $upload_result['error'];
        }
    }
    
    // Refresh user data
    $user = getCurrentUser();
}

// Get user's posts
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? AND p.is_deleted = FALSE
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
    error_log("Error fetching posts: " . $e->getMessage());
}

// Get user stats
try {
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ? AND is_deleted = FALSE) as post_count,
            (SELECT COUNT(*) FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted') as friend_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ?) as like_count
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['post_count' => 0, 'friend_count' => 0, 'like_count' => 0];
    error_log("Error fetching stats: " . $e->getMessage());
}

// Get online friends count
$online_friends_count = function_exists('getOnlineFriendsCount') ? getOnlineFriendsCount($_SESSION['user_id']) : 0;

// Ensure default values for images
if (empty($user['profile_picture']) || !file_exists('uploads/' . $user['profile_picture'])) {
    $user['profile_picture'] = 'default.jpg';
}
if (empty($user['cover_picture']) || !file_exists('uploads/' . $user['cover_picture'])) {
    $user['cover_picture'] = '';
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid px-0">
    <!-- Profile Header -->
    <div class="card profile-header mb-4">
        <!-- Cover Photo -->
        <div class="cover-photo-container position-relative">
            <div class="cover-photo position-relative" 
                 style="height: 300px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        <?php if (!empty($user['cover_picture']) && file_exists('uploads/' . $user['cover_picture'])): ?>
                        background-image: url('uploads/<?php echo htmlspecialchars($user['cover_picture']); ?>'); 
                        <?php endif; ?>
                        background-size: cover; background-position: center;">
                <?php if (!empty($user['cover_picture']) && file_exists('uploads/' . $user['cover_picture'])): ?>
                <div class="cover-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-end justify-content-end p-3" 
                     style="background: rgba(0,0,0,0.3);">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-camera me-1"></i>Cover Photo
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Info -->
        <div class="card-body position-relative" style="margin-top: -80px;">
            <div class="row align-items-end">
                <div class="col-md-2 text-center text-md-start">
                    <div class="profile-image-container position-relative d-inline-block">
                        <?php if (!empty($user['profile_picture']) && file_exists('uploads/' . $user['profile_picture'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                 class="profile-image rounded-circle border border-4 border-white shadow" 
                                 alt="Profile Picture"
                                 style="width: 160px; height: 160px; object-fit: cover;">
                        <?php else: ?>
                            <div class="profile-image rounded-circle border border-4 border-white shadow d-flex align-items-center justify-content-center bg-light"
                                 style="width: 160px; height: 160px;">
                                <i class="fas fa-user fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6 mt-3 mt-md-0">
                    <h1 class="profile-name h2 mb-2"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h1>
                    <p class="profile-username text-muted h5 mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <?php if (!empty($user['bio'])): ?>
                    <p class="profile-bio lead mb-3"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="profile-meta d-flex flex-wrap gap-3">
                        <?php if (!empty($user['location'])): ?>
                        <span class="profile-meta-item d-flex align-items-center">
                            <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                            <?php echo htmlspecialchars($user['location']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                        <span class="profile-meta-item d-flex align-items-center">
                            <i class="fas fa-globe me-2 text-muted"></i>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" class="text-decoration-none">
                                Website
                            </a>
                        </span>
                        <?php endif; ?>
                        
                        <span class="profile-meta-item d-flex align-items-center">
                            <i class="fas fa-calendar-alt me-2 text-muted"></i>
                            Joined <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                    <div class="profile-stats d-flex justify-content-center justify-content-md-end gap-4">
                        <div class="stat-item text-center">
                            <div class="stat-number h3 text-primary mb-1"><?php echo $stats['post_count'] ?? 0; ?></div>
                            <div class="stat-label text-muted small">Posts</div>
                        </div>
                        <div class="stat-item text-center">
                            <div class="stat-number h3 text-success mb-1"><?php echo $stats['friend_count'] ?? 0; ?></div>
                            <div class="stat-label text-muted small">Friends</div>
                        </div>
                        <div class="stat-item text-center">
                            <div class="stat-number h3 text-info mb-1"><?php echo $stats['like_count'] ?? 0; ?></div>
                            <div class="stat-label text-muted small">Likes</div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <span class="badge bg-success">
                            <i class="fas fa-circle me-1"></i>
                            <?php echo $online_friends_count; ?> friends online
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Left Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Profile Update Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Update Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Profile Information -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                   placeholder="Enter your full name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bio</label>
                            <textarea class="form-control" name="bio" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <div class="form-text">Share something about yourself with your friends.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Location</label>
                            <input type="text" class="form-control" name="location" 
                                   value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                   placeholder="Where are you from?">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Website</label>
                            <input type="url" class="form-control" name="website" 
                                   value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                   placeholder="https://example.com">
                            <div class="form-text">Include http:// or https://</div>
                        </div>

                        <!-- Profile Image Upload -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                            <div id="profile_image_preview" class="mt-1"></div>
                            <div class="form-text">JPG, PNG, GIF, or WebP. Max 5MB. Square images work best.</div>
                        </div>
                        
                        <!-- Cover Image Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Cover Image</label>
                            <input type="file" class="form-control" name="cover_image" accept="image/*">
                            <div id="cover_image_preview" class="mt-1"></div>
                            <div class="form-text">JPG, PNG, GIF, or WebP. Max 5MB. Wide images (1500x500) work best.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check-circle me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="friends.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Manage Friends
                        </a>
                        <a href="messages.php" class="btn btn-outline-success">
                            <i class="fas fa-envelope me-2"></i>Messages
                        </a>
                        <a href="index.php" class="btn btn-outline-info">
                            <i class="fas fa-plus me-2"></i>Create Post
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content -->
        <div class="col-12 col-lg-8">
            <!-- Activity Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body py-3">
                            <i class="fas fa-newspaper fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo $stats['post_count'] ?? 0; ?></h4>
                            <small>Posts</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body py-3">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo $stats['friend_count'] ?? 0; ?></h4>
                            <small>Friends</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-info text-white text-center">
                        <div class="card-body py-3">
                            <i class="fas fa-heart fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo $stats['like_count'] ?? 0; ?></h4>
                            <small>Likes</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-warning text-white text-center">
                        <div class="card-body py-3">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <h4 class="mb-0"><?php echo array_sum(array_column($posts, 'comment_count')); ?></h4>
                            <small>Comments</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts Section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-stream me-2"></i>My Activity
                        <span class="badge bg-primary ms-2"><?php echo count($posts); ?></span>
                    </h5>
                    <a href="index.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>New Post
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No posts yet</h4>
                        <p class="text-muted mb-4">Share your thoughts and experiences with your friends</p>
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-pencil-alt me-2"></i>Create Your First Post
                        </a>
                    </div>
                    <?php else: ?>
                        <div class="posts-container">
                            <?php foreach ($posts as $post): ?>
                            <div class="post-item card mb-3">
                                <div class="card-body">
                                    <!-- Post Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($user['profile_picture']) && file_exists('uploads/' . $user['profile_picture'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                                     class="rounded-circle me-3" 
                                                     alt="Profile" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle me-3 d-flex align-items-center justify-content-center bg-light"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item text-danger" 
                                                       href="delete_confirm.php?type=post&id=<?php echo $post['id']; ?>">
                                                        <i class="fas fa-trash me-2"></i>Delete Post
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Post Content -->
                                    <?php if (!empty($post['content'])): ?>
                                    <p class="post-content mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php endif; ?>

                                    <!-- Post Media -->
                                    <?php if ($post['post_type'] === 'image' && !empty($post['image'])): ?>
                                    <div class="post-media mb-3">
                                        <img src="uploads/<?php echo $post['image']; ?>" 
                                             class="img-fluid rounded" 
                                             alt="Post image" 
                                             style="max-height: 400px; object-fit: cover;"
                                             onerror="this.style.display='none'">
                                    </div>
                                    <?php endif; ?>

                                    <!-- Post Stats -->
                                    <div class="post-stats d-flex justify-content-between text-muted mb-3">
                                        <div class="d-flex gap-3">
                                            <small>
                                                <i class="fas fa-heart text-danger me-1"></i>
                                                <?php echo $post['like_count']; ?> likes
                                            </small>
                                            <small>
                                                <i class="fas fa-comment me-1"></i>
                                                <?php echo $post['comment_count']; ?> comments
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-share me-1"></i>Share
                                        </small>
                                    </div>

                                    <!-- Post Actions -->
                                    <div class="post-actions d-flex justify-content-around border-top border-bottom py-2">
                                        <button class="btn btn-sm text-muted">
                                            <i class="fas fa-heart me-1"></i> Like
                                        </button>
                                        <button class="btn btn-sm text-muted">
                                            <i class="fas fa-comment me-1"></i> Comment
                                        </button>
                                        <button class="btn btn-sm text-muted">
                                            <i class="fas fa-share me-1"></i> Share
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-header {
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.cover-photo-container {
    border-radius: 15px 15px 0 0;
    overflow: hidden;
}

.profile-image-container {
    transition: transform 0.3s ease;
}

.profile-image-container:hover {
    transform: scale(1.05);
}

.profile-name {
    font-weight: 700;
    color: #2d3748;
}

.profile-stats .stat-number {
    font-weight: 700;
}

.post-item {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e2e8f0;
}

.post-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.post-content {
    line-height: 1.6;
    font-size: 1.05rem;
}

@media (max-width: 768px) {
    .profile-header .card-body {
        margin-top: -60px !important;
    }
    
    .profile-image-container img,
    .profile-image-container div {
        width: 120px !important;
        height: 120px !important;
    }
    
    .profile-name {
        font-size: 1.5rem !important;
    }
    
    .profile-stats {
        justify-content: center !important;
        margin-top: 1rem;
    }
}
</style>

<script>
// Simple file input display
document.addEventListener('DOMContentLoaded', function() {
    // Show filename when file is selected
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const preview = document.getElementById(this.name + '_preview');
                if (preview) {
                    preview.innerHTML = `<small class="text-success"><i class="fas fa-check me-1"></i>Selected: ${this.files[0].name}</small>`;
                }
                
                // Simple image preview
                if (this.files[0].type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (preview) {
                            preview.innerHTML += `
                                <div class="mt-2">
                                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            `;
                        }
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            }
        });
    });
    
    // Auto-size textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Trigger initial resize
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });
});

// Simple form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const fullName = document.querySelector('input[name="full_name"]');
    if (!fullName.value.trim()) {
        e.preventDefault();
        alert('Please enter your full name');
        fullName.focus();
        return false;
    }
});
</script>

<?php include 'footer.php'; ?>