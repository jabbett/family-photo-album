// Photo Upload functionality with background upload
export function initPhotoUpload() {
    const form = document.getElementById('upload-form');
    const photoInput = document.getElementById('photo-input');
    const filePickerContainer = document.getElementById('file-picker-container');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const captionTextarea = document.getElementById('caption-textarea');
    const continueBtn = document.getElementById('continue-btn');
    const uploadStatus = document.getElementById('upload-status');
    const errorContainer = document.getElementById('error-container');

    if (!form || !photoInput || !previewContainer || !continueBtn) {
        console.error('Required upload form elements not found');
        return;
    }

    let uploadedPhotoId = null;
    let uploadInProgress = false;
    let uploadComplete = false;

    // Handle file selection
    photoInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Reset state
        errorContainer.classList.add('hidden');
        errorContainer.textContent = '';

        // Check if file is HEIC/HEIF (may not be previewable in all browsers)
        const fileName = file.name.toLowerCase();
        const isHeic = fileName.endsWith('.heic') || fileName.endsWith('.heif');

        // 1. INSTANT PREVIEW (FileReader API)
        const reader = new FileReader();
        reader.onload = (e) => {
            if (isHeic) {
                // For HEIC, try to display but have fallback ready
                const handleHeicSuccess = () => {
                    // Browser CAN display HEIC (e.g., Safari) - show it!
                    previewImage.removeEventListener('load', handleHeicSuccess);
                    previewImage.removeEventListener('error', handleHeicError);
                    previewContainer.classList.remove('hidden');
                    filePickerContainer.classList.add('hidden');
                    if (captionTextarea) {
                        captionTextarea.focus();
                    }
                };
                const handleHeicError = () => {
                    // Browser can't display HEIC - show placeholder
                    previewImage.removeEventListener('load', handleHeicSuccess);
                    previewImage.removeEventListener('error', handleHeicError);
                    showHeicPlaceholder();
                };

                previewImage.addEventListener('load', handleHeicSuccess, { once: true });
                previewImage.addEventListener('error', handleHeicError, { once: true });
                previewImage.src = e.target.result;
            } else {
                // Regular image - show immediately
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
                filePickerContainer.classList.add('hidden');
                if (captionTextarea) {
                    captionTextarea.focus();
                }
            }
        };
        reader.onerror = () => {
            showError('Failed to read file. Please try again.');
            resetToFilePicker();
        };
        reader.readAsDataURL(file);

        // 2. BACKGROUND UPLOAD (Fetch API)
        await uploadFileInBackground(file);
    });

    async function uploadFileInBackground(file) {
        uploadInProgress = true;
        uploadComplete = false;
        uploadedPhotoId = null;
        uploadStatus.classList.remove('hidden');

        const formData = new FormData();
        formData.append('photo', file);

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

            uploadedPhotoId = data.photo_id;
            uploadComplete = true;
            uploadStatus.classList.add('hidden');

            console.log('Upload completed', { photo_id: uploadedPhotoId });

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
    }

    function showHeicPlaceholder() {
        // HEIC files can't be previewed in browsers, so show a placeholder
        // Use an SVG data URL for a clean placeholder icon
        const placeholderSvg = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 400 400'%3E%3Crect width='400' height='400' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='45%25' font-family='system-ui' font-size='20' fill='%239ca3af' text-anchor='middle' dominant-baseline='middle'%3EHEIC Photo%3C/text%3E%3Ctext x='50%25' y='55%25' font-family='system-ui' font-size='14' fill='%239ca3af' text-anchor='middle' dominant-baseline='middle'%3EPreview not available%3C/text%3E%3C/svg%3E`;

        previewImage.src = placeholderSvg;
        previewContainer.classList.remove('hidden');
        filePickerContainer.classList.add('hidden');
        if (captionTextarea) {
            captionTextarea.focus();
        }
    }

    function resetToFilePicker() {
        // Reset to file picker (but keep caption!)
        previewContainer.classList.add('hidden');
        filePickerContainer.classList.remove('hidden');
        photoInput.value = ''; // Reset file input to allow re-selection
    }

    // 3. CONTINUE BUTTON: Wait for upload if needed, then submit caption
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
            await waitForUploadComplete();

            continueBtn.textContent = originalText;
            continueBtn.disabled = false;
        }

        if (!uploadComplete || !uploadedPhotoId) {
            showError('Upload failed. Please select a photo and try again.');
            resetToFilePicker();
            return;
        }

        // Submit caption and navigate to crop
        submitCaptionAndCrop();
    });

    async function waitForUploadComplete() {
        // Simple polling with timeout
        const maxWait = 30000; // 30 seconds
        const startTime = Date.now();

        while (uploadInProgress && (Date.now() - startTime) < maxWait) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }

        // Check if we timed out
        if (uploadInProgress) {
            throw new Error('Upload timed out');
        }
    }

    function submitCaptionAndCrop() {
        const caption = captionTextarea ? captionTextarea.value : '';

        // Create a form and submit it (traditional POST)
        const captionForm = document.createElement('form');
        captionForm.method = 'POST';
        captionForm.action = `/photos/${uploadedPhotoId}/caption`;

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content
                       || document.querySelector('[name="_token"]')?.value;

        // Add caption
        const captionInput = document.createElement('input');
        captionInput.type = 'hidden';
        captionInput.name = 'caption';
        captionInput.value = caption;

        captionForm.appendChild(csrfInput);
        captionForm.appendChild(captionInput);
        document.body.appendChild(captionForm);
        captionForm.submit();
    }
}
