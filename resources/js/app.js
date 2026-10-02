import './bootstrap';

// Makes the app installable and keeps the shell available on weak Wi-Fi.
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// Shrinks a phone photo in the browser before upload, so it's fast on mobile data.
async function shrinkImage(file, maxSide = 1600) {
    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        return blob ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        return file; // Unsupported format in this browser: let the server handle it.
    }
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('photoPicker', ($wire) => ({
        preview: null,
        uploading: false,
        progress: 0,
        clear() {
            this.preview = null;
            this.$refs.file.value = '';
        },
        async pick(event) {
            const original = event.target.files[0];
            if (!original) return;
            const file = await shrinkImage(original);
            this.preview = URL.createObjectURL(file);
            this.uploading = true;
            this.progress = 0;
            $wire.upload('photo', file,
                () => { this.uploading = false; },
                () => { this.uploading = false; this.preview = null; },
                (e) => { this.progress = e.detail.progress; },
            );
        },
    }));
});
