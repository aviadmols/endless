import Alpine from 'alpinejs';

/**
 * Multi-image picker with previews, drag & drop, per-file removal and re-ordering.
 * Keeps a real FileList in sync with the <input type="file"> through DataTransfer.
 */
Alpine.data('uploader', (options = {}) => ({
    max: options.max ?? 10,
    maxSizeMb: options.maxSizeMb ?? 8,
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
        this.add(Array.from(event.dataTransfer?.files || []).filter((f) => f.type.startsWith('image/')));
    },

    add(incoming) {
        this.error = '';
        incoming.forEach((file) => {
            if (this.files.length >= this.max) {
                this.error = `ניתן להעלות עד ${this.max} תמונות.`;
                return;
            }
            if (file.size > this.maxSizeMb * 1024 * 1024) {
                this.error = `הקובץ "${file.name}" גדול מ-${this.maxSizeMb}MB.`;
                return;
            }
            this.files.push(file);
            this.previews.push({ name: file.name, url: URL.createObjectURL(file) });
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
