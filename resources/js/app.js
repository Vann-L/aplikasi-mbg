import './scanner';

document.addEventListener('DOMContentLoaded', () => {
    const fotoInput = document.querySelector('[data-foto-input]');
    const fotoPreview = document.querySelector('[data-foto-preview]');

    if (fotoInput && fotoPreview) {
        fotoInput.addEventListener('change', () => {
            const file = fotoInput.files?.[0];

            if (! file) {
                return;
            }

            fotoPreview.src = URL.createObjectURL(file);
            fotoPreview.classList.remove('hidden');
        });
    }

    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');

    if (! sidebar) {
        return;
    }

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    };

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            if (sidebar.classList.contains('-translate-x-full')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });
    });

    overlay?.addEventListener('click', closeSidebar);
});
