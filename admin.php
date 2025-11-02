<?php
// admin.php
// Enhanced admin panel with animations and modern styling

session_start();

// ---------- CONFIG ----------
$dbHost = 'localhost';
$dbName = 'social_media';         // change to your database name
$dbUser = 'root';
$dbPass = '';          // change if necessary

// Simple admin credentials (change immediately)
define('ADMIN_USER', 'shafeeq');
define('ADMIN_PASS', 'shafeeq1234');

// ---------- DB CONNECTION ----------
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}

// ---------- AUTH ----------
if (isset($_POST['login_user']) && isset($_POST['login_pass'])) {
    if ($_POST['login_user'] === ADMIN_USER && $_POST['login_pass'] === ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        // ensure CSRF token exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        }

        // show a small animated "logging in" page then redirect to the admin panel
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Signing in...</title>';
        echo '<style>
            body{margin:0;height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea,#764ba2);font-family:"Segoe UI",Roboto,sans-serif;overflow:hidden}
            .card{background:#fff;padding:28px;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,.15);text-align:center;animation:pop .6s cubic-bezier(.2,.9,.3,1)}
            .spinner{width:52px;height:52px;border-radius:50%;border:6px solid #eef6ff;border-top-color:#3b82f6;margin:0 auto 14px;animation:spin 1s linear infinite}
            .msg{color:#1f2937;font-size:16px;font-weight:500}
            @keyframes spin{to{transform:rotate(360deg)}}@keyframes pop{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
        </style>';
        echo '</head><body><div class="card"><div class="spinner" aria-hidden="true"></div><div class="msg">Welcome back — redirecting to admin panel…</div></div>';
        // short delay so animation is visible, then navigate
        echo '<script>setTimeout(function(){window.location.href=' . json_encode($redirect) . ';},700);</script></body></html>';
        exit;
    } else {
        $login_error = "Invalid credentials";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (empty($_SESSION['is_admin'])) {
    // show enhanced login page
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Admin Login</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body { 
                font-family: 'Segoe UI', Roboto, Arial, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .login-container {
                position: relative;
                width: 100%;
                max-width: 400px;
                padding: 20px;
            }
            .login-box {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                padding: 40px 30px;
                border-radius: 16px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                animation: slideUp 0.6s ease-out;
                position: relative;
                overflow: hidden;
            }
            .login-box::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #667eea, #764ba2);
            }
            h2 {
                text-align: center;
                margin-bottom: 30px;
                color: #333;
                font-weight: 600;
            }
            .input-group {
                margin-bottom: 20px;
                position: relative;
            }
            label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #555;
            }
            input {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 16px;
                transition: all 0.3s;
                background: #f9f9f9;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                background: #fff;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                margin-top: 10px;
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 7px 14px rgba(102, 126, 234, 0.3);
            }
            .error {
                color: #e74c3c;
                background: rgba(231, 76, 60, 0.1);
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 20px;
                text-align: center;
                animation: shake 0.5s;
            }
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        </style>
    </head>
    <body>
    <div class="login-container">
        <div class="login-box">
            <h2>Admin Login</h2>
            <?php if (!empty($login_error)): ?>
                <div class="error"><?=htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="input-group">
                    <label>Username</label>
                    <input name="login_user" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input name="login_pass" type="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ---------- CSRF token ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

// ---------- HANDLE ACTIONS (deletes) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || !hash_equals($csrf, $_POST['csrf'])) {
        die('Invalid CSRF token');
    }

    if (!empty($_POST['action']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($id <= 0) {
            $flash = "Invalid id";
        } else {
            try {
                if ($_POST['action'] === 'delete_user') {
                    // Optionally delete related posts/comments in your schema if needed
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $flash = "User #$id deleted.";
                } elseif ($_POST['action'] === 'delete_post') {
                    // delete comments related to post (if desired)
                    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                    $stmt->execute([$id]);
                    $flash = "Post #$id deleted.";
                } elseif ($_POST['action'] === 'delete_comment') {
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                    $stmt->execute([$id]);
                    $flash = "Comment #$id deleted.";
                }
            } catch (Exception $e) {
                $flash = "Error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
    // redirect to avoid repost
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . '?_msg=' . urlencode($flash ?? ''));
    exit;
}

// optional flash from redirect
if (!empty($_GET['_msg'])) {
    $flash = $_GET['_msg'];
}

// ---------- FETCH DATA ----------
$users = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY id DESC LIMIT 200")->fetchAll();
$posts = $pdo->query("SELECT id, user_id, content, created_at FROM posts ORDER BY id DESC LIMIT 200")->fetchAll();
$comments = $pdo->query("SELECT id, post_id, user_id, content, created_at FROM comments ORDER BY id DESC LIMIT 200")->fetchAll();

// ---------- RENDER PAGE ----------
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: 'Segoe UI', Roboto, Arial, sans-serif; 
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease-out;
        }
        .header h1 {
            font-weight: 600;
            font-size: 28px;
        }
        .btn-logout {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s;
            animation: fadeIn 0.6s ease-out;
            border-top: 4px solid #667eea;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .stat-card h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card .count {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        .section-header {
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }
        .section-content {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #eee;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        tr:hover td {
            background: #f9f9f9;
        }
        .btn-del {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .btn-del:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(231, 76, 60, 0.3);
        }
        .flash {
            padding: 15px 20px;
            background: #d4edda;
            color: #155724;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.5s ease-out;
            border-left: 4px solid #28a745;
        }
        pre.small {
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 13px;
            max-width: 300px;
            max-height: 100px;
            overflow: auto;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
    <script>
        function confirmDelete(which, id) {
            return confirm('Delete ' + which + ' #' + id + '? This action cannot be undone.');
        }
        
        // Add fade-in animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.classList.add('fade-in');
            });
        });
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div>
            <a class="btn-logout" href="?logout=1">Logout</a>
        </div>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="flash"><?=htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="dashboard">
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="count"><?=count($users)?></div>
        </div>
        <div class="stat-card">
            <h3>Total Posts</h3>
            <div class="count"><?=count($posts)?></div>
        </div>
        <div class="stat-card">
            <h3>Total Comments</h3>
            <div class="count"><?=count($comments)?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Users (<?=count($users)?>)</h2>
        </div>
        <div class="section-content">
            <?php if (count($users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?=htmlspecialchars($u['id'])?></td>
                        <td><?=htmlspecialchars($u['username'])?></td>
                        <td><?=htmlspecialchars($u['email'])?></td>
                        <td><?=htmlspecialchars($u['created_at'] ?? '')?></td>
                        <td>
                            <?php if ($u['id'] != 1): // simple protection for root account ?>
                            <form class="inline" method="post" onsubmit="return confirmDelete('user', <?= (int)$u['id'] ?>)">
                                <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="id" value="<?=htmlspecialchars($u['id'])?>">
                                <button class="btn-del" type="submit">Delete</button>
                            </form>
                            <?php else: ?>
                            <span style="color:#999; font-style:italic">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div>📭</div>
                <p>No users found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Posts (<?=count($posts)?>)</h2>
        </div>
        <div class="section-content">
            <?php if (count($posts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Content</th>
                        <th>User ID</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><?=htmlspecialchars($p['id'])?></td>
                        <td><pre class="small"><?=htmlspecialchars(mb_strimwidth($p['content'] ?? '', 0, 200, '...'))?></pre></td>
                        <td><?=htmlspecialchars($p['user_id'])?></td>
                        <td><?=htmlspecialchars($p['created_at'] ?? '')?></td>
                        <td>
                            <form class="inline" method="post" onsubmit="return confirmDelete('post', <?= (int)$p['id'] ?>)">
                                <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="id" value="<?=htmlspecialchars($p['id'])?>">
                                <button class="btn-del" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div>📝</div>
                <p>No posts found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Comments (<?=count($comments)?>)</h2>
        </div>
        <div class="section-content">
            <?php if (count($comments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Post ID</th>
                        <th>User ID</th>
                        <th>Content</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($comments as $c): ?>
                    <tr>
                        <td><?=htmlspecialchars($c['id'])?></td>
                        <td><?=htmlspecialchars($c['post_id'])?></td>
                        <td><?=htmlspecialchars($c['user_id'])?></td>
                        <td><pre class="small"><?=htmlspecialchars(mb_strimwidth($c['content'], 0, 200, '...'))?></pre></td>
                        <td><?=htmlspecialchars($c['created_at'] ?? '')?></td>
                        <td>
                            <form class="inline" method="post" onsubmit="return confirmDelete('comment', <?= (int)$c['id'] ?>)">
                                <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="id" value="<?=htmlspecialchars($c['id'])?>">
                                <button class="btn-del" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div>💬</div>
                <p>No comments found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>