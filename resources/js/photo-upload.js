// Photo Upload functionality
export function initPhotoUpload() {
    const form = document.getElementById('upload-form');
    const photoInput = document.getElementById('photo-input');
    const uploadStatus = document.getElementById('upload-status');
    const continueBtn = document.getElementById('continue-btn');

    if (!form || !photoInput || !uploadStatus || !continueBtn) return;

    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            // Show loading state
            uploadStatus.classList.remove('hidden');
            continueBtn.classList.add('hidden');
            
            // Auto-submit the form
            form.submit();
        }
    });
}