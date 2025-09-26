<?php
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - News Portal</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-page">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-newspaper"></i> News Portal</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="posts.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['posts.php', 'post-add.php', 'post-edit.php']) ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt"></i> Posts
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['categories.php', 'category-add.php', 'category-edit.php']) ? 'active' : ''; ?>">
                            <i class="fas fa-folder"></i> Categories
                        </a>
                    </li>
                    <li>
                        <a href="ads.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['ads.php', 'ad-add.php', 'ad-edit.php']) ? 'active' : ''; ?>">
                            <i class="fas fa-ad"></i> Advertisements
                        </a>
                    </li>
                    <li>
                        <a href="layouts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'layouts.php' ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i> Layout Settings
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i> Site Settings
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <p><strong><?php echo htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                    <p><small><?php echo htmlspecialchars($_SESSION['admin_email'], ENT_QUOTES, 'UTF-8'); ?></small></p>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-content">