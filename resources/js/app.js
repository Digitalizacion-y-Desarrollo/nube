import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const themePreference = {
    get() {
        return localStorage.getItem('theme');
    },
    set(theme) {
        localStorage.setItem('theme', theme);
    },
};

const syncThemeControls = () => {
    const isDark = document.documentElement.classList.contains('dark');

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        toggle.setAttribute('title', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    });
};

const setTheme = (theme, persist = true) => {
    const isDark = theme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';

    if (persist) {
        themePreference.set(theme);
    }

    syncThemeControls();
};

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
    if (event.target.closest('[data-theme-toggle]')) {
        const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        setTheme(nextTheme);
    }

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

document.addEventListener('DOMContentLoaded', syncThemeControls);

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-modal-auto-open]');

    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
});

document.querySelectorAll('[data-login-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-login-submit]');
        const label = form.querySelector('[data-login-label]');

        if (!button || !label) {
            return;
        }

        button.disabled = true;
        button.classList.add('opacity-70', 'cursor-wait');
        label.textContent = 'Autenticando...';
    });
});

document.querySelectorAll('[data-sharing-form]').forEach((form) => {
    const visibility = form.querySelector('[data-sharing-visibility]');
    const collaborationOptions = form.querySelector('[data-collaboration-options]');
    const collaborationScope = form.querySelector('[data-collaboration-scope]');
    const selectedCollaborators = form.querySelector('[data-selected-collaborators]');

    const syncSharingControls = () => {
        const isCollaborative = visibility?.value === 'collaborative';
        const isSelected = isCollaborative && collaborationScope?.value === 'selected';

        collaborationOptions?.classList.toggle('hidden', !isCollaborative);
        selectedCollaborators?.classList.toggle('hidden', !isSelected);

        collaborationOptions?.querySelectorAll('select, input').forEach((input) => {
            input.disabled = !isCollaborative
                || (input.type === 'checkbox' && !isSelected);
        });
    };

    visibility?.addEventListener('change', syncSharingControls);
    collaborationScope?.addEventListener('change', syncSharingControls);
    syncSharingControls();
});

document.querySelectorAll('[data-file-upload-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-file-upload-submit]');
        const label = form.querySelector('[data-file-upload-label]');

        if (!button || !label) {
            return;
        }

        button.disabled = true;
        button.classList.add('cursor-wait', 'opacity-70');
        label.textContent = 'Subiendo...';
    });
});

document.querySelectorAll('[data-permanent-delete-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const fileName = form.dataset.fileName || 'este archivo';
        const result = await Swal.fire({
            title: '¿Eliminar permanentemente?',
            text: `El archivo «${fileName}» no podrá recuperarse.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#b42318',
            cancelButtonColor: '#667085',
            reverseButtons: true,
            focusCancel: true,
        });

        if (result.isConfirmed) {
            HTMLFormElement.prototype.submit.call(form);
        }
    });
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (!themePreference.get()) {
        setTheme(event.matches ? 'dark' : 'light', false);
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
