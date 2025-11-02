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
    header("Location: messages.php");
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
    } elseif ($_GET['action'] == 'remove') {
        // Remove friend from both sides
        $stmt = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
    }
    header("Location: messages.php");
    exit();
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receiver_id']) && isset($_POST['message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
        header("Location: messages.php?friend_id=" . $receiver_id);
        exit();
    }
}

// Get all users except current user and friends
$stmt = $pdo->prepare("
    SELECT u.* 
    FROM users u 
    WHERE u.id != ? 
    AND u.id NOT IN (
        SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
    )
    ORDER BY username
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
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

// Get sent friend requests (pending)
$stmt = $pdo->prepare("
    SELECT u.*, f.id as request_id 
    FROM friends f 
    JOIN users u ON f.friend_id = u.id 
    WHERE f.user_id = ? AND f.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$sent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friends for both friend list and messaging
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

// Get messages with selected friend
$selected_friend = null;
$messages = [];

if (isset($_GET['friend_id'])) {
    $friend_id = $_GET['friend_id'];

    // Get friend info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$friend_id]);
    $selected_friend = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_username 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE");
    $stmt->execute([$friend_id, $_SESSION['user_id']]);
}
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <!-- Friend Requests -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Friend Requests</h5>
            </div>
            <div class="card-body">
                <?php if ($requests): ?>
                    <?php foreach ($requests as $request): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?php echo $request['profile_picture']; ?>" 
                                 class="profile-pic me-2" 
                                 alt="<?php echo htmlspecialchars($request['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($request['username']); ?>&background=6366f1&color=fff'"
                                 style="width: 40px; height: 40px; border-radius: 50%;">
                            <div>
                                <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($request['full_name']); ?></small>
                            </div>
                        </div>
                        <div>
                            <a href="messages.php?action=accept&friend_id=<?php echo $request['id']; ?>" class="btn btn-success btn-sm">Accept</a>
                            <a href="messages.php?action=reject&friend_id=<?php echo $request['id']; ?>" class="btn btn-danger btn-sm">Reject</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No friend requests</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sent Requests -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Sent Requests</h5>
            </div>
            <div class="card-body">
                <?php if ($sent_requests): ?>
                    <?php foreach ($sent_requests as $request): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?php echo $request['profile_picture']; ?>" 
                                 class="profile-pic me-2" 
                                 alt="<?php echo htmlspecialchars($request['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($request['username']); ?>&background=6366f1&color=fff'"
                                 style="width: 40px; height: 40px; border-radius: 50%;">
                            <div>
                                <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                                <br>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <a href="messages.php?action=reject&friend_id=<?php echo $request['id']; ?>" class="btn btn-warning btn-sm">Cancel</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No sent requests</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Friends List for Messaging -->
        <div class="card">
            <div class="card-header">
                <h5>Friends for Messaging</h5>
            </div>
            <div class="card-body">
                <?php foreach ($friends as $friend): ?>
                    <a href="messages.php?friend_id=<?php echo $friend['id']; ?>" class="d-block text-decoration-none text-dark mb-3 p-2 rounded <?php echo ($selected_friend && $selected_friend['id'] == $friend['id']) ? 'bg-light' : ''; ?>">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?php echo $friend['profile_picture']; ?>" 
                                 class="profile-pic me-3" 
                                 alt="<?php echo htmlspecialchars($friend['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($friend['username']); ?>&background=6366f1&color=fff'"
                                 style="width: 40px; height: 40px; border-radius: 50%;">
                            <div>
                                <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Find Friends -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Find Friends</h5>
            </div>
            <div class="card-body">
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?php echo $user['profile_picture']; ?>" 
                                 class="profile-pic me-3" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=6366f1&color=fff'"
                                 style="width: 50px; height: 50px; border-radius: 50%;">
                            <div>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($user['full_name']); ?></small>
                            </div>
                        </div>
                        <form method="POST" class="mb-0">
                            <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Add Friend</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No users found to add as friends</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- My Friends -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>My Friends (<?php echo count($friends); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($friends): ?>
                    <?php foreach ($friends as $friend): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?php echo $friend['profile_picture']; ?>" 
                                 class="profile-pic me-3" 
                                 alt="<?php echo htmlspecialchars($friend['username']); ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($friend['username']); ?>&background=6366f1&color=fff'"
                                 style="width: 50px; height: 50px; border-radius: 50%;">
                            <div>
                                <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($friend['full_name']); ?></small>
                            </div>
                        </div>
                        <a href="messages.php?action=remove&friend_id=<?php echo $friend['id']; ?>" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('Are you sure you want to remove this friend?')">
                            Remove Friend
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No friends yet. Start adding friends!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messaging Section -->
        <?php if ($selected_friend): ?>
           <div class="col-md-8">
        <?php if ($selected_friend): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Chat with <?php echo htmlspecialchars($selected_friend['username']); ?></h5>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;">
                    <?php foreach ($messages as $message): ?>
                        <div class="d-flex mb-3 <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'justify-content-end' : 'justify-content-start'; ?>">
                            <div class="bg-
                            <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'primary text-white' : 'light'; ?>
                             p-3 rounded" style="max-width: 70%;">
                                <p class="mb-1"><?php echo htmlspecialchars($message['message']); ?></p>
                                <small class="<?php echo $message['sender_id'] == $_SESSION['user_id'] ? '' : 'text-muted'; ?>">
                                    <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <form method="POST">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_friend['id']; ?>">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <h5>Select a friend to start chatting</h5>
                </div>
            </div>
        <?php endif; ?>
    </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <h5>Select a friend from the list to start chatting</h5>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>