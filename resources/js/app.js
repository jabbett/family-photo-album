// Main application JavaScript entry point

// Import photo album modules
import { initPhotoAlbum } from './photo-album.js';
import { initPhotoShare } from './photo-share.js';
import { initPhotoCrop } from './photo-crop.js';

// Make functions available globally for inline script initialization
window.PhotoAlbum = {
    initPhotoAlbum,
    initPhotoShare,
    initPhotoCrop
};
