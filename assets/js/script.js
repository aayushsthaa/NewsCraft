// Main website JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    initWebsite();
});

function initWebsite() {
    // Initialize mobile navigation
    initMobileNav();
    
    // Initialize search functionality
    initSearch();
    
    // Initialize ads tracking
    initAdsTracking();
    
    // Initialize lazy loading for images
    initLazyLoading();
    
    // Initialize smooth scrolling
    initSmoothScrolling();
}

function initMobileNav() {
    // Create mobile menu toggle button
    const header = document.querySelector('.site-header');
    const nav = document.querySelector('.main-nav');
    
    if (nav && window.innerWidth <= 768) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-nav-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.setAttribute('aria-label', 'Toggle navigation');
        
        header.querySelector('.header-top').appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            nav.classList.toggle('mobile-nav-active');
            toggleBtn.querySelector('i').classList.toggle('fa-bars');
            toggleBtn.querySelector('i').classList.toggle('fa-times');
        });
        
        // Close mobile nav when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !toggleBtn.contains(e.target)) {
                nav.classList.remove('mobile-nav-active');
                toggleBtn.querySelector('i').classList.add('fa-bars');
                toggleBtn.querySelector('i').classList.remove('fa-times');
            }
        });
    }
}

function initSearch() {
    const searchForm = document.querySelector('.search-box form');
    const searchInput = document.querySelector('.search-box input');
    
    if (searchForm && searchInput) {
        // Add search suggestions functionality
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                // Could implement search suggestions here
                // For now, just validate the input
                this.classList.toggle('has-content', query.length > 0);
            }
        });
        
        // Handle form submission
        searchForm.addEventListener('submit', function(e) {
            const query = searchInput.value.trim();
            if (!query) {
                e.preventDefault();
                searchInput.focus();
                showNotification('Please enter a search term', 'warning');
            }
        });
    }
}

function initAdsTracking() {
    // Track ad clicks for analytics
    window.trackAdClick = function(adId) {
        if (adId) {
            fetch('/track-ad.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ad_id: adId })
            }).catch(error => {
                console.log('Ad tracking failed:', error);
            });
        }
    };
}

function initLazyLoading() {
    // Simple lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => {
            img.classList.add('lazy');
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers without IntersectionObserver
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }
}

function initSmoothScrolling() {
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const target = document.querySelector(href);
            
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${getNotificationColor(type)};
        color: white;
        border-radius: 6px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        font-weight: 500;
        max-width: 300px;
        animation: slideInNotification 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutNotification 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

function getNotificationColor(type) {
    const colors = {
        success: '#2ecc71',
        error: '#e74c3c',
        warning: '#f39c12',
        info: '#3498db'
    };
    return colors[type] || colors.info;
}

// Add CSS for notifications and mobile nav
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInNotification {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutNotification {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .mobile-nav-toggle {
        display: none;
        background: #3498db;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
    }
    
    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .lazy.loaded {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .mobile-nav-toggle {
            display: block;
        }
        
        .main-nav {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .main-nav.mobile-nav-active {
            display: block;
        }
        
        .main-nav ul {
            flex-direction: column;
            padding: 20px;
            gap: 0;
        }
        
        .main-nav li {
            border-bottom: 1px solid #eee;
        }
        
        .main-nav li:last-child {
            border-bottom: none;
        }
        
        .main-nav a {
            display: block;
            padding: 15px 0;
        }
    }
`;
document.head.appendChild(style);