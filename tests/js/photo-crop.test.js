import { describe, it, expect, beforeEach, vi } from 'vitest';

// Mock CropperJS before any imports
const mockCropper = {
  getData: vi.fn(),
  destroy: vi.fn(),
};

const MockedCropper = vi.fn(() => mockCropper);

vi.mock('cropperjs', () => ({
  default: MockedCropper
}));

// Import after mocking
const { initPhotoCrop } = await import('../../resources/js/photo-crop.js');

describe('Photo Crop', () => {
  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';
    
    // Reset all mocks
    vi.clearAllMocks();
  });

  it('should return early when crop-image element is missing', () => {
    document.body.innerHTML = `
      <form id="crop-form">
        <input type="hidden" id="crop_x">
        <input type="hidden" id="crop_y">
        <input type="hidden" id="crop_size">
      </form>
    `;
    
    const result = initPhotoCrop();
    expect(result).toBeUndefined();
    
    // Verify Cropper was not instantiated
    expect(MockedCropper).not.toHaveBeenCalled();
  });

  it('should return early when crop-form element is missing', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
    `;
    
    const result = initPhotoCrop();
    expect(result).toBeUndefined();
    
    // Verify Cropper was not instantiated
    expect(MockedCropper).not.toHaveBeenCalled();
  });

  it('should initialize Cropper immediately when image is already loaded', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x">
        <input type="hidden" id="crop_y">
        <input type="hidden" id="crop_size">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    // Mock image as already complete
    Object.defineProperty(image, 'complete', { value: true });
    
    initPhotoCrop();
    
    // Verify Cropper was instantiated with correct options
    expect(MockedCropper).toHaveBeenCalledWith(image, {
      aspectRatio: 1,
      viewMode: 1,
      dragMode: 'move',
      autoCropArea: 1,
      movable: true,
      zoomable: true,
      scalable: false,
      rotatable: false,
    });
  });

  it('should initialize Cropper on image load event when image is not complete', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x">
        <input type="hidden" id="crop_y">
        <input type="hidden" id="crop_size">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    // Mock image as not complete
    Object.defineProperty(image, 'complete', { value: false });
    
    initPhotoCrop();
    
    // Cropper should not be instantiated yet
    expect(MockedCropper).not.toHaveBeenCalled();
    
    // Trigger load event
    const loadEvent = new Event('load');
    image.dispatchEvent(loadEvent);
    
    // Now Cropper should be instantiated
    expect(MockedCropper).toHaveBeenCalledWith(image, expect.any(Object));
  });

  it('should handle form submission with cropper data', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x" value="">
        <input type="hidden" id="crop_y" value="">
        <input type="hidden" id="crop_size" value="">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    Object.defineProperty(image, 'complete', { value: true });
    
    // Mock cropper data
    const mockData = {
      x: 10.7,
      y: 20.3,
      width: 100.9,
      height: 95.1
    };
    mockCropper.getData.mockReturnValue(mockData);
    
    initPhotoCrop();
    
    const form = document.getElementById('crop-form');
    const cropXInput = document.getElementById('crop_x');
    const cropYInput = document.getElementById('crop_y');
    const cropSizeInput = document.getElementById('crop_size');
    
    // Trigger form submit event
    const submitEvent = new Event('submit');
    form.dispatchEvent(submitEvent);
    
    // Verify cropper.getData was called with true (integers)
    expect(mockCropper.getData).toHaveBeenCalledWith(true);
    
    // Verify form inputs were populated with rounded values
    expect(cropXInput.value).toBe('11'); // Math.max(0, Math.round(10.7))
    expect(cropYInput.value).toBe('20'); // Math.max(0, Math.round(20.3))
    expect(cropSizeInput.value).toBe('95'); // Math.max(1, Math.round(Math.min(100.9, 95.1)))
  });

  it('should handle form submission with negative crop coordinates', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x" value="">
        <input type="hidden" id="crop_y" value="">
        <input type="hidden" id="crop_size" value="">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    Object.defineProperty(image, 'complete', { value: true });
    
    // Mock cropper data with negative values
    const mockData = {
      x: -5.2,
      y: -10.8,
      width: 50.6,
      height: 60.3
    };
    mockCropper.getData.mockReturnValue(mockData);
    
    initPhotoCrop();
    
    const form = document.getElementById('crop-form');
    const cropXInput = document.getElementById('crop_x');
    const cropYInput = document.getElementById('crop_y');
    const cropSizeInput = document.getElementById('crop_size');
    
    // Trigger form submit event
    const submitEvent = new Event('submit');
    form.dispatchEvent(submitEvent);
    
    // Verify negative values are clamped to 0/1
    expect(cropXInput.value).toBe('0'); // Math.max(0, Math.round(-5.2))
    expect(cropYInput.value).toBe('0'); // Math.max(0, Math.round(-10.8))
    expect(cropSizeInput.value).toBe('51'); // Math.max(1, Math.round(Math.min(50.6, 60.3)))
  });

  it('should handle form submission when cropper is not initialized', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x" value="default_x">
        <input type="hidden" id="crop_y" value="default_y">
        <input type="hidden" id="crop_size" value="default_size">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    Object.defineProperty(image, 'complete', { value: false });
    
    initPhotoCrop();
    
    const form = document.getElementById('crop-form');
    const cropXInput = document.getElementById('crop_x');
    const cropYInput = document.getElementById('crop_y');
    const cropSizeInput = document.getElementById('crop_size');
    
    // Trigger form submit event before cropper is initialized
    const submitEvent = new Event('submit');
    form.dispatchEvent(submitEvent);
    
    // Verify cropper.getData was not called
    expect(mockCropper.getData).not.toHaveBeenCalled();
    
    // Verify form inputs retain their default values
    expect(cropXInput.value).toBe('default_x');
    expect(cropYInput.value).toBe('default_y');
    expect(cropSizeInput.value).toBe('default_size');
  });

  it('should handle zero or very small crop sizes', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x" value="">
        <input type="hidden" id="crop_y" value="">
        <input type="hidden" id="crop_size" value="">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    Object.defineProperty(image, 'complete', { value: true });
    
    // Mock cropper data with very small dimensions
    const mockData = {
      x: 5,
      y: 10,
      width: 0.3,
      height: 0.7
    };
    mockCropper.getData.mockReturnValue(mockData);
    
    initPhotoCrop();
    
    const form = document.getElementById('crop-form');
    const cropSizeInput = document.getElementById('crop_size');
    
    // Trigger form submit event
    const submitEvent = new Event('submit');
    form.dispatchEvent(submitEvent);
    
    // Verify crop size is clamped to minimum of 1
    expect(cropSizeInput.value).toBe('1'); // Math.max(1, Math.round(Math.min(0.3, 0.7)))
  });

  it('should use minimum of width and height for square crop', () => {
    document.body.innerHTML = `
      <img id="crop-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Test">
      <form id="crop-form">
        <input type="hidden" id="crop_x" value="">
        <input type="hidden" id="crop_y" value="">
        <input type="hidden" id="crop_size" value="">
      </form>
    `;
    
    const image = document.getElementById('crop-image');
    Object.defineProperty(image, 'complete', { value: true });
    
    // Mock cropper data where width > height
    const mockData = {
      x: 0,
      y: 0,
      width: 150,
      height: 120
    };
    mockCropper.getData.mockReturnValue(mockData);
    
    initPhotoCrop();
    
    const form = document.getElementById('crop-form');
    const cropSizeInput = document.getElementById('crop_size');
    
    // Trigger form submit event
    const submitEvent = new Event('submit');
    form.dispatchEvent(submitEvent);
    
    // Verify crop size uses the smaller dimension (height)
    expect(cropSizeInput.value).toBe('120'); // Math.min(150, 120)
  });
});