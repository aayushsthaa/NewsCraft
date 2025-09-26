<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = 'Edit Advertisement';

// Get advertisement ID
$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ads.php');
    exit;
}

$db = Database::getInstance();

// Get advertisement data
$ad = $db->fetch("SELECT * FROM ads WHERE id = :id", [':id' => $id]);
if (!$ad) {
    $_SESSION['error'] = 'Advertisement not found.';
    header('Location: ads.php');
    exit;
}

$errors = [];

if ($_POST) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        // Validate input
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitizeAdContent($_POST['content'] ?? '');
        $link_url = sanitize($_POST['link_url'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $start_date = sanitize($_POST['start_date'] ?? '');
        $end_date = sanitize($_POST['end_date'] ?? '');
        
        // Validation
        if (empty($title)) {
            $errors[] = 'Title is required.';
        }
        
        if (empty($position)) {
            $errors[] = 'Position is required.';
        }
        
        $valid_positions = ['header', 'sidebar', 'content-top', 'content-middle', 'content-bottom', 'footer'];
        if (!in_array($position, $valid_positions)) {
            $errors[] = 'Invalid position selected.';
        }
        
        if ($link_url && !filter_var($link_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid URL for the link.';
        }
        
        if ($start_date && $end_date && strtotime($start_date) >= strtotime($end_date)) {
            $errors[] = 'End date must be after start date.';
        }
        
        // Handle image upload
        $image_url = $ad['image_url']; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = uploadImage($_FILES['image'], 'ads');
            if ($new_image) {
                // Delete old image if exists using secure function
                if ($ad['image_url']) {
                    deleteAdImage($ad['image_url']);
                }
                $image_url = $new_image;
            } else {
                $errors[] = 'Failed to upload new image. Please check file format and size.';
            }
        }
        
        // If removing existing image
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if ($ad['image_url']) {
                deleteAdImage($ad['image_url']);
            }
            $image_url = null;
        }
        
        // If no image and no content, error
        if (empty($image_url) && empty($content)) {
            $errors[] = 'Please provide either an image or content for the advertisement.';
        }
        
        // Update database if no errors
        if (empty($errors)) {
            $updateData = [
                'title' => $title,
                'content' => $content,
                'image_url' => $image_url,
                'link_url' => $link_url ?: null,
                'position' => $position,
                'is_active' => $is_active,
                'start_date' => $start_date ?: null,
                'end_date' => $end_date ?: null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($db->update('ads', $updateData, 'id = :id', [':id' => $id])) {
                $_SESSION['success'] = 'Advertisement updated successfully!';
                header('Location: ads.php');
                exit;
            } else {
                $errors[] = 'Failed to update advertisement. Please try again.';
            }
        }
    }
} else {
    // Pre-populate form with existing data
    $_POST = $ad;
}

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

<div class="ad-form">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Advertisement</h1>
        <div class="header-actions">
            <a href="ads.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Ads
            </a>
            <?php if ($ad['link_url']): ?>
                <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" class="btn btn-info">
                    <i class="fas fa-external-link-alt"></i> Visit Link
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Ad Statistics -->
    <div class="ad-stats">
        <div class="stat-item">
            <i class="fas fa-mouse-pointer"></i>
            <span class="stat-number"><?php echo number_format($ad['click_count']); ?></span>
            <span class="stat-label">Total Clicks</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-calendar"></i>
            <span class="stat-number"><?php echo formatDate($ad['created_at'], 'M j, Y'); ?></span>
            <span class="stat-label">Created</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-eye"></i>
            <span class="stat-number"><?php echo $ad['is_active'] ? 'Active' : 'Inactive'; ?></span>
            <span class="stat-label">Status</span>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data" data-validate="true">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Advertisement Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                           placeholder="Enter a descriptive title for this ad">
                </div>
                
                <div class="form-group">
                    <label for="position">Display Position *</label>
                    <select id="position" name="position" required>
                        <option value="">Select Position</option>
                        <?php foreach ($positions as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo (($_POST['position'] ?? '') === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="link_url">Link URL (Optional)</label>
                <input type="url" id="link_url" name="link_url" 
                       value="<?php echo htmlspecialchars($_POST['link_url'] ?? ''); ?>"
                       placeholder="https://example.com">
                <small class="form-help">Where users will be taken when they click the ad</small>
            </div>
            
            <div class="form-tabs">
                <div class="tab-buttons">
                    <button type="button" class="tab-button <?php echo ($ad['image_url'] || !$ad['content']) ? 'active' : ''; ?>" data-tab="image">Image Ad</button>
                    <button type="button" class="tab-button <?php echo (!$ad['image_url'] && $ad['content']) ? 'active' : ''; ?>" data-tab="html">HTML/Text Ad</button>
                </div>
                
                <div class="tab-content <?php echo ($ad['image_url'] || !$ad['content']) ? 'active' : ''; ?>" id="tab-image">
                    <?php if ($ad['image_url']): ?>
                        <div class="current-image">
                            <label>Current Image:</label>
                            <div class="image-display">
                                <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" alt="Current ad image">
                                <div class="image-actions">
                                    <label class="remove-image-label">
                                        <input type="checkbox" name="remove_image" value="1" id="remove_image">
                                        Remove current image
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="image">Upload New Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small class="form-help">Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB. Will replace current image.</small>
                        <div id="image_preview" class="image-preview"></div>
                    </div>
                </div>
                
                <div class="tab-content <?php echo (!$ad['image_url'] && $ad['content']) ? 'active' : ''; ?>" id="tab-html">
                    <div class="form-group">
                        <label for="content">HTML/Text Content</label>
                        <textarea id="content" name="content" rows="8" 
                                  placeholder="Enter HTML code or plain text for your ad"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        <small class="form-help">You can use HTML tags for styling. For security, JavaScript is not allowed.</small>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date (Optional)</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                    <small class="form-help">When the ad should start displaying</small>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date (Optional)</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                    <small class="form-help">When the ad should stop displaying</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" 
                               <?php echo (($_POST['is_active'] ?? $ad['is_active']) ? 'checked' : ''); ?>>
                        <span class="checkmark"></span>
                        Make this advertisement active
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Advertisement
                </button>
                <a href="ads.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.ad-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.stat-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    text-align: center;
    flex: 1;
}

.stat-item i {
    font-size: 24px;
    color: #3498db;
    margin-bottom: 10px;
}

.stat-number {
    display: block;
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.current-image {
    margin-bottom: 20px;
}

.image-display img {
    max-width: 300px;
    max-height: 200px;
    border-radius: 6px;
    margin-top: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.image-actions {
    margin-top: 10px;
}

.remove-image-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #e74c3c;
    cursor: pointer;
}

.remove-image-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

/* Copy styles from ad-add.php */
.form-container {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-tabs {
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 20px;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
}

.tab-button {
    flex: 1;
    padding: 12px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
}

.tab-button.active {
    background: white;
    color: #333;
    border-bottom: 2px solid #3498db;
}

.tab-content {
    padding: 20px;
    display: none;
}

.tab-content.active {
    display: block;
}

.image-preview img {
    max-width: 300px;
    max-height: 200px;
    border-radius: 6px;
    margin-top: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.checkbox-group {
    margin-top: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-container {
        padding: 20px;
    }
    
    .ad-stats {
        flex-direction: column;
    }
    
    .header-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Same JavaScript as ad-add.php for tabs and image preview
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    });
});

document.getElementById('image').addEventListener('change', function() {
    const file = this.files[0];
    const preview = document.getElementById('image_preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Remove image functionality
document.getElementById('remove_image').addEventListener('change', function() {
    const currentImage = document.querySelector('.current-image');
    if (this.checked) {
        currentImage.style.opacity = '0.5';
    } else {
        currentImage.style.opacity = '1';
    }
});

// Date validation
document.getElementById('start_date').addEventListener('change', function() {
    const endDate = document.getElementById('end_date');
    if (this.value) {
        endDate.min = this.value;
    }
});

document.getElementById('end_date').addEventListener('change', function() {
    const startDate = document.getElementById('start_date');
    if (this.value) {
        startDate.max = this.value;
    }
});
</script>

<?php include 'includes/footer.php'; ?>