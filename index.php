<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Handle post creation with media
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'] ?? 'text';
    $location_name = $_POST['location_name'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    
    $image_file = null;
    $video_file = null;
    $video_thumbnail = null;
    
    // Handle file uploads
    if ($post_type === 'image' && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['image_file'], 'image');
        if ($upload_result['success']) {
            $image_file = $upload_result['filename'];
        }
    }
    
    if ($post_type === 'video' && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['video_file'], 'video');
        if ($upload_result['success']) {
            $video_file = $upload_result['filename'];
            $video_thumbnail = $upload_result['thumbnail'] ?? null;
        }
    }
    
    // Get location name from coordinates if not provided
    if ($post_type === 'location' && $latitude && $longitude && empty($location_name)) {
        $location_name = getLocationName($latitude, $longitude);
    }
    
    if (!empty($content) || $image_file || $video_file || ($latitude && $longitude)) {
        $stmt = $pdo->prepare("
            INSERT INTO posts (user_id, content, image, post_type, location_name, latitude, longitude, video_thumbnail) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], 
            $content, 
            $image_file, 
            $post_type, 
            $location_name, 
            $latitude, 
            $longitude, 
            $video_thumbnail
        ]);
        
        $_SESSION['post_created'] = true;
        header("Location: index.php");
        exit();
    }
}

// Get posts with media using the new function
$posts = getPostsWithDeletionCheck($_SESSION['user_id']);
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <!-- Welcome Banner -->
        <div class="card mb-4 welcome-banner">
            <div class="card-body text-center py-5">
                <h2 class="gradient-text mb-3">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>! 👋</h2>
                <p class="text-muted">Share your moments with friends</p>
            </div>
        </div>

        <!-- Enhanced Create Post Card -->
        <div class="card mb-4 create-post-card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <img src="uploads/<?php echo $user['profile_picture']; ?>" class="profile-pic me-3" alt="Profile">
                    <div class="flex-grow-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h6>
                        <small class="text-muted">Share what's on your mind</small>
                    </div>
                </div>
                
                <form method="POST" id="postForm" enctype="multipart/form-data">
                    <input type="hidden" name="post_type" id="postType" value="text">
                    <input type="hidden" name="location_name" id="locationName">
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    
                    <!-- Post Type Selector -->
                    <div class="post-type-selector">
                        <div class="post-type-btn active" data-type="text">
                            <i class="fas fa-edit"></i>
                            <span>Text</span>
                        </div>
                         <div class="post-type-btn" data-type="image">
                            <i class="fas fa-image"></i>
                            <span>Image</span>
                        </div> 
                        <div class="post-type-btn" data-type="video">
                            <i class="fas fa-video"></i>
                            <span>Video</span>
                        </div>
                        <div class="post-type-btn" data-type="location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Location</span>
                        </div>
                    </div>
                    
                    <!-- Content Textarea -->
                    <div class="mb-3">
                        <textarea class="form-control" name="content" id="postContent" rows="3" placeholder="What's on your mind, <?php echo htmlspecialchars($user['username']); ?>?" required></textarea>
                    </div>
                    
                    <!-- Image Upload Section -->
                    <div id="imageSection" class="post-type-section" style="display: none;">
                        <div class="upload-area" id="imageUploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h5>Upload Image</h5>
                            <p class="text-muted">Drag & drop or click to select</p>
                            <p class="text-muted small">Supported formats: JPG, PNG, GIF, WebP (Max 50MB)</p>
                            <input type="file" id="imageFile" name="image_file" accept="image/*" style="display: none;">
                        </div>
                        <div id="imagePreview" class="upload-preview"></div>
                    </div>
                    
                    <!-- Video Upload Section -->
                    <div id="videoSection" class="post-type-section" style="display: none;">
                        <div class="upload-area" id="videoUploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <h5>Upload Video</h5>
                            <p class="text-muted">Drag & drop or click to select</p>
                            <p class="text-muted small">Supported formats: MP4, MOV, AVI, WebM (Max 50MB)</p>
                            <input type="file" id="videoFile" name="video_file" accept="video/*" style="display: none;">
                        </div>
                        <div id="videoPreview" class="upload-preview"></div>
                    </div>
                    
                    <!-- Location Section -->
                    <div id="locationSection" class="post-type-section" style="display: none;">
                        <div class="location-picker">
                            <div id="location-map"></div>
                        </div>
                        <div class="location-coordinates">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latDisplay" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="lngDisplay" readonly>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Location Name</label>
                                <input type="text" class="form-control" id="locationNameInput" placeholder="Custom location name (optional)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Post Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex gap-2" id="actionButtons">
                            <!-- Action buttons will be shown based on post type -->
                        </div>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            <i class="fas fa-paper-plane me-2"></i>Post
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Posts Feed -->
        <?php foreach ($posts as $index => $post): ?>
        <div class="card post-card mb-4" style="animation-delay: <?php echo $index * 0.1; ?>s">
            <div class="card-body">
                <!-- Post Header -->
                <div class="d-flex align-items-center mb-3">
                    <div class="position-relative">
                        <img src="uploads/<?php echo $post['profile_picture']; ?>" class="profile-pic me-3" alt="Profile">
                        <div class="online-status"></div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
                    </div>
                    <span class="post-type-badge">
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
                    
                    <!-- Dropdown Menu -->
                    <div class="dropdown">
                      <!--   <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        
                        </button>
                         -->
                             <a class="dropdown-item text-danger" 
                                   href="delete_confirm.php?type=post&id=<?php echo $post['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <i class="fas fa-trash me-2"></i>Delete Post
                                </a>
                        

                       <!--  <ul class="dropdown-menu">
                            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                            <li>
                                <a class="dropdown-item text-danger" 
                                   href="delete_confirm.php?type=post&id=<?php echo $post['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <i class="fas fa-trash me-2"></i>Delete Post
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-bookmark me-2"></i>Save Post</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-share me-2"></i>Share</a></li>
                        </ul> -->
                    </div>
                </div>
                
                <!-- Post Content -->
                <?php if (!empty($post['content'])): ?>
                <p class="card-text post-content mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php endif; ?>
                
                <!-- Media Content -->
                <?php if ($post['post_type'] === 'image' && $post['image']): ?>
                <div class="media-preview">
                    <img src="uploads/<?php echo $post['image']; ?>" 
                         alt="Post image" 
                         class="hover-lift"
                         onclick="openMediaModal('<?php echo $post['image']; ?>', 'image')">
                </div>
                <?php endif; ?>
                
                <?php if ($post['post_type'] === 'video' && $post['image']): ?>
                <div class="media-preview video-container">
                    <video controls poster="uploads/<?php echo $post['video_thumbnail']; ?>" class="hover-lift">
                        <source src="uploads/<?php echo $post['image']; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <?php endif; ?>
                
                <?php if ($post['post_type'] === 'location' && $post['latitude'] && $post['longitude']): ?>
                <div class="location-card">
                    <div class="location-content">
                        <div class="location-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="mb-2"><?php echo htmlspecialchars($post['location_name']); ?></h5>
                        <p class="mb-2">
                            <i class="fas fa-crosshairs me-2"></i>
                            Lat: <?php echo $post['latitude']; ?>, Lng: <?php echo $post['longitude']; ?>
                        </p>
                        <div class="location-actions">
                            <a href="https://maps.google.com/?q=<?php echo $post['latitude']; ?>,<?php echo $post['longitude']; ?>" 
                               target="_blank" 
                               class="btn btn-light btn-sm me-2">
                                <i class="fas fa-external-link-alt me-1"></i>Open in Maps
                            </a>
                            <button class="btn btn-light btn-sm" onclick="copyLocation('<?php echo $post['latitude']; ?>', '<?php echo $post['longitude']; ?>')">
                                <i class="fas fa-copy me-1"></i>Copy Coordinates
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Post Stats and Actions -->
                <div class="d-flex justify-content-between text-muted mb-3">
                    <small>
                        <i class="fas fa-heart text-danger me-1"></i>
                        <?php echo $post['like_count']; ?> likes
                    </small>
                    <small>
                        <i class="fas fa-comment me-1"></i>
                        <?php echo $post['comment_count']; ?> comments
                    </small>
                </div>
                
                <div class="d-flex justify-content-around border-top border-bottom py-2 mb-3">
                    <button class="btn btn-sm like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                        <i class="fas fa-heart me-1"></i>
                        <span class="like-count"><?php echo $post['like_count']; ?></span> Like
                    </button>
                    
                    <button class="btn btn-sm text-muted" data-bs-toggle="collapse" data-bs-target="#comments-<?php echo $post['id']; ?>">
                        <i class="fas fa-comment me-1"></i> Comment
                    </button>
                    
                    <button class="btn btn-sm text-muted">
                        <i class="fas fa-share me-1"></i> Share
                    </button>
                </div>

                <!-- Comments Section - FIXED CODE -->
                <div class="collapse" id="comments-<?php echo $post['id']; ?>">
                    <?php
                    // Get comments for this specific post
                    $comment_stmt = $pdo->prepare("
                        SELECT c.*, u.username, u.profile_picture 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.post_id = ? 
                        ORDER BY c.created_at ASC
                    ");
                    $comment_stmt->execute([$post['id']]); // Use $post['id'] from the loop
                    $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if ($comments): ?>
                    <div class="comments-section">
                        <?php foreach ($comments as $comment): ?>
                        <div class="d-flex align-items-start mb-3 comment-item">
                            <img src="uploads/<?php echo $comment['profile_picture']; ?>" class="small-profile-pic me-2" alt="Profile">
                            <div class="flex-grow-1 position-relative">
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                        <?php if ($comment['user_id'] == $_SESSION['user_id'] || $post['user_id'] == $_SESSION['user_id']): ?>
                                        <a href="delete_confirm.php?type=comment&id=<?php echo $comment['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                           class="btn btn-sm btn-outline-danger delete-comment-btn"
                                           title="Delete comment">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($comment['content']); ?></p>
                                </div>
                                <small class="text-muted"><?php echo date('M j, g:i A', strtotime($comment['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Add Comment Form -->
                    <form method="POST" action="add_comment.php" class="mt-3">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <div class="input-group">
                            <input type="text" name="content" class="form-control" placeholder="Write a comment..." required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($posts)): ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No posts yet</h4>
                <p class="text-muted">Start by making a post or adding some friends!</p>
                <a href="friends.php" class="btn btn-primary">Find Friends</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Media Modal -->
<div class="modal fade media-modal" id="mediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="" style="display: none;">
                <video id="modalVideo" controls style="display: none;"></video>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>