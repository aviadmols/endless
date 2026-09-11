import Alpine from 'alpinejs';

/**
 * Multi-file picker for photos and videos with previews, drag & drop and removal.
 * Keeps a real FileList in sync with the <input type="file"> through DataTransfer.
 */
Alpine.data('uploader', (options = {}) => ({
    max: options.max ?? 10,
    videos: options.videos ?? false,
    maxImageMb: options.maxImageMb ?? 8,
    maxVideoMb: options.maxVideoMb ?? 60,
    files: [],
    previews: [],
    error: '',
    dragging: false,

    init() {
        this.$refs.input.addEventListener('change', (e) => this.add(Array.from(e.target.files || [])));
    },

    pick() {
        this.$refs.input.click();
    },

    onDrop(event) {
        this.dragging = false;
        const dropped = Array.from(event.dataTransfer?.files || []).filter(
            (f) => f.type.startsWith('image/') || (this.videos && f.type.startsWith('video/'))
        );
        this.add(dropped);
    },

    add(incoming) {
        this.error = '';
        incoming.forEach((file) => {
            const isVideo = file.type.startsWith('video/');

            if (isVideo && !this.videos) {
                this.error = 'כאן אפשר להעלות תמונות בלבד.';
                return;
            }
            if (this.files.length >= this.max) {
                this.error = `ניתן להעלות עד ${this.max} קבצים.`;
                return;
            }

            const limitMb = isVideo ? this.maxVideoMb : this.maxImageMb;
            if (file.size > limitMb * 1024 * 1024) {
                this.error = `הקובץ "${file.name}" גדול מ-${limitMb}MB.`;
                return;
            }

            this.files.push(file);
            this.previews.push({ name: file.name, url: URL.createObjectURL(file), isVideo });
        });
        this.sync();
    },

    remove(index) {
        URL.revokeObjectURL(this.previews[index]?.url);
        this.files.splice(index, 1);
        this.previews.splice(index, 1);
        this.error = '';
        this.sync();
    },

    move(index, delta) {
        const target = index + delta;
        if (target < 0 || target >= this.files.length) return;
        [this.files[index], this.files[target]] = [this.files[target], this.files[index]];
        [this.previews[index], this.previews[target]] = [this.previews[target], this.previews[index]];
        this.sync();
    },

    sync() {
        const dt = new DataTransfer();
        this.files.forEach((f) => dt.items.add(f));
        this.$refs.input.files = dt.files;
    },
}));
