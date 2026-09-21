import jsQR from 'jsqr';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-qr-scanner]');

    if (!root) {
        return;
    }

    const startButton = root.querySelector('[data-scan-start]');
    const cancelButton = root.querySelector('[data-scan-cancel]');
    const retryButton = root.querySelector('[data-scan-retry]');
    const video = root.querySelector('[data-scan-video]');
    const statusText = root.querySelector('[data-scan-status]');
    const errorBox = root.querySelector('[data-scan-error]');
    const insecureBox = root.querySelector('[data-scan-insecure]');
    const cameraBox = root.querySelector('[data-scan-camera]');
    const loadingBox = root.querySelector('[data-scan-loading]');
    const scanForm = root.querySelector('[data-scan-form]');
    const tokenInput = root.querySelector('[data-scan-token-input]');

    let activeStream = null;
    let scanLoop = null;
    let scanning = false;
    let submitted = false;

    const stopCamera = () => {
        scanning = false;

        if (scanLoop) {
            cancelAnimationFrame(scanLoop);
            scanLoop = null;
        }

        if (activeStream) {
            activeStream.getTracks().forEach((track) => track.stop());
            activeStream = null;
        }

        if (video) {
            video.srcObject = null;
        }

        cameraBox?.classList.add('hidden');
    };

    const showError = (message) => {
        if (errorBox) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }
    };

    const hideError = () => {
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }
    };

    const extractToken = (payload) => {
        const value = String(payload).trim();

        if (!value) {
            return null;
        }

        try {
            const url = new URL(value, window.location.origin);
            const segments = url.pathname.split('/').filter(Boolean);
            const scanIndex = segments.findIndex((segment) => segment === 'scan');

            if (scanIndex !== -1 && scanIndex === segments.length - 2) {
                const token = decodeURIComponent(segments[segments.length - 1]);

                return token !== '' ? token : null;
            }
        } catch {
            // Not a valid URL; treat as unrecognized.
        }

        return null;
    };

    const handleDecoded = (payload) => {
        stopCamera();

        const token = extractToken(payload);

        if (!token) {
            showError('QR tidak dikenali. Pindai QR Absensi yang ditampilkan admin / kepala.');

            if (retryButton) {
                retryButton.classList.remove('hidden');
            }

            return;
        }

        if (!scanForm || !tokenInput) {
            return;
        }

        submitted = true;
        tokenInput.value = token;

        if (loadingBox) {
            loadingBox.classList.remove('hidden');
        }

        scanForm.submit();
    };

    const tick = () => {
        if (!scanning) {
            return;
        }

        if (video && video.readyState === video.HAVE_ENOUGH_DATA) {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const context = canvas.getContext('2d', { willReadFrequently: true });

            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const qr = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
            });

            if (qr && qr.data) {
                if (statusText) {
                    statusText.textContent = 'QR terdeteksi. Memproses absensi...';
                }

                handleDecoded(qr.data);

                return;
            }
        }

        scanLoop = requestAnimationFrame(tick);
    };

    const startCamera = async () => {
        hideError();
        insecureBox?.classList.add('hidden');

        if (window.isSecureContext === false) {
            insecureBox?.classList.remove('hidden');

            return;
        }

        if (scanning || (activeStream !== null && activeStream.active)) {
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Kamera tidak didukung pada perangkat ini. Gunakan menu input manual di bawah.');

            return;
        }

        retryButton?.classList.add('hidden');

        if (statusText) {
            statusText.textContent = 'Arahkan kamera ke QR Absensi...';
        }

        cameraBox?.classList.remove('hidden');

        try {
            activeStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            });
        } catch (error) {
            cameraBox?.classList.add('hidden');

            if (error instanceof DOMException && error.name === 'NotAllowedError') {
                showError('Akses kamera ditolak. Izinkan akses kamera pada browser lalu coba lagi.');
            } else if (error instanceof DOMException && error.name === 'NotFoundError') {
                showError('Kamera tidak ditemukan pada perangkat ini.');
            } else if (error instanceof DOMException && error.name === 'NotReadableError') {
                showError('Kamera tidak dapat digunakan. Tutup aplikasi lain yang memakai kamera lalu coba lagi.');
            } else {
                showError('Tidak dapat membuka kamera. Gunakan menu input manual di bawah.');
            }

            return;
        }

        if (video) {
            video.srcObject = activeStream;
            await video.play().catch(() => {});
        }

        scanning = true;
        scanLoop = requestAnimationFrame(tick);
    };

    startButton.addEventListener('click', startCamera);

    cancelButton.addEventListener('click', () => {
        stopCamera();
        hideError();
        retryButton?.classList.add('hidden');
    });

    retryButton.addEventListener('click', startCamera);

    scanForm.addEventListener('submit', () => {
        if (submitted) {
            return;
        }

        stopCamera();

        if (loadingBox) {
            loadingBox.classList.remove('hidden');
        }
    });

    window.addEventListener('pagehide', () => {
        if (!submitted) {
            stopCamera();
        }
    });
});