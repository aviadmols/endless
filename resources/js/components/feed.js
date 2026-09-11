import Alpine from 'alpinejs';
import { observeReveals } from './reveal';
import { appendToMasonry } from './masonry';

/** Memories feed: expandable cards + "load more" that appends server-rendered HTML. */
Alpine.data('feed', (options = {}) => ({
    endpoint: options.endpoint,
    page: options.page ?? 2,
    hasMore: options.hasMore ?? false,
    loading: false,
    error: '',

    async loadMore() {
        if (this.loading || !this.hasMore) return;
        this.loading = true;
        this.error = '';

        try {
            const url = new URL(this.endpoint, window.location.origin);
            url.searchParams.set('page', this.page);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('network');
            const data = await response.json();

            const holder = document.createElement('div');
            holder.innerHTML = data.html;
            const nodes = Array.from(holder.children);
            appendToMasonry(this.$refs.list, nodes);
            observeReveals(this.$refs.list);

            this.page = data.nextPage;
            this.hasMore = data.hasMore;
        } catch (e) {
            this.error = 'לא הצלחנו לטעון עוד זיכרונות. נסו שוב.';
        } finally {
            this.loading = false;
        }
    },
}));

// Expand / collapse a feed card (mobile feed style).
document.addEventListener('click', (event) => {
    const card = event.target.closest('[data-expandable]');
    if (!card || event.target.closest('a, button')) return;
    card.classList.toggle('is-open');
});
