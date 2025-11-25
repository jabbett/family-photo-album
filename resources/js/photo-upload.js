// Photo Upload functionality with background upload (supports multiple photos)
export function initPhotoUpload() {
    const form = document.getElementById('upload-form');
    const photoInput = document.getElementById('photo-input');
    const filePickerContainer = document.getElementById('file-picker-container');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const photoCount = document.getElementById('photo-count');
    const captionTextarea = document.getElementById('caption-textarea');
    const continueBtn = document.getElementById('continue-btn');
    const uploadStatus = document.getElementById('upload-status');
    const errorContainer = document.getElementById('error-container');

    if (!form || !photoInput || !previewContainer || !continueBtn) {
        console.error('Required upload form elements not found');
        return;
    }

    let uploadedPostId = null;
    let uploadInProgress = false;
    let uploadComplete = false;
    let selectedFiles = [];

    // Handle file selection
    photoInput.addEventListener('change', async function(e) {
        const files = Array.from(e.target.files);

        if (files.length === 0) return;

        // Check max file limit
        if (files.length > 10) {
            showError('Maximum 10 photos allowed per post.');
            photoInput.value = '';
            return;
        }

        selectedFiles = files;

        // Reset state
        errorContainer.classList.add('hidden');
        errorContainer.textContent = '';

        // 1. INSTANT PREVIEW (FileReader API) - show all photos
        showPhotoPreview(files);

        // 2. BACKGROUND UPLOAD (Fetch API)
        await uploadFilesInBackground(files);
    });

    function showPhotoPreview(files) {
        // Clear previous previews
        previewGrid.innerHTML = '';

        // Update photo count
        const count = files.length;
        photoCount.textContent = count === 1 ? '1 photo selected' : `${count} photos selected`;

        // Show preview container
        previewContainer.classList.remove('hidden');
        filePickerContainer.classList.add('hidden');

        // Create preview for each file
        files.forEach((file, index) => {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative aspect-square rounded overflow-hidden bg-gray-100';

            const img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            img.alt = `Preview ${index + 1}`;

            const positionBadge = document.createElement('div');
            positionBadge.className = 'absolute top-1 right-1 bg-black/60 text-white text-xs px-2 py-1 rounded';
            positionBadge.textContent = index + 1;

            previewDiv.appendChild(img);
            previewDiv.appendChild(positionBadge);
            previewGrid.appendChild(previewDiv);

            // Load image preview
            const reader = new FileReader();
            reader.onload = (e) => {
                const fileName = file.name.toLowerCase();
                const isHeic = fileName.endsWith('.heic') || fileName.endsWith('.heif');

                if (isHeic) {
                    // For HEIC, try to display but have fallback
                    img.addEventListener('error', () => {
                        // Show placeholder for HEIC files that can't be previewed
                        const placeholderSvg = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' font-family='system-ui' font-size='12' fill='%239ca3af' text-anchor='middle' dominant-baseline='middle'%3EHEIC%3C/text%3E%3C/svg%3E`;
                        img.src = placeholderSvg;
                    }, { once: true });
                }

                img.src = e.target.result;
            };
            reader.onerror = () => {
                console.error('Failed to read file:', file.name);
            };
            reader.readAsDataURL(file);
        });

        // Focus caption after showing previews
        if (captionTextarea) {
            captionTextarea.focus();
        }
    }

    async function uploadFilesInBackground(files) {
        uploadInProgress = true;
        uploadComplete = false;
        uploadedPostId = null;
        uploadStatus.classList.remove('hidden');

        const formData = new FormData();

        // Add all files to formData
        files.forEach((file, index) => {
            formData.append(`photos[${index}]`, file);
        });

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                       || document.querySelector('[name="_token"]')?.value;

        if (csrfToken) {
            formData.append('_token', csrfToken);
        }

        try {
            const response = await fetch('/photos/upload/async', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Upload failed');
            }

            uploadedPostId = data.post_id;
            uploadComplete = true;
            uploadStatus.classList.add('hidden');

            console.log('Upload completed', { post_id: uploadedPostId, photo_count: data.photos.length });

        } catch (error) {
            // ERROR HANDLING: Show error, preserve caption, allow retry
            console.error('Upload error:', error);

            uploadInProgress = false;
            uploadComplete = false;
            uploadStatus.classList.add('hidden');

            showError(error.message || 'Upload failed. Please try again.');
            resetToFilePicker();

        } finally {
            uploadInProgress = false;
        }
    }

    function showError(message) {
        errorContainer.textContent = message;
        errorContainer.classList.remove('hidden');

        // Scroll error into view
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function resetToFilePicker() {
        // Reset to file picker (but keep caption!)
        previewContainer.classList.add('hidden');
        filePickerContainer.classList.remove('hidden');
        photoInput.value = ''; // Reset file input to allow re-selection
        selectedFiles = [];
    }

    // CONTINUE BUTTON: Wait for upload if needed, then submit caption
    continueBtn.addEventListener('click', async function(e) {
        e.preventDefault();

        // Clear any previous errors
        errorContainer.classList.add('hidden');

        // Wait for upload to complete if still in progress
        if (uploadInProgress) {
            continueBtn.disabled = true;
            const originalText = continueBtn.textContent;
            continueBtn.textContent = 'Finishing upload...';

            // Poll until complete
            try {
                await waitForUploadComplete();
            } catch (error) {
                showError(error.message || 'Upload timed out. Please try again.');
                continueBtn.textContent = originalText;
                continueBtn.disabled = false;
                return;
            }

            continueBtn.textContent = originalText;
            continueBtn.disabled = false;
        }

        if (!uploadComplete || !uploadedPostId) {
            showError('Upload failed. Please select photos and try again.');
            resetToFilePicker();
            return;
        }

        // Submit caption via hidden form (will redirect to crop or complete)
        const captionForm = document.getElementById('caption-submit-form');
        const captionHiddenInput = document.getElementById('caption-hidden-input');

        if (captionForm && captionHiddenInput) {
            captionForm.action = `/posts/${uploadedPostId}/caption`;
            captionHiddenInput.value = captionTextarea ? captionTextarea.value : '';
            captionForm.submit();
        } else {
            console.error('Caption form elements not found');
            showError('Unable to continue. Please refresh and try again.');
        }
    });

    async function waitForUploadComplete() {
        // Simple polling with timeout
        const maxWait = 60000; // 60 seconds (longer for multiple files)
        const startTime = Date.now();

        while (uploadInProgress && (Date.now() - startTime) < maxWait) {
            await new Promise(resolve => setTimeout(resolve, 200));
        }

        // Check if we timed out
        if (uploadInProgress) {
            uploadInProgress = false;
            throw new Error('Upload timed out');
        }
    }
}
