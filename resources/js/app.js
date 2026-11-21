import Alpine from 'alpinejs';
import './echo';

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

Alpine.data('topbarNotifications', (role) => ({
    open: false,
    items: [],
    unreadCount: 0,

    init() {
        if (!window.Echo || !role) {
            return;
        }

        window.Echo.private(`notifications.${role}`)
            .listen('.order.status.changed', (payload) => this.push(payload))
            .listen('.pelunasan.completed', (payload) => this.push(payload))
            .listen('.serah-terima.completed', (payload) => this.push(payload));
    },

    push(payload) {
        this.items.unshift({
            id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            message: payload?.message ?? 'Ada pembaruan baru.',
            time: new Date().toLocaleString('id-ID'),
            read: false,
        });
        this.items = this.items.slice(0, 10);
        this.unreadCount = Math.min(this.unreadCount + 1, 99);
    },

    markAllRead() {
        this.items = this.items.map((item) => ({ ...item, read: true }));
        this.unreadCount = 0;
    },
}));

Alpine.start();
