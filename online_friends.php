<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Get online friends
$online_friends = getOnlineFriends($_SESSION['user_id']);
$online_count = count($online_friends);

// Get all friends with online status
$all_friends = getFriendsWithOnlineStatus($_SESSION['user_id']);
$total_friends = count($all_friends);

// Get recently active friends (last 24 hours)
$stmt = $pdo->prepare("
    SELECT u.* 
    FROM users u 
    WHERE u.id IN (
        SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
    )
    AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY u.last_activity DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$recently_active = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Friends Online</h1>
                    <p class="text-muted">See who's currently active</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="friends.php" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>All Friends
                    </a>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h2 class="mb-0"><?php echo $online_count; ?></h2>
                            <p class="mb-0">Online Now</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h2 class="mb-0"><?php echo $total_friends; ?></h2>
                            <p class="mb-0">Total Friends</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h2 class="mb-0"><?php echo count($recently_active); ?></h2>
                            <p class="mb-0">Active Today</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Online Friends -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-circle me-2"></i>
                                Online Friends (<?php echo $online_count; ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($online_count > 0): ?>
                                <div class="row">
                                    <?php foreach ($online_friends as $friend): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="friend-card online-card">
                                            <div class="d-flex align-items-center">
                                                <div class="position-relative me-3">
                                                    <img src="uploads/<?php echo $friend['profile_picture']; ?>" 
                                                         class="friend-avatar" 
                                                         alt="<?php echo htmlspecialchars($friend['username']); ?>">
                                                    <div class="online-indicator pulse"></div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($friend['full_name'] ?? $friend['username']); ?></h6>
                                                    <p class="text-success mb-0 small">
                                                        <i class="fas fa-circle me-1"></i>
                                                        Online now
                                                    </p>
                                                    <small class="text-muted">
                                                        Active <?php echo formatLastSeen($friend['last_activity']); ?>
                                                    </small>
                                                </div>
                                                <div class="friend-actions">
                                                    <a href="messages.php?friend_id=<?php echo $friend['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Send message">
                                                        <i class="fas fa-comment"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No friends online</h5>
                                    <p class="text-muted">Your friends will appear here when they're online</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recently Active Friends -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Recently Active (Last 24 hours)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recently_active): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recently_active as $friend): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="position-relative me-3">
                                                <img src="uploads/<?php echo $friend['profile_picture']; ?>" 
                                                     class="friend-avatar-sm" 
                                                     alt="<?php echo htmlspecialchars($friend['username']); ?>">
                                                <?php if (isUserOnline($friend['id'])): ?>
                                                <div class="online-indicator-sm"></div>
                                                <?php else: ?>
                                                <div class="offline-indicator-sm"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($friend['full_name'] ?? $friend['username']); ?></h6>
                                                <p class="text-muted mb-0 small">
                                                    <?php if (isUserOnline($friend['id'])): ?>
                                                    <span class="text-success">
                                                        <i class="fas fa-circle me-1"></i>Online now
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Last seen <?php echo formatLastSeen($friend['last_seen']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="friend-actions">
                                                <a href="messages.php?friend_id=<?php echo $friend['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-comment"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No recent activity from friends</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="friends.php" class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>All Friends
                                </a>
                                <a href="messages.php" class="btn btn-outline-success">
                                    <i class="fas fa-envelope me-2"></i>Messages
                                </a>
                                <button class="btn btn-outline-info" onclick="startGroupChat()">
                                    <i class="fas fa-comments me-2"></i>Group Chat
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Online Status Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">About Online Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="online-indicator me-2"></div>
                                    <span class="small">Online now</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="offline-indicator me-2"></div>
                                    <span class="small">Offline</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="away-indicator me-2"></div>
                                    <span class="small">Away (inactive)</span>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">
                                Users are shown as online if they've been active in the last 5 minutes.
                            </p>
                        </div>
                    </div>

                    <!-- Your Status -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Your Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="online-indicator pulse me-3"></div>
                                <div>
                                    <strong class="text-success">Online</strong>
                                    <div class="small text-muted">
                                        Last active: Just now
                                    </div>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="statusToggle" checked>
                                <label class="form-check-label" for="statusToggle">
                                    Show me as online
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function startGroupChat() {
    const onlineFriends = <?php echo json_encode($online_friends); ?>;
    
    if (onlineFriends.length === 0) {
        alert('No online friends to start a group chat with.');
        return;
    }
    
    // In a real application, this would open a group chat modal
    const friendNames = onlineFriends.map(friend => friend.full_name || friend.username).join(', ');
    alert(`Starting group chat with: ${friendNames}`);
}

// Auto-refresh every 30 seconds
setTimeout(() => {
    location.reload();
}, 30000);

// Status toggle functionality
document.getElementById('statusToggle').addEventListener('change', function() {
    const status = this.checked ? 'online' : 'offline';
    
    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'status=' + status
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Status updated to:', status);
        }
    });
});
</script>

<?php include 'footer.php'; ?>