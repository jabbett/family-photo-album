// Photo Share functionality
export function initPhotoShare() {
    const btn = document.getElementById('shareButton');
    if (!btn) return;

    btn.addEventListener('click', async function() {
        try {
            if (navigator.share) {
                await navigator.share({
                    title: document.title,
                    url: window.location.href
                });
            } else if (navigator.clipboard) {
                await navigator.clipboard.writeText(window.location.href);
                // You could replace this alert with a better notification system
                alert('Link copied to clipboard');
            } else {
                // Fallback for older browsers
                prompt('Copy this link:', window.location.href);
            }
        } catch (e) {
            console.error('Share failed:', e);
        }
    });
}
