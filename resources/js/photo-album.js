// Photo Album - Infinite Scroll functionality
export function initPhotoAlbum(options = {}) {
    const {
        nextPage: initialNextPage,
        perPage = 20,
        feedUrl = '/photos/feed'
    } = options;

    let nextPage = initialNextPage;
    const grid = document.getElementById('photo-grid');
    const sentinel = document.getElementById('infinite-scroll-sentinel');
    const loadMoreBtn = document.getElementById('load-more-button');
    
    if (!grid || !sentinel || nextPage === null) return;

    let isLoading = false;

    function showSkeleton(count) {
        grid.setAttribute('aria-busy', 'true');
        for (let i = 0; i < count; i++) {
            const skel = document.createElement('div');
            skel.setAttribute('data-skeleton', '');
            skel.className = 'block overflow-hidden sm:bg-white sm:rounded-lg sm:shadow-sm sm:border sm:border-gray-200';
            const inner = document.createElement('div');
            inner.className = 'w-full aspect-square bg-gray-100 animate-pulse';
            skel.appendChild(inner);
            grid.appendChild(skel);
        }
    }

    function hideSkeleton() {
        grid.removeAttribute('aria-busy');
        const nodes = grid.querySelectorAll('[data-skeleton]');
        nodes.forEach(n => n.parentNode?.removeChild(n));
    }

    function renderItems(items) {
        items.forEach(item => {
            const a = document.createElement('a');
            a.setAttribute('data-photo-id', item.id);
            a.href = item.url;
            a.className = 'block overflow-hidden sm:bg-white sm:rounded-lg sm:shadow-sm sm:border sm:border-gray-200 relative';

            const img = document.createElement('img');
            img.src = item.thumbnail_url;
            img.alt = item.caption || 'Photo';
            img.loading = 'lazy';
            img.className = 'w-full aspect-square object-cover';
            a.appendChild(img);

            // Add layers icon for multi-photo posts
            if (item.is_collection) {
                const iconContainer = document.createElement('div');
                iconContainer.className = 'absolute top-2 right-2';

                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('class', 'w-5 h-5 text-white');
                svg.setAttribute('style', 'filter: drop-shadow(0 1px 2px rgb(0 0 0 / 0.3));');
                svg.setAttribute('fill', 'none');
                svg.setAttribute('viewBox', '0 0 24 24');
                svg.setAttribute('stroke', 'currentColor');
                svg.setAttribute('stroke-width', '2');
                svg.setAttribute('stroke-linecap', 'round');
                svg.setAttribute('stroke-linejoin', 'round');

                // Layers icon path (Lucide icon)
                const path1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path1.setAttribute('d', 'M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z');

                const path2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path2.setAttribute('d', 'm6.08 9.5-3.5 1.6a1 1 0 0 0 0 1.81l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9a1 1 0 0 0 0-1.83l-3.5-1.59');

                const path3 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path3.setAttribute('d', 'm6.08 14.5-3.5 1.6a1 1 0 0 0 0 1.81l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9a1 1 0 0 0 0-1.83l-3.5-1.59');

                svg.appendChild(path1);
                svg.appendChild(path2);
                svg.appendChild(path3);
                iconContainer.appendChild(svg);
                a.appendChild(iconContainer);
            }

            grid.appendChild(a);
        });
    }

    async function loadNext() {
        if (isLoading || nextPage === null) return;
        isLoading = true;
        
        if (loadMoreBtn) {
            loadMoreBtn.disabled = true;
            loadMoreBtn.classList.add('opacity-60', 'cursor-not-allowed');
            loadMoreBtn.querySelector('span').textContent = 'Loading…';
        }
        
        showSkeleton(6);
        
        try {
            const url = `${feedUrl}?page=${nextPage}&per_page=${perPage}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Network error');
            
            const data = await res.json();
            renderItems(data.data || []);
            nextPage = data.nextPage;
            
            if (nextPage === null) {
                sentinel?.parentNode?.removeChild(sentinel);
                loadMoreBtn?.parentNode?.removeChild(loadMoreBtn);
            }
        } catch (e) {
            console.error('Failed to load more photos:', e);
        } finally {
            hideSkeleton();
            isLoading = false;
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
                loadMoreBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                loadMoreBtn.querySelector('span').textContent = 'Load more';
            }
        }
    }

    // Auto-loading with Intersection Observer or fallback to button
    if ('IntersectionObserver' in window) {
        loadMoreBtn?.style.setProperty('display', 'none');
        const io = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadNext();
                }
            });
        });
        io.observe(sentinel);
    } else {
        sentinel?.parentNode?.removeChild(sentinel);
        loadMoreBtn?.addEventListener('click', loadNext);
    }
}
