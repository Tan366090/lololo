// Loading system
function showLoading() {
    const loader = document.getElementById('loadingOverlay');
    if (loader) {
        loader.style.display = 'flex';
    }
}

function hideLoading() {
    const loader = document.getElementById('loadingOverlay');
    if (loader) {
        loader.style.display = 'none';
    }
}

// Export functions
window.showLoading = showLoading;
window.hideLoading = hideLoading; 