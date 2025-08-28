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
            skel.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden block';
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
            a.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden block';
            const img = document.createElement('img');
            img.src = item.thumbnail_url;
            img.alt = item.caption || 'Photo';
            img.className = 'w-full aspect-square object-cover';
            a.appendChild(img);
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
