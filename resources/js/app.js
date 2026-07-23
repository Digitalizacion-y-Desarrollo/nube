import './bootstrap';

const setSidebarState = (open) => {
    const sidebar = document.querySelector('#mobile-sidebar');

    if (!sidebar) {
        return;
    }

    sidebar.classList.toggle('hidden', !open);
    sidebar.setAttribute('aria-hidden', String(!open));
    document.body.classList.toggle('overflow-hidden', open);
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-open]')) {
        setSidebarState(true);
    }

    if (event.target.closest('[data-sidebar-close]')) {
        setSidebarState(false);
    }

    const modalOpen = event.target.closest('[data-modal-open]');
    if (modalOpen) {
        const modal = document.getElementById(modalOpen.dataset.modalOpen);
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    const modalClose = event.target.closest('[data-modal-close]');
    if (modalClose) {
        const modal = document.getElementById(modalClose.dataset.modalClose);
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    const passwordToggle = event.target.closest('[data-password-toggle]');
    if (passwordToggle) {
        const input = document.querySelector('[data-password-input]');
        if (!input) {
            return;
        }

        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(showPassword));
        passwordToggle.setAttribute('aria-label', showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    setSidebarState(false);

    document.querySelectorAll('[data-modal]:not(.hidden)').forEach((modal) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    document.body.classList.remove('overflow-hidden');
});
