// TechnoHacks Solutions ERP - Main JavaScript

// Toast notification system
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed shadow-lg animate-fade-in`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px;';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// Show success message from URL params
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('msg')) {
        showToast(params.get('msg'), 'success');
        window.history.replaceState({}, '', window.location.pathname);
    }
});

// Confirm delete actions
function confirmDelete(msg) {
    return confirm(msg || 'Are you sure you want to delete this item?');
}
