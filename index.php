<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get homepage settings
$posts_per_page = getSiteSetting('posts_per_page', 10);
$featured_posts = getPosts(5, 0, null, 'published');
$categories = getCategories();

// Get posts by category for different sections
$recent_posts = getPosts(6, 0, null, 'published');

$page_title = getSiteSetting('site_name', 'News Portal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <h1><a href="/"><?php echo htmlspecialchars(getSiteSetting('site_name', 'News Portal')); ?></a></h1>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <form action="search.php" method="GET">
                            <input type="text" name="q" placeholder="Search news..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="main-nav">
                <ul>
                    <li><a href="/" class="active">Home</a></li>
                    <?php foreach ($categories as $category): ?>
                        <li><a href="/category.php?slug=<?php echo urlencode($category['slug']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Featured Section -->
                <?php if (!empty($featured_posts)): ?>
                <section class="featured-section">
                    <div class="featured-main">
                        <?php 
                        $main_post = $featured_posts[0]; 
                        ?>
                        <article class="featured-article">
                            <?php if ($main_post['featured_image']): ?>
                                <div class="article-image">
                                    <img src="<?php echo UPLOAD_URL . $main_post['featured_image']; ?>" alt="<?php echo htmlspecialchars($main_post['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="article-content">
                                <div class="article-meta">
                                    <?php if ($main_post['category_name']): ?>
                                        <span class="category"><?php echo htmlspecialchars($main_post['category_name']); ?></span>
                                    <?php endif; ?>
                                    <span class="date"><?php echo formatDate($main_post['created_at'], 'M j, Y'); ?></span>
                                </div>
                                <h2><a href="/article.php?slug=<?php echo urlencode($main_post['slug']); ?>">
                                    <?php echo htmlspecialchars($main_post['title']); ?>
                                </a></h2>
                                <p><?php echo htmlspecialchars($main_post['excerpt'] ?? truncateText(strip_tags($main_post['content']), 150)); ?></p>
                            </div>
                        </article>
                    </div>
                    
                    <div class="featured-sidebar">
                        <?php for ($i = 1; $i < min(4, count($featured_posts)); $i++): 
                            $post = $featured_posts[$i];
                        ?>
                            <article class="sidebar-article">
                                <?php if ($post['featured_image']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo UPLOAD_URL . $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="article-content">
                                    <div class="article-meta">
                                        <span class="date"><?php echo formatDate($post['created_at'], 'M j'); ?></span>
                                    </div>
                                    <h3><a href="/article.php?slug=<?php echo urlencode($post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a></h3>
                                </div>
                            </article>
                        <?php endfor; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Ads Section -->
                <?php $header_ads = getActiveAdsByPosition('header'); ?>
                <?php if (!empty($header_ads)): ?>
                    <section class="ads-section">
                        <?php foreach ($header_ads as $ad): ?>
                            <div class="ad-banner">
                                <?php if ($ad['link_url']): ?>
                                    <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" onclick="trackAdClick(<?php echo $ad['id']; ?>)">
                                <?php endif; ?>
                                
                                <?php if ($ad['image_url']): ?>
                                    <?php 
                                    // Handle both relative and full URL paths for backward compatibility
                                    $image_src = (strpos($ad['image_url'], 'http') === 0) ? $ad['image_url'] : UPLOAD_URL . $ad['image_url'];
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                                <?php else: ?>
                                    <?php echo $ad['content']; // Content is already sanitized when saved ?>
                                <?php endif; ?>
                                
                                <?php if ($ad['link_url']): ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <!-- Latest News Section -->
                <section class="latest-news">
                    <div class="section-header">
                        <h2>Latest News</h2>
                    </div>
                    
                    <div class="news-grid">
                        <?php foreach ($recent_posts as $post): ?>
                            <article class="news-card">
                                <?php if ($post['featured_image']): ?>
                                    <div class="card-image">
                                        <img src="<?php echo UPLOAD_URL . $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="card-content">
                                    <div class="card-meta">
                                        <?php if ($post['category_name']): ?>
                                            <span class="category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        <?php endif; ?>
                                        <span class="date"><?php echo formatDate($post['created_at'], 'M j'); ?></span>
                                    </div>
                                    <h3><a href="/article.php?slug=<?php echo urlencode($post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a></h3>
                                    <p><?php echo htmlspecialchars($post['excerpt'] ?? truncateText(strip_tags($post['content']), 100)); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Sidebar Ads -->
                <?php $sidebar_ads = getActiveAdsByPosition('sidebar'); ?>
                <?php if (!empty($sidebar_ads)): ?>
                    <aside class="sidebar">
                        <div class="sidebar-ads">
                            <?php foreach ($sidebar_ads as $ad): ?>
                                <div class="sidebar-ad">
                                    <?php if ($ad['link_url']): ?>
                                        <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" onclick="trackAdClick(<?php echo $ad['id']; ?>)">
                                    <?php endif; ?>
                                    
                                    <?php if ($ad['image_url']): ?>
                                        <?php 
                                        // Handle both relative and full URL paths for backward compatibility
                                        $image_src = (strpos($ad['image_url'], 'http') === 0) ? $ad['image_url'] : UPLOAD_URL . $ad['image_url'];
                                        ?>
                                        <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                                    <?php else: ?>
                                        <?php echo $ad['content']; // Content is already sanitized when saved ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($ad['link_url']): ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars(getSiteSetting('site_name', 'News Portal')); ?></h3>
                    <p><?php echo htmlspecialchars(getSiteSetting('site_description', 'Your trusted source for news')); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4>Categories</h4>
                    <ul>
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                            <li><a href="/category.php?slug=<?php echo urlencode($category['slug']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSiteSetting('site_name', 'News Portal')); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>