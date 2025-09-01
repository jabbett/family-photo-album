import { describe, it, expect, beforeEach, vi } from 'vitest';
import { initPhotoAlbum } from '../../resources/js/photo-album.js';

// Mock fetch globally
global.fetch = vi.fn();

// Mock IntersectionObserver
global.IntersectionObserver = vi.fn(() => ({
  observe: vi.fn(),
  disconnect: vi.fn(),
  unobserve: vi.fn(),
}));

describe('Photo Album', () => {
  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';
    
    // Reset all mocks
    vi.clearAllMocks();
    
    // Setup default DOM elements
    const grid = document.createElement('div');
    grid.id = 'photo-grid';
    document.body.appendChild(grid);
    
    const sentinel = document.createElement('div');
    sentinel.id = 'infinite-scroll-sentinel';
    document.body.appendChild(sentinel);
    
    const loadMoreBtn = document.createElement('button');
    loadMoreBtn.id = 'load-more-button';
    loadMoreBtn.innerHTML = '<span>Load more</span>';
    document.body.appendChild(loadMoreBtn);
  });

  it('should initialize with required DOM elements', () => {
    const result = initPhotoAlbum({
      nextPage: 2,
      perPage: 10,
      feedUrl: '/test/feed'
    });
    
    // Should not return anything but should not throw
    expect(result).toBeUndefined();
    
    // Grid should be accessible
    const grid = document.getElementById('photo-grid');
    expect(grid).toBeTruthy();
  });

  it('should handle missing DOM elements gracefully', () => {
    // Remove required elements
    document.getElementById('photo-grid').remove();
    
    const result = initPhotoAlbum({
      nextPage: 2
    });
    
    // Should return early without error
    expect(result).toBeUndefined();
  });

  it('should not initialize when nextPage is null', () => {
    const result = initPhotoAlbum({
      nextPage: null
    });
    
    // Should return early
    expect(result).toBeUndefined();
  });

  it('should use default options when not provided', () => {
    const result = initPhotoAlbum({
      nextPage: 1
    });
    
    // Should initialize successfully with defaults
    expect(result).toBeUndefined();
  });

  it('should setup IntersectionObserver when supported', () => {
    initPhotoAlbum({
      nextPage: 2
    });

    const sentinel = document.getElementById('infinite-scroll-sentinel');
    const loadMoreBtn = document.getElementById('load-more-button');
    
    // Should create IntersectionObserver
    expect(IntersectionObserver).toHaveBeenCalled();
    
    // Should hide load more button
    expect(loadMoreBtn.style.display).toBe('none');
  });

  it('should fallback to button when IntersectionObserver not supported', () => {
    // Mock IntersectionObserver as not available
    const originalIO = global.IntersectionObserver;
    delete global.IntersectionObserver;
    
    initPhotoAlbum({
      nextPage: 2
    });
    
    const sentinel = document.getElementById('infinite-scroll-sentinel');
    const loadMoreBtn = document.getElementById('load-more-button');
    
    // Sentinel should be removed, button should have listener
    expect(document.getElementById('infinite-scroll-sentinel')).toBeFalsy();
    expect(loadMoreBtn).toBeTruthy();
    
    // Restore IntersectionObserver
    global.IntersectionObserver = originalIO;
  });

  it('should load photos when intersection occurs', async () => {
    const mockData = {
      data: [
        {
          id: 1,
          url: '/photo/1',
          thumbnail_url: '/thumb/1.jpg',
          caption: 'Test Photo 1'
        }
      ],
      nextPage: 3
    };

    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockData)
    });

    // Mock IntersectionObserver to immediately trigger intersection
    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        // Simulate intersection immediately
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    initPhotoAlbum({
      nextPage: 2,
      perPage: 10,
      feedUrl: '/api/photos'
    });

    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 10));

    expect(fetch).toHaveBeenCalledWith(
      '/api/photos?page=2&per_page=10',
      { headers: { 'Accept': 'application/json' } }
    );

    const grid = document.getElementById('photo-grid');
    const addedPhoto = grid.querySelector('[data-photo-id="1"]');
    expect(addedPhoto).toBeTruthy();
    expect(addedPhoto.href).toBe('http://localhost:3000/photo/1');
  });

  it('should handle fetch errors gracefully', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    
    fetch.mockRejectedValueOnce(new Error('Network error'));

    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    initPhotoAlbum({
      nextPage: 2
    });

    await new Promise(resolve => setTimeout(resolve, 10));

    expect(consoleSpy).toHaveBeenCalledWith(
      'Failed to load more photos:',
      expect.any(Error)
    );

    consoleSpy.mockRestore();
  });

  it('should handle non-ok fetch responses', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    
    fetch.mockResolvedValueOnce({
      ok: false,
      status: 404
    });

    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    initPhotoAlbum({
      nextPage: 2
    });

    await new Promise(resolve => setTimeout(resolve, 10));

    expect(consoleSpy).toHaveBeenCalledWith(
      'Failed to load more photos:',
      expect.any(Error)
    );

    consoleSpy.mockRestore();
  });

  it('should remove sentinel and button when no more pages', async () => {
    const mockData = {
      data: [],
      nextPage: null
    };

    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockData)
    });

    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    initPhotoAlbum({
      nextPage: 2
    });

    await new Promise(resolve => setTimeout(resolve, 10));

    expect(document.getElementById('infinite-scroll-sentinel')).toBeFalsy();
    expect(document.getElementById('load-more-button')).toBeFalsy();
  });

  it('should create skeleton elements while loading', async () => {
    // Setup a delayed response
    fetch.mockImplementationOnce(() => 
      new Promise(resolve => {
        setTimeout(() => {
          resolve({
            ok: true,
            json: () => Promise.resolve({ data: [], nextPage: null })
          });
        }, 50);
      })
    );

    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    initPhotoAlbum({
      nextPage: 2
    });

    // Wait a bit for skeleton to show
    await new Promise(resolve => setTimeout(resolve, 10));

    const grid = document.getElementById('photo-grid');
    expect(grid.getAttribute('aria-busy')).toBe('true');
    
    const skeletons = grid.querySelectorAll('[data-skeleton]');
    expect(skeletons.length).toBe(6);

    // Wait for completion
    await new Promise(resolve => setTimeout(resolve, 60));

    // Skeletons should be removed
    const remainingSkeletons = grid.querySelectorAll('[data-skeleton]');
    expect(remainingSkeletons.length).toBe(0);
    expect(grid.hasAttribute('aria-busy')).toBe(false);
  });

  it('should handle photos without captions', async () => {
    const mockData = {
      data: [
        {
          id: 1,
          url: '/photo/1',
          thumbnail_url: '/thumb/1.jpg'
          // No caption
        }
      ],
      nextPage: null
    };

    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockData)
    });

    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    initPhotoAlbum({
      nextPage: 2
    });

    await new Promise(resolve => setTimeout(resolve, 10));

    const grid = document.getElementById('photo-grid');
    const img = grid.querySelector('img');
    expect(img.alt).toBe('Photo');
  });

  it('should prevent loading when already loading', async () => {
    // Setup multiple intersection events
    const mockIO = vi.fn((callback) => ({
      observe: vi.fn(() => {
        // Trigger multiple intersections rapidly
        setTimeout(() => {
          callback([{ isIntersecting: true }]);
          callback([{ isIntersecting: true }]);
          callback([{ isIntersecting: true }]);
        }, 0);
      }),
      disconnect: vi.fn(),
      unobserve: vi.fn(),
    }));
    global.IntersectionObserver = mockIO;

    fetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ data: [], nextPage: null })
    });

    initPhotoAlbum({
      nextPage: 2
    });

    await new Promise(resolve => setTimeout(resolve, 10));

    // Should only call fetch once despite multiple intersections
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});