import { defineConfig } from 'vitest/config';
import { resolve } from 'path';

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/js/setup.js'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      reportsDirectory: './reports/coverage/js',
      include: ['resources/js/**/*.js'],
      exclude: [
        'node_modules/',
        'tests/',
        'vendor/',
        'bootstrap/',
        'config/',
        'database/',
        'public/',
        'storage/',
      ],
      thresholds: {
        global: {
          branches: 50,
          functions: 50,
          lines: 50,
          statements: 50
        }
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
    }
  }
});