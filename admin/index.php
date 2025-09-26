<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = 'Dashboard';

// Get dashboard statistics
$db = Database::getInstance();

$stats = [
    'total_posts' => $db->fetch("SELECT COUNT(*) as count FROM posts")['count'],
    'published_posts' => $db->fetch("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")['count'],
    'draft_posts' => $db->fetch("SELECT COUNT(*) as count FROM posts WHERE status = 'draft'")['count'],
    'total_categories' => $db->fetch("SELECT COUNT(*) as count FROM categories WHERE is_active = true")['count'],
    'active_ads' => $db->fetch("SELECT COUNT(*) as count FROM ads WHERE is_active = true")['count']
];

$recent_posts = $db->fetchAll("
    SELECT p.*, c.name as category_name, u.username as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");

include 'includes/header.php';
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_posts']; ?></h3>
                <p>Total Posts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['published_posts']; ?></h3>
                <p>Published Posts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['draft_posts']; ?></h3>
                <p>Draft Posts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_categories']; ?></h3>
                <p>Categories</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-ad"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['active_ads']; ?></h3>
                <p>Active Ads</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Posts -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Recent Posts</h2>
            <a href="post-add.php" class="btn btn-primary">Add New Post</a>
        </div>
        
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_posts as $post): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                            <td>
                                <span class="status status-<?php echo $post['status']; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($post['created_at'], 'M j, Y'); ?></td>
                            <td>
                                <a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../article.php?slug=<?php echo $post['slug']; ?>" class="btn btn-sm btn-info" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>