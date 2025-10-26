<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialConnect - Connect with Friends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f8fafc;
            --accent: #f59e0b;
            --text: #1e293b;
            --text-light: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text);
            font-size: 14px;
        }

        .app-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            min-height: 100vh;
        }

        /* Mobile First Navigation */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar-toggler {
            border: none;
            padding: 4px 8px;
            font-size: 1.1rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .nav-link {
            font-weight: 500;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            padding: 8px 12px !important;
        }

        .nav-link:hover {
            background: var(--primary);
            color: white !important;
        }

        /* Mobile Responsive Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        /* Profile Pictures - Responsive */
        .profile-pic {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .small-profile-pic {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        /* Buttons - Mobile Friendly */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Mobile Optimized Forms */
        .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        /* Mobile Grid System */
        .row {
            margin-left: -8px;
            margin-right: -8px;
        }

        .col, [class*="col-"] {
            padding-left: 8px;
            padding-right: 8px;
        }

        /* Mobile Typography */
        h1 { font-size: 1.8rem; }
        h2 { font-size: 1.6rem; }
        h3 { font-size: 1.4rem; }
        h4 { font-size: 1.2rem; }
        h5 { font-size: 1.1rem; }
        h6 { font-size: 1rem; }

        /* Mobile Post Cards */
        .post-card {
            margin-bottom: 1rem;
        }

        .post-content {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Mobile Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            flex: 1;
            min-width: 80px;
            text-align: center;
        }

        /* Mobile Menu Improvements */
        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .dropdown-item {
            padding: 8px 16px;
        }

        /* Mobile Modal Improvements */
        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            padding: 1rem;
        }

        .modal-body {
            padding: 1rem;
        }

        /* Mobile Table Improvements */
        .table-responsive {
            font-size: 0.85rem;
        }

        /* Mobile Utility Classes */
        .text-sm { font-size: 0.85rem; }
        .text-xs { font-size: 0.75rem; }

        .py-mobile-3 { padding-top: 1rem; padding-bottom: 1rem; }
        .px-mobile-2 { padding-left: 0.75rem; padding-right: 0.75rem; }

        /* Mobile Image Responsive */
        img, video {
            max-width: 100%;
            height: auto;
        }

        /* Mobile Footer */
        .footer-mobile {
            padding: 1rem 0;
            background: var(--secondary);
            border-top: 1px solid #e2e8f0;
        }

        /* Mobile Specific Styles */
        .mobile-hidden {
            display: none;
        }

        .mobile-visible {
            display: block;
        }

        /* Online Status Mobile */
        .online-indicator {
            width: 10px;
            height: 10px;
            border: 1.5px solid white;
        }

        /* Post Type Selector Mobile */
        .post-type-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 1rem;
        }

        .post-type-btn {
            padding: 10px 8px;
            font-size: 0.8rem;
        }

        /* Media Upload Mobile */
        .upload-area {
            padding: 2rem 1rem;
        }

        .upload-icon {
            font-size: 2rem;
        }

        /* Comments Mobile */
        .comments-section {
            max-height: 200px;
            overflow-y: auto;
        }

        /* Friend List Mobile */
        .friend-card {
            padding: 12px;
        }

        /* Message Bubbles Mobile */
        .message-bubble {
            max-width: 85%;
            padding: 10px 12px;
            margin-bottom: 8px;
        }

        /* Mobile First Media Queries */
        @media (min-width: 576px) {
            body { font-size: 15px; }
            .profile-pic { width: 50px; height: 50px; }
            .small-profile-pic { width: 40px; height: 40px; }
            .post-type-selector { grid-template-columns: repeat(4, 1fr); }
            .post-type-btn { font-size: 0.9rem; }
            .mobile-hidden { display: block; }
            .mobile-visible { display: none; }
        }

        @media (min-width: 768px) {
            body { font-size: 16px; }
            .container { max-width: 720px; }
            .post-content { font-size: 1rem; }
            .action-buttons .btn { flex: none; min-width: auto; }
            .comments-section { max-height: 300px; }
            .message-bubble { max-width: 70%; }
        }

        @media (min-width: 992px) {
            .container { max-width: 960px; }
            .navbar-brand { font-size: 1.5rem; }
            .profile-pic { width: 55px; height: 55px; }
        }

        @media (min-width: 1200px) {
            .container { max-width: 1140px; }
        }

        /* Mobile Touch Improvements */
        @media (hover: none) and (pointer: coarse) {
            .btn, .nav-link, .dropdown-item {
                min-height: 44px;
                display: flex;
                align-items: center;
            }
            
            .form-control {
                min-height: 44px;
            }
            
            .dropdown-toggle::after {
                margin-left: 8px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .app-container {
                background: rgba(185, 194, 212, 0.95);
                color: #e2e8f0;
            }
            
            .card {
                background: rgba(58, 74, 100, 0.9);
                color: #e2e8f0;
            }
            
            .text-muted {
                color: #94a3b8 !important;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Mobile Safe Areas */
        .safe-area-padding {
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* Loading States */
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }</style>
</head>
<body>
    <div class="app-container">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-users me-2"></i>Recycle Zone Hub
                </a>
                
                <?php if (isLoggedIn()): ?>
                <div class="navbar-nav ms-auto">
                   
                    <a class="nav-link position-relative" href="index.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                    <a class="nav-link position-relative" href="online_friends.php">
    <i class="fas fa-circle me-1"></i> Online Friends
    <?php if (getOnlineFriendsCount($_SESSION['user_id']) > 0): ?>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
        <?php echo getOnlineFriendsCount($_SESSION['user_id']); ?>
    </span>
    <?php endif; ?>
</a>
 <a class="nav-link" href="my_posts.php">
    <i class="fas fa-newspaper me-1"></i> My Posts
</a>
                    <a class="nav-link position-relative" href="profile.php">
                        <i class="fas fa-user me-1"></i> Profile
                    </a>
                    <a class="nav-link position-relative" href="friends.php">
                        <i class="fas fa-users me-1"></i> Friends
                       <!--  <span class="notification-badge"></span> -->
                    </a>
                    <a class="nav-link position-relative" href="messages.php">
                        <i class="fas fa-envelope me-1"></i> Messages
                       <!--  <span class="notification-badge">5</span> -->
                    </a>
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> 
                    </a>
                </div><?php else: ?>
                <div class="navbar-nav ms-auto">
                <a class="nav-link" href="public.php">
                    <i class="fas fa-home me-1"></i> Public Feed
                </a>
                <a class="nav-link" href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
                <a class="nav-link" href="register.php">
                    <i class="fas fa-user-plus me-1"></i> Register
                </a>
            </div>
                <?php endif; ?>
            </div>
        </nav>

        <div class="container mt-4">
 <!-- In header.php, replace the navbar section with this: -->
<!-- nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?php echo isLoggedIn() ? 'index.php' : 'public.php'; ?>">
            <i class="fas fa-users me-2"></i>SocialConnect
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isLoggedIn()): ?>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    <span class="mobile-hidden">Home</span>
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user me-1"></i>
                    <span class="mobile-hidden">Profile</span>
                </a>
                <a class="nav-link" href="friends.php">
                    <i class="fas fa-users me-1"></i>
                    <span class="mobile-hidden">Friends</span>
                </a>
                <a class="nav-link" href="messages.php">
                    <i class="fas fa-envelope me-1"></i>
                    <span class="mobile-hidden">Messages</span>
                </a>
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    <span class="mobile-hidden">Logout</span>
                </a>
            </div>
            
            
            <?php endif; ?>
        </div>
    </div>
</nav>          -->