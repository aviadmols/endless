import Alpine from 'alpinejs';

/**
 * Multi-file picker for photos and videos.
 *
 * The picker itself is opened by a native <label for>, never by a scripted
 * click — a scripted click that bubbles back to its own trigger re-opens the
 * phone's photo picker mid-selection, which made choosing a photo look like it
 * did nothing at all.
 *
 * Files are kept in a local array and written back to the input through a
 * DataTransfer so removal works; when DataTransfer is unavailable the browser's
 * own selection is left untouched and removal is hidden.
 */
const canRewriteFiles = (() => {
    try {
        const dt = new DataTransfer();

        return typeof dt.items?.add === 'function';
    } catch {
        return false;
    }
})();

const IMAGE_MB = 8;
const VIDEO_MB = 60;
const TOTAL_MB = 70; // stays under the server's post_max_size

Alpine.data('uploader', (options = {}) => ({
    max: options.max ?? 10,
    videos: options.videos ?? false,
    files: [],
    previews: [],
    error: '',
    dragging: false,
    canRemove: canRewriteFiles,

    onDrop(event) {
        this.dragging = false;
        this.add(event.dataTransfer?.files);
    },

    add(fileList) {
        const incoming = Array.from(fileList || []);
        if (! incoming.length) {
            return;
        }

        this.error = '';

        for (const file of incoming) {
            const problem = this.reject(file);
            if (problem) {
                this.error = problem;
                continue;
            }
            this.files.push(file);
            this.previews.push({
                key: `${file.name}-${file.size}-${file.lastModified}-${this.previews.length}`,
                name: file.name,
                url: URL.createObjectURL(file),
                isVideo: this.isVideo(file),
            });
        }

        this.sync();
    },

    /** Returns a message when the file cannot be accepted, or an empty string. */
    reject(file) {
        const name = file.name || 'הקובץ';

        if (this.duplicate(file)) {
            return '';
        }
        if (/\.(heic|heif)$/i.test(name) || /heic|heif/i.test(file.type)) {
            return `"${name}" בפורמט HEIC. בהגדרות האייפון: מצלמה → פורמטים → "הכי תואם", או שלחו את התמונה כ-JPG.`;
        }
        if (this.files.length >= this.max) {
            return `ניתן להעלות עד ${this.max} קבצים.`;
        }

        const isVideo = this.isVideo(file);
        if (isVideo && ! this.videos) {
            return 'כאן אפשר להעלות תמונות בלבד.';
        }

        const limit = isVideo ? VIDEO_MB : IMAGE_MB;
        if (file.size > limit * 1024 * 1024) {
            return `"${name}" גדול מ-${limit}MB.`;
        }

        const total = this.files.reduce((sum, f) => sum + f.size, 0) + file.size;
        if (total > TOTAL_MB * 1024 * 1024) {
            return `סך הקבצים גדול מ-${TOTAL_MB}MB. הסירו קובץ ונסו שוב.`;
        }

        return '';
    },

    duplicate(file) {
        return this.files.some((f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
    },

    isVideo(file) {
        return (file.type || '').startsWith('video/') || /\.(mp4|webm|mov|m4v)$/i.test(file.name || '');
    },

    remove(index) {
        URL.revokeObjectURL(this.previews[index]?.url);
        this.files.splice(index, 1);
        this.previews.splice(index, 1);
        this.error = '';
        this.sync();
    },

    sync() {
        if (! canRewriteFiles) {
            return;
        }
        try {
            const dt = new DataTransfer();
            this.files.forEach((f) => dt.items.add(f));
            this.$refs.input.files = dt.files;
        } catch {
            // Leave whatever the browser selected; the form still submits it.
        }
    },
}));
