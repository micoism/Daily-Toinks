// ===================================
// ADMIN CSRF & XSS HELPERS
// Auto-patches fetch() to include CSRF token in all state-changing requests
// ===================================

(function() {
    const originalFetch = window.fetch;

    window.fetch = function(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();

        // Inject CSRF token for state-changing methods
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            const meta = document.querySelector('meta[name="csrf-token"]');
            const token = meta ? meta.getAttribute('content') : '';

            if (!options.headers) {
                options.headers = {};
            }

            // If headers is a Headers object, use set; otherwise plain object
            if (options.headers instanceof Headers) {
                options.headers.set('X-CSRF-Token', token);
            } else {
                options.headers['X-CSRF-Token'] = token;
            }

            // Also append to FormData body if applicable
            if (options.body instanceof FormData) {
                options.body.append('csrf_token', token);
            }
        }

        return originalFetch.call(this, url, options);
    };
})();

// XSS escape helper for admin pages
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}
