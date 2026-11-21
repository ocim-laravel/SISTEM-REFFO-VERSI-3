import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('imagePreview', () => ({
    url: null,
    name: '',
    isImage: false,
    failed: false,

    pick(event) {
        this.reset();

        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'tif', 'tiff'];

        this.name = file.name;
        this.isImage = file.type.startsWith('image/') || imageExtensions.includes(ext);

        if (!this.isImage) {
            return;
        }

        this.url = URL.createObjectURL(file);
    },

    clear() {
        this.reset();

        if (this.$refs.file) {
            this.$refs.file.value = '';
        }
    },

    reset() {
        if (this.url) {
            URL.revokeObjectURL(this.url);
        }

        this.url = null;
        this.name = '';
        this.isImage = false;
        this.failed = false;
    },

    destroy() {
        this.reset();
    },
}));

Alpine.start();
