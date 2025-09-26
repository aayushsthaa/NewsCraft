<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = 'Manage Advertisements';

// Handle delete action
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $db = Database::getInstance();
            
            // Get ad details before deletion to clean up image file
            $ad = $db->fetch("SELECT image_url FROM ads WHERE id = :id", [':id' => $id]);
            
            if ($ad && $db->delete('ads', 'id = :id', [':id' => $id])) {
                // Clean up associated image file
                if ($ad['image_url']) {
                    deleteAdImage($ad['image_url']);
                }
                $_SESSION['success'] = 'Advertisement deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete advertisement.';
            }
        }
    } else {
        $_SESSION['error'] = 'Invalid security token.';
    }
    header('Location: ads.php');
    exit;
}

// Get all ads
$db = Database::getInstance();
$ads = $db->fetchAll("
    SELECT * FROM ads 
    ORDER BY created_at DESC
");

// Get position options
$positions = [
    'header' => 'Header Banner',
    'sidebar' => 'Sidebar',
    'content-top' => 'Content Top',
    'content-middle' => 'Content Middle', 
    'content-bottom' => 'Content Bottom',
    'footer' => 'Footer Banner'
];

include 'includes/header.php';
?>

<div class="ads-management">
    <div class="page-header">
        <h1><i class="fas fa-ad"></i> Manage Advertisements</h1>
        <a href="ad-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Advertisement
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="ads-grid">
        <?php if (empty($ads)): ?>
            <div class="empty-state">
                <i class="fas fa-ad"></i>
                <h3>No advertisements found</h3>
                <p>Create your first advertisement to start displaying ads on your website.</p>
                <a href="ad-add.php" class="btn btn-primary">Add New Advertisement</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Clicks</th>
                            <th>Duration</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $ad): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ad['title']); ?></strong>
                                    <?php if ($ad['link_url']): ?>
                                        <br><small><a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" class="text-muted">
                                            <i class="fas fa-external-link-alt"></i> <?php echo htmlspecialchars(parse_url($ad['link_url'], PHP_URL_HOST)); ?>
                                        </a></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="position-badge position-<?php echo $ad['position']; ?>">
                                        <?php echo htmlspecialchars($positions[$ad['position']] ?? $ad['position']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $status = 'active';
                                    if (!$ad['is_active']) {
                                        $status = 'inactive';
                                    } elseif ($ad['end_date'] && strtotime($ad['end_date']) < time()) {
                                        $status = 'expired';
                                    } elseif ($ad['start_date'] && strtotime($ad['start_date']) > time()) {
                                        $status = 'scheduled';
                                    }
                                    ?>
                                    <span class="status status-<?php echo $status; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="click-count">
                                        <i class="fas fa-mouse-pointer"></i> <?php echo number_format($ad['click_count']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ad['start_date'] || $ad['end_date']): ?>
                                        <small>
                                            <?php if ($ad['start_date']): ?>
                                                From: <?php echo formatDate($ad['start_date'], 'M j, Y'); ?><br>
                                            <?php endif; ?>
                                            <?php if ($ad['end_date']): ?>
                                                Until: <?php echo formatDate($ad['end_date'], 'M j, Y'); ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">No limit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo formatDate($ad['created_at'], 'M j, Y'); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="ad-edit.php?id=<?php echo $ad['id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this advertisement?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        
                                        <?php if ($ad['link_url']): ?>
                                            <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" class="btn btn-sm btn-info" title="Visit Link">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ad Position Guide -->
    <div class="dashboard-section mt-30">
        <div class="section-header">
            <h2>Advertisement Positions Guide</h2>
        </div>
        
        <div class="position-guide">
            <div class="position-item">
                <div class="position-demo header-demo">Header Banner</div>
                <div class="position-info">
                    <h4>Header Banner</h4>
                    <p>Displayed at the top of pages, below the main navigation. High visibility, great for important announcements.</p>
                    <span class="position-badge position-header">header</span>
                </div>
            </div>
            
            <div class="position-item">
                <div class="position-demo sidebar-demo">Sidebar</div>
                <div class="position-info">
                    <h4>Sidebar</h4>
                    <p>Displayed in the right sidebar on desktop. Perfect for smaller ads and ongoing campaigns.</p>
                    <span class="position-badge position-sidebar">sidebar</span>
                </div>
            </div>
            
            <div class="position-item">
                <div class="position-demo content-demo">Content Area</div>
                <div class="position-info">
                    <h4>Content Areas</h4>
                    <p>Embedded within article content (top, middle, bottom). Good for contextual advertising.</p>
                    <span class="position-badge position-content-top">content-top</span>
                    <span class="position-badge position-content-middle">content-middle</span>
                    <span class="position-badge position-content-bottom">content-bottom</span>
                </div>
            </div>
            
            <div class="position-item">
                <div class="position-demo footer-demo">Footer Banner</div>
                <div class="position-info">
                    <h4>Footer Banner</h4>
                    <p>Displayed at the bottom of pages, before the footer. Good for secondary campaigns.</p>
                    <span class="position-badge position-footer">footer</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ads-grid {
    margin-top: 20px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.position-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    margin: 2px;
}

.position-header { background: #3498db; color: white; }
.position-sidebar { background: #2ecc71; color: white; }
.position-content-top { background: #f39c12; color: white; }
.position-content-middle { background: #e67e22; color: white; }
.position-content-bottom { background: #d35400; color: white; }
.position-footer { background: #9b59b6; color: white; }

.click-count {
    color: #666;
    font-size: 14px;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.position-guide {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.position-item {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.position-demo {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    padding: 15px;
    text-align: center;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: 500;
    color: #666;
}

.header-demo { border-color: #3498db; color: #3498db; }
.sidebar-demo { border-color: #2ecc71; color: #2ecc71; }
.content-demo { border-color: #f39c12; color: #f39c12; }
.footer-demo { border-color: #9b59b6; color: #9b59b6; }

.position-info h4 {
    margin-bottom: 8px;
    color: #2c3e50;
}

.position-info p {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
    line-height: 1.5;
}

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }
.status-expired { background: #fff3cd; color: #856404; }
.status-scheduled { background: #cce7ff; color: #004085; }

.text-muted {
    color: #666 !important;
    text-decoration: none;
}

.mt-30 { margin-top: 30px; }
</style>

<?php include 'includes/footer.php'; ?>