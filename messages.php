<?php
require_once 'config.php';
requireLogin();

// Get friends for messaging
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
        <div class="card">
            <div class="card-header">
                <h5>Friends</h5>
            </div>
            <div class="card-body">
                <?php foreach ($friends as $friend): ?>
                <a href="messages.php?friend_id=<?php echo $friend['id']; ?>" class="d-block text-decoration-none text-dark mb-3 p-2 rounded <?php echo ($selected_friend && $selected_friend['id'] == $friend['id']) ? 'bg-light' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <img src="uploads/<?php echo $friend['profile_picture']; ?>" class="small-profile-pic me-3">
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
        <?php if ($selected_friend): ?>
        <div class="card">
            <div class="card-header">
                <h5>Chat with <?php echo htmlspecialchars($selected_friend['username']); ?></h5>
            </div>
            <div class="card-body" style="height: 400px; overflow-y: auto;">
                <?php foreach ($messages as $message): ?>
                <div class="d-flex mb-3 <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'justify-content-end' : 'justify-content-start'; ?>">
                    <div class="bg-<?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'primary text-white' : 'light'; ?> p-3 rounded" style="max-width: 70%;">
                        <p class="mb-1"><?php echo htmlspecialchars($message['message']); ?></p>
                        <small class="<?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'text-white-50' : 'text-muted'; ?>">
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
</div>

<?php include 'footer.php'; ?>