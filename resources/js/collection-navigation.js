// Collection navigation with horizontal swipe gestures and thumbnail controls

export function initCollectionNavigation(config) {
    const { photos, prevPostUrl, nextPostUrl } = config;

    if (!photos || photos.length === 0) {
        console.error('No photos provided for collection navigation');
        return;
    }

    const photoStage = document.getElementById('photoStage');
    const downloadButton = document.getElementById('downloadButton');
    const positionIndicator = document.getElementById('currentPhotoPosition');
    const thumbnailButtons = document.querySelectorAll('.thumbnail-button');

    if (!photoStage) {
        console.error('Photo stage element not found');
        return;
    }

    let currentPhotoIndex = 0;
    let touchStartX = 0;
    let touchStartY = 0;
    const swipeThreshold = 50;

    // Go to specific photo by index
    window.PhotoAlbum = window.PhotoAlbum || {};
    window.PhotoAlbum.goToPhoto = function(index) {
        if (index < 0 || index >= photos.length) return;

        // Hide all photo slides
        const slides = document.querySelectorAll('.photo-slide');
        slides.forEach(slide => slide.classList.add('hidden'));

        // Show target photo slide
        const targetSlide = document.querySelector(`[data-photo-index="${index}"]`);
        if (targetSlide) {
            targetSlide.classList.remove('hidden');
        }

        // Update position indicator
        if (positionIndicator) {
            positionIndicator.textContent = index + 1;
        }

        // Update thumbnail highlights
        thumbnailButtons.forEach((thumb, i) => {
            if (i === index) {
                thumb.classList.add('ring-2', 'ring-blue-500');
            } else {
                thumb.classList.remove('ring-2', 'ring-blue-500');
            }
        });

        // Update download button URL
        if (downloadButton && photos[index]) {
            downloadButton.href = photos[index].download_url;
        }

        currentPhotoIndex = index;
    };

    // Handle touch start
    photoStage.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    }, { passive: true });

    // Handle touch end with swipe detection
    photoStage.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;

        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;

        // Determine if horizontal or vertical swipe based on which delta is larger
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            // Horizontal swipe - navigate within collection
            if (Math.abs(deltaX) > swipeThreshold) {
                if (deltaX > 0 && currentPhotoIndex > 0) {
                    // Swipe right = previous photo in collection
                    window.PhotoAlbum.goToPhoto(currentPhotoIndex - 1);
                } else if (deltaX < 0 && currentPhotoIndex < photos.length - 1) {
                    // Swipe left = next photo in collection
                    window.PhotoAlbum.goToPhoto(currentPhotoIndex + 1);
                }
            }
        } else {
            // Vertical swipe - navigate between posts
            if (Math.abs(deltaY) > swipeThreshold) {
                if (deltaY > 0 && prevPostUrl) {
                    // Swipe down = previous post
                    window.location.href = prevPostUrl;
                } else if (deltaY < 0 && nextPostUrl) {
                    // Swipe up = next post
                    window.location.href = nextPostUrl;
                }
            }
        }
    }, { passive: true });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        // Horizontal arrows for photos in collection
        if (e.key === 'ArrowLeft' && currentPhotoIndex > 0) {
            e.preventDefault();
            window.PhotoAlbum.goToPhoto(currentPhotoIndex - 1);
        } else if (e.key === 'ArrowRight' && currentPhotoIndex < photos.length - 1) {
            e.preventDefault();
            window.PhotoAlbum.goToPhoto(currentPhotoIndex + 1);
        }
        // Vertical arrows for posts
        else if (e.key === 'ArrowUp' && prevPostUrl) {
            e.preventDefault();
            window.location.href = prevPostUrl;
        } else if (e.key === 'ArrowDown' && nextPostUrl) {
            e.preventDefault();
            window.location.href = nextPostUrl;
        }
    });
}
