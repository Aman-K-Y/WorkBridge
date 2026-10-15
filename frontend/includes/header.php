<?php
// This file expects config.php and api.php to already be included by the parent page.
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — WorkBridge' : 'WorkBridge'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>">
    <script>
        // Set the theme before the page paints, to avoid a flash of the wrong theme.
        (function () {
            var saved = localStorage.getItem('workbridge-theme');
            var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">Work<span>Bridge</span></a>
            <div class="nav-links">
                <a href="browse-jobs.php">Browse Jobs</a>
                <?php if ($currentUser): ?>
                    <?php if ($currentUser['role'] === 'client'): ?>
                        <a href="post-job.php">Post a Job</a>
                    <?php endif; ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php?id=<?php echo $currentUser['user_id']; ?>">My Profile</a>
                    <span class="nav-user">Hi, <?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <a href="logout.php" class="nav-btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php" class="nav-btn">Sign Up</a>
                <?php endif; ?>
                <button type="button" id="theme-toggle" class="theme-switch" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <span class="theme-switch-track">
                        <span class="theme-switch-thumb">
                            <svg class="icon-sun" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <circle cx="12" cy="12" r="4"></circle>
                                <line x1="12" y1="2" x2="12" y2="4"></line>
                                <line x1="12" y1="20" x2="12" y2="22"></line>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                <line x1="2" y1="12" x2="4" y2="12"></line>
                                <line x1="20" y1="12" x2="22" y2="12"></line>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                            </svg>
                            <svg class="icon-moon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </nav>
    <main class="main-content">
