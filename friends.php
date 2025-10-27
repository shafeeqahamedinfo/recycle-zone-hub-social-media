<?php
require_once 'config.php';
requireLogin();

// Handle friend request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['friend_id'])) {
    $friend_id = $_POST['friend_id'];
    
    // Check if request already exists
    $stmt = $pdo->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $friend_id]);
    }
    header("Location: friends.php");
    exit();
}

// Handle accept/reject friend request
if (isset($_GET['action']) && isset($_GET['friend_id'])) {
    $friend_id = $_GET['friend_id'];
    
    if ($_GET['action'] == 'accept') {
        $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ?");
        $stmt->execute([$friend_id, $_SESSION['user_id']]);
    } elseif ($_GET['action'] == 'reject') {
        $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");
        $stmt->execute([$friend_id, $_SESSION['user_id']]);
    }
    header("Location: friends.php");
    exit();
}

// Get all users except current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY username");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friend requests
$stmt = $pdo->prepare("
    SELECT u.*, f.id as request_id 
    FROM friends f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friends
$stmt = $pdo->prepare("
    SELECT u.* FROM users u 
    WHERE u.id IN (
        SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
    )
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Friend Requests</h5>
            </div>
            <div class="card-body">
                <?php if ($requests): ?>
                    <?php foreach ($requests as $request): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                        </div>
                        <div>
                            <a href="friends.php?action=accept&friend_id=<?php echo $request['id']; ?>" class="btn btn-success btn-sm">Accept</a>
                            <a href="friends.php?action=reject&friend_id=<?php echo $request['id']; ?>" class="btn btn-danger btn-sm">Reject</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No friend requests</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Find Friends</h5>
            </div>
            <div class="card-body">
                <?php foreach ($users as $user): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($user['full_name']); ?></small>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Add Friend</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>My Friends (<?php echo count($friends); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($friends): ?>
                    <?php foreach ($friends as $friend): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="uploads/<?php echo $friend['profile_picture']; ?>" class="small-profile-pic me-3">
                        <div>
                            <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($friend['full_name']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No friends yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
