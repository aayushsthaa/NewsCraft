<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = 'Add Advertisement';

$errors = [];
$success = false;

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
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_url = uploadImage($_FILES['image'], 'ads');
            if (!$image_url) {
                $errors[] = 'Failed to upload image. Please check file format and size.';
            }
        }
        
        // If no image and no content, error
        if (empty($image_url) && empty($content)) {
            $errors[] = 'Please provide either an image or content for the advertisement.';
        }
        
        // Save to database if no errors
        if (empty($errors)) {
            $db = Database::getInstance();
            
            $adData = [
                'title' => $title,
                'content' => $content,
                'image_url' => $image_url ?: null,
                'link_url' => $link_url ?: null,
                'position' => $position,
                'is_active' => $is_active,
                'start_date' => $start_date ?: null,
                'end_date' => $end_date ?: null,
                'click_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($db->insert('ads', $adData)) {
                $_SESSION['success'] = 'Advertisement created successfully!';
                header('Location: ads.php');
                exit;
            } else {
                $errors[] = 'Failed to save advertisement. Please try again.';
            }
        }
    }
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
        <h1><i class="fas fa-plus"></i> Add New Advertisement</h1>
        <a href="ads.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Ads
        </a>
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
                    <button type="button" class="tab-button active" data-tab="image">Image Ad</button>
                    <button type="button" class="tab-button" data-tab="html">HTML/Text Ad</button>
                </div>
                
                <div class="tab-content active" id="tab-image">
                    <div class="form-group">
                        <label for="image">Upload Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small class="form-help">Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
                        <div id="image_preview" class="image-preview"></div>
                    </div>
                </div>
                
                <div class="tab-content" id="tab-html">
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
                               <?php echo (($_POST['is_active'] ?? '1') === '1') ? 'checked' : ''; ?>>
                        <span class="checkmark"></span>
                        Make this advertisement active immediately
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Create Advertisement
                </button>
                <a href="ads.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
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
}
</style>

<script>
// Tab switching functionality
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        
        // Update buttons
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        // Update content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    });
});

// Image preview functionality
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