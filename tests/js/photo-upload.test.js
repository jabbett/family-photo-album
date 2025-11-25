import { describe, it, expect, beforeEach, vi } from 'vitest';
import { initPhotoUpload } from '../../resources/js/photo-upload.js';

describe('Photo Upload', () => {
  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';
  });

  it('should return early when required DOM elements are missing', () => {
    // Call without any DOM elements
    const result = initPhotoUpload();
    expect(result).toBeUndefined();
  });

  it('should return early when upload-form is missing', () => {
    // Create only some elements
    document.body.innerHTML = `
      <input type="file" id="photo-input">
      <div id="upload-status" class="hidden">Uploading...</div>
      <button id="continue-btn">Continue</button>
    `;
    
    const result = initPhotoUpload();
    expect(result).toBeUndefined();
  });

  it('should return early when photo-input is missing', () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="upload-status" class="hidden">Uploading...</div>
        <button id="continue-btn">Continue</button>
      </form>
    `;
    
    const result = initPhotoUpload();
    expect(result).toBeUndefined();
  });

  it('should return early when upload-status is missing', () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <input type="file" id="photo-input">
        <button id="continue-btn">Continue</button>
      </form>
    `;
    
    const result = initPhotoUpload();
    expect(result).toBeUndefined();
  });

  it('should return early when continue-btn is missing', () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <input type="file" id="photo-input">
        <div id="upload-status" class="hidden">Uploading...</div>
      </form>
    `;
    
    const result = initPhotoUpload();
    expect(result).toBeUndefined();
  });

  it('should initialize successfully with all required elements', () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <input type="file" id="photo-input" accept="image/*">
        <div id="upload-status" class="hidden">Uploading...</div>
        <button id="continue-btn">Continue</button>
      </form>
    `;
    
    const result = initPhotoUpload();
    expect(result).toBeUndefined(); // Function doesn't return anything
    
    // Check that event listener was added (we can't directly test this, but we can test the DOM is ready)
    const photoInput = document.getElementById('photo-input');
    expect(photoInput).toBeTruthy();
  });

  it('should handle file selection and trigger form submission', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    // Mock global fetch for async upload
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          photo_id: 123
        })
      })
    );

    // Mock FileReader
    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/jpeg;base64,test' } });
        }, 0);
      }
    };

    // Initialize the upload functionality
    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const uploadStatus = document.getElementById('upload-status');
    const filePickerContainer = document.getElementById('file-picker-container');
    const previewContainer = document.getElementById('preview-container');

    // Create a mock file
    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });

    // Mock the files property
    Object.defineProperty(photoInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    // Trigger the change event
    const changeEvent = new Event('change');
    photoInput.dispatchEvent(changeEvent);

    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 50));

    // Verify UI state changes for NEW async flow:
    // - File picker should be hidden
    // - Preview should be visible
    // - Upload status should have been shown during upload
    expect(filePickerContainer.classList.contains('hidden')).toBe(true);
    expect(previewContainer.classList.contains('hidden')).toBe(false);

    // Upload should be complete, so status should be hidden again
    expect(uploadStatus.classList.contains('hidden')).toBe(true);

    // Verify fetch was called with correct data
    expect(global.fetch).toHaveBeenCalledWith(
      '/photos/upload/async',
      expect.objectContaining({
        method: 'POST',
      })
    );
  });

  it('should not submit form when no file is selected', () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <input type="file" id="photo-input" accept="image/*">
        <div id="upload-status" class="hidden">Uploading...</div>
        <button id="continue-btn">Continue</button>
      </form>
    `;
    
    // Mock form.submit()
    const form = document.getElementById('upload-form');
    const mockSubmit = vi.fn();
    form.submit = mockSubmit;
    
    // Initialize the upload functionality
    initPhotoUpload();
    
    const photoInput = document.getElementById('photo-input');
    const uploadStatus = document.getElementById('upload-status');
    const continueBtn = document.getElementById('continue-btn');
    
    // Mock empty files
    Object.defineProperty(photoInput, 'files', {
      value: [],
      writable: false,
    });
    
    // Trigger the change event
    const changeEvent = new Event('change');
    photoInput.dispatchEvent(changeEvent);
    
    // Verify UI state didn't change
    expect(uploadStatus.classList.contains('hidden')).toBe(true);
    expect(continueBtn.classList.contains('hidden')).toBe(false);
    
    // Verify form submission was NOT triggered
    expect(mockSubmit).not.toHaveBeenCalled();
  });

  it('should not submit form when files is null', () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <input type="file" id="photo-input" accept="image/*">
        <div id="upload-status" class="hidden">Uploading...</div>
        <button id="continue-btn">Continue</button>
      </form>
    `;

    // Mock form.submit()
    const form = document.getElementById('upload-form');
    const mockSubmit = vi.fn();
    form.submit = mockSubmit;

    // Initialize the upload functionality
    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');

    // Mock null files
    Object.defineProperty(photoInput, 'files', {
      value: null,
      writable: false,
    });

    // Trigger the change event
    const changeEvent = new Event('change');
    photoInput.dispatchEvent(changeEvent);

    // Verify form submission was NOT triggered
    expect(mockSubmit).not.toHaveBeenCalled();
  });

  it('should handle HEIC files that cannot be previewed', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    // Mock fetch for successful upload
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: true, photo_id: 456 })
      })
    );

    // Mock FileReader
    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/heic;base64,test' } });
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const previewImage = document.getElementById('preview-image');
    const previewContainer = document.getElementById('preview-container');
    const filePickerContainer = document.getElementById('file-picker-container');

    // Create a HEIC file
    const heicFile = new File(['test'], 'test.heic', { type: 'image/heic' });
    Object.defineProperty(photoInput, 'files', {
      value: [heicFile],
      writable: false,
    });

    // Trigger change event
    photoInput.dispatchEvent(new Event('change'));

    // Wait for FileReader
    await new Promise(resolve => setTimeout(resolve, 10));

    // Simulate image error (browser can't display HEIC)
    previewImage.dispatchEvent(new Event('error'));

    // Wait for error handler
    await new Promise(resolve => setTimeout(resolve, 50));

    // Preview should still be shown (with placeholder)
    expect(filePickerContainer.classList.contains('hidden')).toBe(true);
    expect(previewContainer.classList.contains('hidden')).toBe(false);
  });

  it('should handle HEIC files that can be previewed', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: true, photo_id: 789 })
      })
    );

    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/heic;base64,test' } });
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const previewImage = document.getElementById('preview-image');

    const heicFile = new File(['test'], 'photo.HEIF', { type: 'image/heif' });
    Object.defineProperty(photoInput, 'files', {
      value: [heicFile],
      writable: false,
    });

    photoInput.dispatchEvent(new Event('change'));
    await new Promise(resolve => setTimeout(resolve, 10));

    // Simulate successful load (Safari can display HEIC)
    previewImage.dispatchEvent(new Event('load'));
    await new Promise(resolve => setTimeout(resolve, 50));

    expect(document.getElementById('preview-container').classList.contains('hidden')).toBe(false);
  });

  it('should handle FileReader errors', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onerror();
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const errorContainer = document.getElementById('error-container');
    const filePickerContainer = document.getElementById('file-picker-container');

    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(photoInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    photoInput.dispatchEvent(new Event('change'));
    await new Promise(resolve => setTimeout(resolve, 10));

    // Error should be shown
    expect(errorContainer.classList.contains('hidden')).toBe(false);
    expect(errorContainer.textContent).toContain('Failed to read file');

    // Should reset to file picker
    expect(filePickerContainer.classList.contains('hidden')).toBe(false);
  });

  it('should handle upload failures', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    // Mock fetch to return error
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
        json: () => Promise.resolve({ success: false, message: 'Upload failed' })
      })
    );

    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/jpeg;base64,test' } });
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const errorContainer = document.getElementById('error-container');

    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(photoInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    photoInput.dispatchEvent(new Event('change'));
    await new Promise(resolve => setTimeout(resolve, 100));

    // Error should be displayed
    expect(errorContainer.classList.contains('hidden')).toBe(false);
    expect(errorContainer.textContent).toContain('Upload failed');
  });

  it('should handle Continue button click when upload is complete', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <meta name="csrf-token" content="test-token">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea">My caption</textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: true, photo_id: 123 })
      })
    );

    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/jpeg;base64,test' } });
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const continueBtn = document.getElementById('continue-btn');

    // Upload a file
    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(photoInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    photoInput.dispatchEvent(new Event('change'));
    await new Promise(resolve => setTimeout(resolve, 100));

    // Mock form submission
    let submittedForm = null;
    const originalAppendChild = document.body.appendChild;
    document.body.appendChild = vi.fn((element) => {
      if (element.tagName === 'FORM') {
        submittedForm = element;
        element.submit = vi.fn();
      }
      return originalAppendChild.call(document.body, element);
    });

    // Click continue
    continueBtn.click();
    await new Promise(resolve => setTimeout(resolve, 10));

    // Verify form was created and submitted
    expect(submittedForm).toBeTruthy();
    expect(submittedForm.action).toContain('/photos/123/caption');
    expect(submittedForm.method.toUpperCase()).toBe('POST');
  });

  it('should handle Continue button click when upload is still in progress', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <meta name="csrf-token" content="test-token">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea">My caption</textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    // Mock slow upload
    let resolveUpload;
    global.fetch = vi.fn(() => new Promise((resolve) => {
      resolveUpload = () => resolve({
        ok: true,
        json: () => Promise.resolve({ success: true, photo_id: 999 })
      });
    }));

    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/jpeg;base64,test' } });
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const continueBtn = document.getElementById('continue-btn');

    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(photoInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    photoInput.dispatchEvent(new Event('change'));
    await new Promise(resolve => setTimeout(resolve, 10));

    // Click continue while upload is in progress
    const clickPromise = new Promise(async (resolve) => {
      continueBtn.click();
      await new Promise(r => setTimeout(r, 10));

      // Button should be disabled and text changed
      expect(continueBtn.disabled).toBe(true);
      expect(continueBtn.textContent).toContain('Finishing upload');

      // Complete the upload
      resolveUpload();
      await new Promise(r => setTimeout(r, 50));

      resolve();
    });

    await clickPromise;
  });

  it('should show error when Continue is clicked without upload', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    initPhotoUpload();

    const continueBtn = document.getElementById('continue-btn');
    const errorContainer = document.getElementById('error-container');

    continueBtn.click();
    await new Promise(resolve => setTimeout(resolve, 10));

    expect(errorContainer.classList.contains('hidden')).toBe(false);
    expect(errorContainer.textContent).toContain('Upload failed');
  });

  it('should handle network errors during upload', async () => {
    document.body.innerHTML = `
      <form id="upload-form">
        <div id="file-picker-container">
          <input type="file" id="photo-input" accept="image/*">
        </div>
        <div id="preview-container" class="hidden">
          <img id="preview-image" src="" alt="Preview">
          <textarea id="caption-textarea"></textarea>
        </div>
        <div id="upload-status" class="hidden">Uploading...</div>
        <div id="error-container" class="hidden"></div>
        <button id="continue-btn" type="button">Continue</button>
      </form>
    `;

    global.fetch = vi.fn(() => Promise.reject(new Error('Network error')));

    global.FileReader = class {
      readAsDataURL() {
        setTimeout(() => {
          this.onload({ target: { result: 'data:image/jpeg;base64,test' } });
        }, 0);
      }
    };

    initPhotoUpload();

    const photoInput = document.getElementById('photo-input');
    const errorContainer = document.getElementById('error-container');

    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(photoInput, 'files', {
      value: [mockFile],
      writable: false,
    });

    photoInput.dispatchEvent(new Event('change'));
    await new Promise(resolve => setTimeout(resolve, 100));

    expect(errorContainer.classList.contains('hidden')).toBe(false);
    expect(errorContainer.textContent).toContain('Network error');
  });
});