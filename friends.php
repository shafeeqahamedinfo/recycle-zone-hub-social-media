<?php
require_once 'config.php';
requireLogin();

// Get friends with online status
$friends = getFriendsWithOnlineStatus($_SESSION['user_id']);
$online_count = getOnlineFriendsCount($_SESSION['user_id']);

// Get friend requests
$stmt = $pdo->prepare("
    SELECT u.*, f.id as request_id 
    FROM friends f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users except current user for adding friends
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY username");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Friends</h1>
                    <p class="text-muted">
                        <?php echo count($friends); ?> friends • 
                        <span class="text-success"><?php echo $online_count; ?> online</span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="online_friends.php" class="btn btn-success">
                        <i class="fas fa-circle me-2"></i>Online Friends
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                        <i class="fas fa-user-plus me-2"></i>Add Friend
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Friend Requests -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock me-2"></i>
                                Friend Requests
                                <?php if ($requests): ?>
                                <span class="badge bg-danger ms-2"><?php echo count($requests); ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($requests): ?>
                                <?php foreach ($requests as $request): ?>
                                <div class="friend-request-card mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-3">
                                            <img src="uploads/<?php echo $request['profile_picture']; ?>" 
                                                 class="friend-avatar-sm" 
                                                 alt="<?php echo htmlspecialchars($request['username']); ?>">
                                            <?php if (isUserOnline($request['id'])): ?>
                                            <div class="online-indicator-sm"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($request['full_name'] ?? $request['username']); ?></h6>
                                            <p class="text-muted mb-0 small">
                                                @<?php echo htmlspecialchars($request['username']); ?>
                                            </p>
                                        </div>
                                        <div class="friend-actions">
                                            <a href="friends.php?action=accept&friend_id=<?php echo $request['id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="friends.php?action=reject&friend_id=<?php echo $request['id']; ?>" 
                                               class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">No pending friend requests</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Friends List -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Friends (<?php echo count($friends); ?>)</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary active" data-filter="all">
                                        All
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" data-filter="online">
                                        Online
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" data-filter="offline">
                                        Offline
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($friends): ?>
                                <div class="row" id="friendsList">
                                    <?php foreach ($friends as $friend): ?>
                                    <div class="col-md-6 mb-3 friend-item" 
                                         data-status="<?php echo $friend['is_currently_online'] ? 'online' : 'offline'; ?>">
                                        <div class="friend-card <?php echo $friend['is_currently_online'] ? 'online-card' : 'offline-card'; ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="position-relative me-3">
                                                    <img src="uploads/<?php echo $friend['profile_picture']; ?>" 
                                                         class="friend-avatar" 
                                                         alt="<?php echo htmlspecialchars($friend['username']); ?>">
                                                    <?php if ($friend['is_currently_online']): ?>
                                                    <div class="online-indicator pulse"></div>
                                                    <?php else: ?>
                                                    <div class="offline-indicator"></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($friend['full_name'] ?? $friend['username']); ?></h6>
                                                    <p class="mb-0 small">
                                                        <?php if ($friend['is_currently_online']): ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-circle me-1"></i>Online
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Last seen <?php echo formatLastSeen($friend['last_seen']); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        @<?php echo htmlspecialchars($friend['username']); ?>
                                                    </small>
                                                </div>
                                                <div class="friend-actions">
                                                    <a href="messages.php?friend_id=<?php echo $friend['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Send message">
                                                        <i class="fas fa-comment"></i>
                                                    </a>
                                                    <a href="profile.php?user_id=<?php echo $friend['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" 
                                                       title="View profile">
                                                        <i class="fas fa-eye"></i>
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
                                    <h5 class="text-muted">No friends yet</h5>
                                    <p class="text-muted">Start by adding some friends!</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                                        <i class="fas fa-user-plus me-2"></i>Add Your First Friend
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Friend Modal -->
<div class="modal fade" id="addFriendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Friend</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php foreach ($users as $user): ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="position-relative me-3">
                                <img src="uploads/<?php echo $user['profile_picture']; ?>" 
                                     class="friend-avatar-sm" 
                                     alt="<?php echo htmlspecialchars($user['username']); ?>">
                                <?php if (isUserOnline($user['id'])): ?>
                                <div class="online-indicator-sm"></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h6>
                                <p class="text-muted mb-0 small">
                                    @<?php echo htmlspecialchars($user['username']); ?>
                                    <?php if (isUserOnline($user['id'])): ?>
                                    • <span class="text-success">Online</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Add</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Friend list filtering
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    const friendItems = document.querySelectorAll('.friend-item');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter items
            friendItems.forEach(item => {
                if (filter === 'all' || item.dataset.status === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php include 'footer.php'; ?>