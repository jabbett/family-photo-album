// Global test setup for Vitest
import { vi } from 'vitest';

// Mock global objects that might be used in the frontend code
global.window = window;
global.document = document;

// Mock any Laravel-specific globals
global.route = vi.fn();
global.axios = vi.fn();

// Setup DOM environment
Object.defineProperty(window, 'location', {
  value: {
    href: 'http://localhost',
    origin: 'http://localhost',
    pathname: '/',
    search: '',
    hash: ''
  },
  writable: true
});