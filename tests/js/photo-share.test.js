import { describe, it, expect, beforeEach, vi } from 'vitest';
import { initPhotoShare } from '../../resources/js/photo-share.js';

describe('Photo Share', () => {
  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';
    
    // Reset all mocks
    vi.restoreAllMocks();
  });

  it('should return early when shareButton element is missing', () => {
    const result = initPhotoShare();
    expect(result).toBeUndefined();
  });

  it('should initialize successfully with shareButton present', () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    const result = initPhotoShare();
    expect(result).toBeUndefined(); // Function doesn't return anything
    
    // Verify button exists
    const btn = document.getElementById('shareButton');
    expect(btn).toBeTruthy();
  });

  it('should use native share API when available', async () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    // Mock native share API
    const mockShare = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'share', {
      value: mockShare,
      writable: true,
    });
    
    // Mock document.title and window.location
    Object.defineProperty(document, 'title', {
      value: 'Test Photo Title',
      writable: true,
    });
    Object.defineProperty(window, 'location', {
      value: { href: 'https://example.com/photo/123' },
      writable: true,
    });
    
    // Initialize share functionality
    initPhotoShare();
    
    const btn = document.getElementById('shareButton');
    
    // Trigger click
    await btn.click();
    
    // Verify native share was called with correct data
    expect(mockShare).toHaveBeenCalledWith({
      title: 'Test Photo Title',
      url: 'https://example.com/photo/123'
    });
  });

  it('should fall back to clipboard API when native share is not available', async () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    // Ensure navigator.share is not available
    Object.defineProperty(navigator, 'share', {
      value: undefined,
      writable: true,
    });
    
    // Mock clipboard API
    const mockWriteText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: mockWriteText },
      writable: true,
    });
    
    // Mock alert
    const mockAlert = vi.fn();
    global.alert = mockAlert;
    
    // Mock window.location
    Object.defineProperty(window, 'location', {
      value: { href: 'https://example.com/photo/456' },
      writable: true,
    });
    
    // Initialize share functionality
    initPhotoShare();
    
    const btn = document.getElementById('shareButton');
    
    // Trigger click event and wait for completion
    const clickEvent = new Event('click');
    btn.dispatchEvent(clickEvent);
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // Verify clipboard was used
    expect(mockWriteText).toHaveBeenCalledWith('https://example.com/photo/456');
    expect(mockAlert).toHaveBeenCalledWith('Link copied to clipboard');
  });

  it('should fall back to prompt when neither native share nor clipboard are available', async () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    // No native share or clipboard API
    Object.defineProperty(navigator, 'share', { value: undefined });
    Object.defineProperty(navigator, 'clipboard', { value: undefined });
    
    // Mock prompt
    const mockPrompt = vi.fn().mockReturnValue('copied');
    global.prompt = mockPrompt;
    
    // Mock window.location
    Object.defineProperty(window, 'location', {
      value: { href: 'https://example.com/photo/789' },
      writable: true,
    });
    
    // Initialize share functionality
    initPhotoShare();
    
    const btn = document.getElementById('shareButton');
    
    // Trigger click
    await btn.click();
    
    // Verify prompt was used
    expect(mockPrompt).toHaveBeenCalledWith('Copy this link:', 'https://example.com/photo/789');
  });

  it('should handle native share API errors gracefully', async () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    // Mock native share API that throws an error
    const mockShare = vi.fn().mockRejectedValue(new Error('Share failed'));
    Object.defineProperty(navigator, 'share', {
      value: mockShare,
      writable: true,
    });
    
    // Mock console.error to verify error handling
    const mockConsoleError = vi.fn();
    global.console.error = mockConsoleError;
    
    // Initialize share functionality
    initPhotoShare();
    
    const btn = document.getElementById('shareButton');
    
    // Trigger click and wait for error handling
    await btn.click();
    
    // Verify error was logged
    expect(mockConsoleError).toHaveBeenCalledWith('Share failed:', expect.any(Error));
  });

  it('should handle clipboard API errors gracefully', async () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    // Ensure navigator.share is not available
    Object.defineProperty(navigator, 'share', {
      value: undefined,
      writable: true,
    });
    
    // Mock clipboard API that throws an error
    const mockWriteText = vi.fn().mockRejectedValue(new Error('Clipboard failed'));
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: mockWriteText },
      writable: true,
    });
    
    // Mock console.error
    const mockConsoleError = vi.fn();
    global.console.error = mockConsoleError;
    
    // Initialize share functionality
    initPhotoShare();
    
    const btn = document.getElementById('shareButton');
    
    // Trigger click event
    const clickEvent = new Event('click');
    btn.dispatchEvent(clickEvent);
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 10));
    
    // Verify error was logged
    expect(mockConsoleError).toHaveBeenCalledWith('Share failed:', expect.any(Error));
  });

  it('should handle multiple clicks without issues', async () => {
    document.body.innerHTML = `
      <button id="shareButton">Share Photo</button>
    `;
    
    // Ensure navigator.share is not available
    Object.defineProperty(navigator, 'share', {
      value: undefined,
      writable: true,
    });
    
    // Mock clipboard API
    const mockWriteText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: mockWriteText },
      writable: true,
    });
    
    // Mock alert
    global.alert = vi.fn();
    
    // Initialize share functionality
    initPhotoShare();
    
    const btn = document.getElementById('shareButton');
    
    // Trigger multiple clicks
    const clickEvent1 = new Event('click');
    const clickEvent2 = new Event('click');
    const clickEvent3 = new Event('click');
    
    btn.dispatchEvent(clickEvent1);
    btn.dispatchEvent(clickEvent2);
    btn.dispatchEvent(clickEvent3);
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 10));
    
    // Verify clipboard was called multiple times
    expect(mockWriteText).toHaveBeenCalledTimes(3);
  });
});