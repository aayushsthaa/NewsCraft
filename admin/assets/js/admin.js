// Admin Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin panel
    initAdmin();
});

function initAdmin() {
    // Handle sidebar navigation
    initSidebar();
    
    // Handle form submissions
    initForms();
    
    // Handle delete confirmations
    initDeleteConfirmations();
    
    // Handle image uploads
    initImageUpload();
    
    // Handle WYSIWYG editor
    initEditor();
}

function initSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }
    
    // Auto-close sidebar on mobile when clicking outside
    if (window.innerWidth <= 768) {
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !e.target.matches('.sidebar-toggle')) {
                sidebar.classList.remove('active');
            }
        });
    }
}

function initForms() {
    // Auto-generate slugs from titles
    const titleInputs = document.querySelectorAll('input[name="title"]');
    const slugInputs = document.querySelectorAll('input[name="slug"]');
    
    titleInputs.forEach((titleInput, index) => {
        if (slugInputs[index]) {
            titleInput.addEventListener('input', function() {
                if (!slugInputs[index].value || slugInputs[index].dataset.auto !== 'false') {
                    slugInputs[index].value = generateSlug(this.value);
                    slugInputs[index].dataset.auto = 'true';
                }
            });
            
            slugInputs[index].addEventListener('input', function() {
                this.dataset.auto = 'false';
            });
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function generateSlug(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '') // Remove special characters
        .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Clear previous error states
    form.querySelectorAll('.form-error').forEach(error => error.remove());
    form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        }
    });
    
    // Validate email fields
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });
    
    // Validate URL fields
    const urlFields = form.querySelectorAll('input[type="url"]');
    urlFields.forEach(field => {
        if (field.value && !isValidUrl(field.value)) {
            showFieldError(field, 'Please enter a valid URL');
            isValid = false;
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.btn-delete, .delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const itemName = this.dataset.itemName || 'this item';
            const confirmMessage = `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });
}

function initImageUpload() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Preview image
                const previewContainer = document.getElementById(this.id + '_preview');
                if (previewContainer) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewContainer.innerHTML = `
                            <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 6px;">
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    });
}

function initEditor() {
    // Simple WYSIWYG toolbar for content areas
    const textareas = document.querySelectorAll('textarea[data-editor="true"]');
    
    textareas.forEach(textarea => {
        createEditorToolbar(textarea);
    });
}

function createEditorToolbar(textarea) {
    const toolbar = document.createElement('div');
    toolbar.className = 'editor-toolbar';
    toolbar.style.cssText = `
        border: 1px solid #ddd;
        border-bottom: none;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px 6px 0 0;
        display: flex;
        gap: 5px;
    `;
    
    const buttons = [
        { name: 'Bold', tag: 'strong', icon: 'B' },
        { name: 'Italic', tag: 'em', icon: 'I' },
        { name: 'Link', tag: 'a', icon: 'Link' }
    ];
    
    buttons.forEach(buttonConfig => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = buttonConfig.icon;
        button.title = buttonConfig.name;
        button.style.cssText = `
            padding: 5px 10px;
            border: 1px solid #ccc;
            background: white;
            border-radius: 3px;
            cursor: pointer;
        `;
        
        button.addEventListener('click', function() {
            insertTag(textarea, buttonConfig.tag);
        });
        
        toolbar.appendChild(button);
    });
    
    textarea.parentNode.insertBefore(toolbar, textarea);
    textarea.style.borderRadius = '0 0 6px 6px';
}

function insertTag(textarea, tag) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    
    let replacement;
    if (tag === 'a') {
        const url = prompt('Enter URL:');
        if (url) {
            replacement = `<a href="${url}">${selectedText || 'Link text'}</a>`;
        } else {
            return;
        }
    } else {
        replacement = `<${tag}>${selectedText}</${tag}>`;
    }
    
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    
    // Set cursor position
    const newPosition = start + replacement.length;
    textarea.setSelectionRange(newPosition, newPosition);
    textarea.focus();
}

// Utility functions
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .form-group input.error,
    .form-group textarea.error,
    .form-group select.error {
        border-color: #e74c3c;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    }
`;
document.head.appendChild(style);