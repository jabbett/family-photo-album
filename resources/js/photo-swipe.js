// Photo swipe navigation functionality
// Enables touch gestures for navigating between photos on mobile devices

export function initPhotoSwipe(config = {}) {
    const photoStage = document.getElementById('photoStage');
    const img = photoStage?.querySelector('img');
    
    if (!photoStage || !img) return;

    // Navigation URLs from configuration
    const { prevPhotoUrl, nextPhotoUrl } = config;

    let touchStartY = 0;
    let touchStartX = 0;
    let isDragging = false;
    let startTime = 0;

    const SWIPE_THRESHOLD = 50; // Minimum distance for a swipe
    const VELOCITY_THRESHOLD = 0.3; // Minimum velocity (px/ms)
    const MAX_HORIZONTAL_DRIFT = 100; // Max horizontal movement to still count as vertical swipe

    function handleTouchStart(e) {
        const touch = e.touches[0];
        touchStartY = touch.clientY;
        touchStartX = touch.clientX;
        startTime = Date.now();
        isDragging = true;
        
        // Add slight transition for visual feedback preparation
        img.style.transition = 'transform 0.1s ease-out';
    }

    function handleTouchMove(e) {
        if (!isDragging) return;

        const touch = e.touches[0];
        const deltaY = touch.clientY - touchStartY;
        const deltaX = Math.abs(touch.clientX - touchStartX);

        // If too much horizontal movement, cancel vertical swipe
        if (deltaX > MAX_HORIZONTAL_DRIFT) {
            resetSwipe();
            return;
        }

        // Prevent default to avoid page scrolling during swipe
        if (Math.abs(deltaY) > 10) {
            e.preventDefault();
        }

        // Apply visual feedback - subtle transform based on swipe direction
        const maxTransform = 20; // Maximum transform amount
        const transformAmount = Math.min(Math.abs(deltaY) / 5, maxTransform);
        
        if (deltaY > 10 && nextPhotoUrl) {
            // Swiping down for next photo (newer)
            img.style.transform = `translateY(${transformAmount}px) scale(${1 - transformAmount / 200})`;
            img.style.opacity = Math.max(0.7, 1 - transformAmount / 100);
        } else if (deltaY < -10 && prevPhotoUrl) {
            // Swiping up for previous photo (older)
            img.style.transform = `translateY(-${transformAmount}px) scale(${1 - transformAmount / 200})`;
            img.style.opacity = Math.max(0.7, 1 - transformAmount / 100);
        }
    }

    function handleTouchEnd(e) {
        if (!isDragging) return;

        const touch = e.changedTouches[0];
        const deltaY = touch.clientY - touchStartY;
        const deltaX = Math.abs(touch.clientX - touchStartX);
        const deltaTime = Date.now() - startTime;
        const velocity = Math.abs(deltaY) / deltaTime;

        isDragging = false;

        // Check if this was a valid vertical swipe
        const isVerticalSwipe = deltaX < MAX_HORIZONTAL_DRIFT;
        const isSignificantSwipe = Math.abs(deltaY) > SWIPE_THRESHOLD || velocity > VELOCITY_THRESHOLD;

        if (isVerticalSwipe && isSignificantSwipe) {
            if (deltaY > 0 && nextPhotoUrl) {
                // Swipe down - go to next photo (newer)
                window.location.href = nextPhotoUrl;
                return; // Don't reset transform, we're navigating
            } else if (deltaY < 0 && prevPhotoUrl) {
                // Swipe up - go to previous photo (older)
                window.location.href = prevPhotoUrl;
                return; // Don't reset transform, we're navigating
            }
        }

        // Reset visual feedback
        resetSwipe();
    }

    function resetSwipe() {
        isDragging = false;
        img.style.transition = 'transform 0.2s ease-out, opacity 0.2s ease-out';
        img.style.transform = '';
        img.style.opacity = '';
        
        // Clean up transition after animation
        setTimeout(() => {
            img.style.transition = '';
        }, 200);
    }

    // Add touch event listeners
    photoStage.addEventListener('touchstart', handleTouchStart, { passive: true });
    photoStage.addEventListener('touchmove', handleTouchMove, { passive: false });
    photoStage.addEventListener('touchend', handleTouchEnd, { passive: true });
    photoStage.addEventListener('touchcancel', resetSwipe, { passive: true });

    // Return cleanup function
    return function cleanup() {
        photoStage.removeEventListener('touchstart', handleTouchStart);
        photoStage.removeEventListener('touchmove', handleTouchMove);
        photoStage.removeEventListener('touchend', handleTouchEnd);
        photoStage.removeEventListener('touchcancel', resetSwipe);
    };
}