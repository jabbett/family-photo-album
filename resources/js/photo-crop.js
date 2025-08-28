// Photo Crop functionality using CropperJS v1.6.2
import Cropper from 'cropperjs';
export function initPhotoCrop() {
    const image = document.getElementById('crop-image');
    const form = document.getElementById('crop-form');
    if (!image || !form) return;

    let cropper;

    function initCropper() {
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            scalable: false,
            rotatable: false,
        });
    }

    if (image.complete) {
        initCropper();
    } else {
        image.addEventListener('load', initCropper);
    }

    form.addEventListener('submit', function(e) {
        if (!cropper) return;
        
        const data = cropper.getData(true); // integers
        document.getElementById('crop_x').value = Math.max(0, Math.round(data.x));
        document.getElementById('crop_y').value = Math.max(0, Math.round(data.y));
        // For square aspect, width == height
        document.getElementById('crop_size').value = Math.max(1, Math.round(Math.min(data.width, data.height)));
    });
}
