import Alpine from 'alpinejs';
import QRCode from 'qrcode';

/** Copy-to-clipboard field with feedback. */
Alpine.data('copyField', (value = '') => ({
    value,
    copied: false,

    async copy() {
        try {
            await navigator.clipboard.writeText(this.value);
        } catch (e) {
            const input = this.$refs.input;
            input.removeAttribute('readonly');
            input.select();
            document.execCommand('copy');
            input.setAttribute('readonly', 'readonly');
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 2200);
    },
}));

/** Renders a QR code for a link into a <canvas x-data="qr(url)" x-ref="canvas">. */
Alpine.data('qr', (value = '') => ({
    init() {
        QRCode.toCanvas(this.$el, value, { width: 380, margin: 1, color: { dark: '#1D1D20', light: '#FFFFFF' } });
    },
}));
