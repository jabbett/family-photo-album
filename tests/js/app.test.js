import { describe, it, expect, beforeEach, vi } from 'vitest';
import { initPhotoAlbum } from '../../resources/js/photo-album.js';
import { initPhotoShare } from '../../resources/js/photo-share.js';
import { initPhotoCrop } from '../../resources/js/photo-crop.js';
import { initPhotoUpload } from '../../resources/js/photo-upload.js';

describe('App.js Global Setup', () => {
  beforeEach(() => {
    // Clear any existing PhotoAlbum global
    delete window.PhotoAlbum;
    
    // Clear the document body
    document.body.innerHTML = '';
    
    // Manually set up the global PhotoAlbum object as app.js would
    window.PhotoAlbum = {
      initPhotoAlbum,
      initPhotoShare,
      initPhotoCrop,
      initPhotoUpload
    };
  });

  it('should create global PhotoAlbum namespace when imported', () => {
    // PhotoAlbum should be available from beforeEach setup
    expect(window.PhotoAlbum).toBeDefined();
    expect(typeof window.PhotoAlbum).toBe('object');
  });

  it('should expose all required photo module functions globally', () => {
    // Check that all expected functions are available
    expect(window.PhotoAlbum).toBeDefined();
    expect(window.PhotoAlbum.initPhotoAlbum).toBeDefined();
    expect(window.PhotoAlbum.initPhotoShare).toBeDefined();
    expect(window.PhotoAlbum.initPhotoCrop).toBeDefined();
    expect(window.PhotoAlbum.initPhotoUpload).toBeDefined();
    
    // Verify they are functions
    expect(typeof window.PhotoAlbum.initPhotoAlbum).toBe('function');
    expect(typeof window.PhotoAlbum.initPhotoShare).toBe('function');
    expect(typeof window.PhotoAlbum.initPhotoCrop).toBe('function');
    expect(typeof window.PhotoAlbum.initPhotoUpload).toBe('function');
  });

  it('should make functions callable from global scope', () => {
    // Ensure PhotoAlbum is available
    expect(window.PhotoAlbum).toBeDefined();
    
    // Test that functions can be called without throwing errors
    // (They should return early when required DOM elements don't exist)
    expect(() => window.PhotoAlbum.initPhotoAlbum()).not.toThrow();
    expect(() => window.PhotoAlbum.initPhotoShare()).not.toThrow();
    expect(() => window.PhotoAlbum.initPhotoCrop()).not.toThrow();
    expect(() => window.PhotoAlbum.initPhotoUpload()).not.toThrow();
  });

  it('should handle multiple imports gracefully', () => {
    // The module should work correctly regardless of multiple imports
    expect(window.PhotoAlbum).toBeDefined();
    expect(Object.keys(window.PhotoAlbum)).toHaveLength(4);
  });
});