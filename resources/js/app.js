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

let sidebarTrigger = null;

const setSidebarState = (open, trigger = null) => {
    const sidebar = document.querySelector('#mobile-sidebar');

    if (!sidebar) {
        return;
    }

    sidebar.classList.toggle('hidden', !open);
    sidebar.setAttribute('aria-hidden', String(!open));
    document.body.classList.toggle('overflow-hidden', open);

    if (open) {
        sidebarTrigger = trigger;
        window.requestAnimationFrame(() => {
            sidebar.querySelector('[data-sidebar-close]')?.focus();
        });
    } else {
        sidebarTrigger?.focus();
        sidebarTrigger = null;
    }
};

let activeModalTrigger = null;
const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const openModal = (modal, trigger = null) => {
    if (!modal) {
        return;
    }

    activeModalTrigger = trigger;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');

    window.requestAnimationFrame(() => {
        const preferredFocus = modal.querySelector('[autofocus]')
            || modal.querySelector(focusableSelector)
            || modal.querySelector('[data-modal-panel]');
        preferredFocus?.focus();
    });
};

const closeModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');

    if (!document.querySelector('[data-modal]:not(.hidden)')) {
        document.body.classList.remove('overflow-hidden');
    }

    activeModalTrigger?.focus();
    activeModalTrigger = null;
};

const globalSearch = document.querySelector('[data-global-search]');
const globalSearchInput = globalSearch?.querySelector('[data-global-search-input]');
const globalSearchResults = globalSearch?.querySelector('[data-global-search-results]');
const globalSearchStatus = globalSearch?.querySelector('[data-global-search-status]');
let globalSearchTrigger = null;
let globalSearchTimer = null;
let globalSearchRequest = null;
let globalSearchItems = [];
let globalSearchActiveIndex = -1;

const setGlobalSearchStatus = (message, loading = false) => {
    if (!globalSearchStatus || !globalSearchResults) {
        return;
    }

    globalSearchStatus.textContent = message;
    globalSearchStatus.classList.remove('hidden');
    globalSearchStatus.classList.add('flex');
    globalSearchResults.classList.add('hidden');
    globalSearchResults.replaceChildren();
    globalSearch?.setAttribute('aria-busy', String(loading));
    globalSearchInput?.setAttribute('aria-expanded', 'false');
    globalSearchInput?.setAttribute('aria-label', 'Buscar archivos y carpetas');
    globalSearchInput?.removeAttribute('aria-activedescendant');
    globalSearchItems = [];
    globalSearchActiveIndex = -1;
};

const syncGlobalSearchSelection = (nextIndex) => {
    if (globalSearchItems.length === 0) {
        return;
    }

    globalSearchActiveIndex = (nextIndex + globalSearchItems.length) % globalSearchItems.length;

    globalSearchItems.forEach((item, index) => {
        const isActive = index === globalSearchActiveIndex;
        item.dataset.active = String(isActive);
        item.setAttribute('aria-selected', String(isActive));

        if (isActive) {
            globalSearchInput?.setAttribute('aria-activedescendant', item.id);
            item.scrollIntoView({ block: 'nearest' });
        }
    });
};

const renderGlobalSearchResults = (results, query) => {
    if (!globalSearchResults || !globalSearchStatus) {
        return;
    }

    globalSearchResults.replaceChildren();

    results.forEach((result, index) => {
        const item = result.url
            ? document.createElement('a')
            : document.createElement('div');
        const iconShell = document.createElement('span');
        const icon = document.createElement('img');
        const content = document.createElement('span');
        const name = document.createElement('span');
        const meta = document.createElement('span');
        const badge = document.createElement('span');

        item.id = `global-search-result-${index}`;
        item.className = 'spotlight-result flex items-center gap-3 rounded-xl border border-transparent px-3 py-3 text-left outline-none transition sm:px-4';
        item.setAttribute('role', 'option');
        item.setAttribute('aria-selected', 'false');
        item.dataset.active = 'false';

        if (result.url) {
            item.href = result.url;
        } else {
            item.setAttribute('aria-disabled', 'true');
        }

        iconShell.className = 'flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/55 shadow-sm dark:bg-white/8';
        icon.src = result.kind === 'folder'
            ? globalSearch.dataset.folderIcon
            : globalSearch.dataset.fileIcon;
        icon.alt = '';
        icon.width = 21;
        icon.height = 21;
        iconShell.append(icon);

        content.className = 'min-w-0 flex-1';
        name.className = 'block truncate text-sm font-semibold text-ink';
        name.textContent = result.name;
        meta.className = 'mt-1 block truncate text-xs text-muted';
        meta.textContent = result.meta;
        content.append(name, meta);

        badge.className = 'hidden shrink-0 rounded-lg bg-white/50 px-2.5 py-1 text-[10px] font-semibold text-muted dark:bg-white/6 sm:inline-flex';
        badge.textContent = result.visibility;

        item.append(iconShell, content, badge);
        item.addEventListener('mouseenter', () => syncGlobalSearchSelection(index));
        globalSearchResults.append(item);
    });

    globalSearchStatus.classList.add('hidden');
    globalSearchStatus.classList.remove('flex');
    globalSearchResults.classList.remove('hidden');
    globalSearch?.setAttribute('aria-busy', 'false');
    globalSearchInput?.setAttribute('aria-expanded', 'true');
    globalSearchItems = [...globalSearchResults.querySelectorAll('[role="option"]')];
    syncGlobalSearchSelection(0);

    const resultLabel = results.length === 1 ? 'resultado' : 'resultados';
    globalSearchInput?.setAttribute(
        'aria-label',
        `${results.length} ${resultLabel} para ${query}`,
    );
};

const performGlobalSearch = async () => {
    const query = globalSearchInput?.value.trim() || '';

    if (query.length < 2) {
        globalSearchRequest?.abort();
        setGlobalSearchStatus('Escribe al menos 2 caracteres para buscar en todo tu contenido disponible.');
        return;
    }

    globalSearchRequest?.abort();
    globalSearchRequest = new AbortController();
    setGlobalSearchStatus(`Buscando “${query}”…`, true);

    try {
        const response = await fetch(
            `${globalSearch.dataset.searchUrl}?q=${encodeURIComponent(query)}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: globalSearchRequest.signal,
            },
        );

        if (!response.ok) {
            throw new Error(`Search request failed with status ${response.status}`);
        }

        const payload = await response.json();

        if (payload.results.length === 0) {
            setGlobalSearchStatus(`No encontramos archivos o carpetas para “${query}”.`);
            return;
        }

        renderGlobalSearchResults(payload.results, query);
    } catch (error) {
        if (error.name !== 'AbortError') {
            setGlobalSearchStatus('No fue posible completar la búsqueda. Inténtalo de nuevo.');
        }
    }
};

const openGlobalSearch = (trigger = null) => {
    if (!globalSearch) {
        return;
    }

    globalSearchTrigger = trigger;
    globalSearch.classList.remove('hidden');
    globalSearch.classList.add('flex');
    globalSearch.setAttribute('aria-hidden', 'false');
    document.querySelectorAll('[data-global-search-open]').forEach((trigger) => {
        trigger.setAttribute('aria-expanded', 'true');
    });
    document.body.classList.add('overflow-hidden');

    window.requestAnimationFrame(() => {
        globalSearchInput?.focus();
        globalSearchInput?.select();
    });
};

const closeGlobalSearch = () => {
    if (!globalSearch) {
        return;
    }

    globalSearchRequest?.abort();
    window.clearTimeout(globalSearchTimer);
    globalSearch.classList.add('hidden');
    globalSearch.classList.remove('flex');
    globalSearch.setAttribute('aria-hidden', 'true');
    document.querySelectorAll('[data-global-search-open]').forEach((trigger) => {
        trigger.setAttribute('aria-expanded', 'false');
    });
    document.body.classList.remove('overflow-hidden');
    globalSearchTrigger?.focus();
    globalSearchTrigger = null;
};

globalSearchInput?.addEventListener('input', () => {
    window.clearTimeout(globalSearchTimer);
    globalSearchTimer = window.setTimeout(performGlobalSearch, 180);
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-theme-toggle]')) {
        const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        setTheme(nextTheme);
    }

    if (event.target.closest('[data-sidebar-open]')) {
        setSidebarState(true, event.target.closest('[data-sidebar-open]'));
    }

    if (event.target.closest('[data-sidebar-close]')) {
        setSidebarState(false);
    }

    const globalSearchOpen = event.target.closest('[data-global-search-open]');
    if (globalSearchOpen) {
        openGlobalSearch(globalSearchOpen);
    }

    if (event.target.closest('[data-global-search-close]')) {
        closeGlobalSearch();
    }

    if (event.target === globalSearch) {
        closeGlobalSearch();
    }

    const modalOpen = event.target.closest('[data-modal-open]');
    if (modalOpen) {
        const modal = document.getElementById(modalOpen.dataset.modalOpen);
        openModal(modal, modalOpen);
    }

    const modalClose = event.target.closest('[data-modal-close]');
    if (modalClose) {
        const modal = document.getElementById(modalClose.dataset.modalClose);
        closeModal(modal);
    }

    if (event.target.matches('[data-modal]:not(.hidden)')) {
        closeModal(event.target);
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
        openModal(modal);
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
            const option = input.closest('[data-collaborator-option]');
            const collaboratorSelected = option
                ?.querySelector('[data-collaborator-checkbox]')?.checked === true;
            const isPermissionInput = input.matches('[data-collaborator-permission-input]');

            input.disabled = !isCollaborative
                || (input.type === 'checkbox' && !isSelected)
                || (isPermissionInput && (!isSelected || !collaboratorSelected));
        });

        if (!isSelected) {
            selectedCollaborators?.querySelector('[data-collaborator-list]')?.classList.add('hidden');
            selectedCollaborators?.querySelector('[data-collaborator-search]')
                ?.setAttribute('aria-expanded', 'false');
        }
    };

    visibility?.addEventListener('change', syncSharingControls);
    collaborationScope?.addEventListener('change', syncSharingControls);
    syncSharingControls();
});

const normalizeSearchValue = (value) => value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('es')
    .trim();

document.querySelectorAll('[data-collaborator-picker]').forEach((picker) => {
    const search = picker.querySelector('[data-collaborator-search]');
    const list = picker.querySelector('[data-collaborator-list]');
    const options = [...picker.querySelectorAll('[data-collaborator-option]')];
    const empty = picker.querySelector('[data-collaborator-empty]');
    const summary = picker.querySelector('[data-collaborator-summary]');
    let activeIndex = -1;

    if (!search || !list) {
        return;
    }

    const visibleOptions = () => options.filter((option) => !option.classList.contains('hidden'));

    const setOpen = (open) => {
        list.classList.toggle('hidden', !open);
        search.setAttribute('aria-expanded', String(open));

        if (!open) {
            search.removeAttribute('aria-activedescendant');
            activeIndex = -1;
            options.forEach((option) => option.removeAttribute('data-active'));
        }
    };

    const syncSummary = () => {
        const selected = options.filter((option) => {
            const checkbox = option.querySelector('[data-collaborator-checkbox]');
            const isSelected = checkbox?.checked === true;

            option.setAttribute('aria-selected', String(isSelected));
            option.classList.toggle('border-brand/25', isSelected);
            option.classList.toggle('bg-brand/5', isSelected);
            option.querySelector('[data-collaborator-permissions]')
                ?.classList.toggle('hidden', !isSelected);
            option.querySelectorAll('[data-collaborator-permission-input]')
                .forEach((input) => {
                    input.disabled = !isSelected;
                });

            return isSelected;
        });

        if (summary) {
            summary.textContent = selected.length === 0
                ? 'Ninguna persona seleccionada'
                : `${selected.length} ${selected.length === 1 ? 'persona seleccionada' : 'personas seleccionadas'}`;
        }
    };

    const filterOptions = () => {
        const query = normalizeSearchValue(search.value);
        let matches = 0;

        options.forEach((option) => {
            const searchableValue = normalizeSearchValue(option.dataset.searchValue || '');
            const matchesQuery = query === '' || searchableValue.includes(query);

            option.classList.toggle('hidden', !matchesQuery);
            matches += matchesQuery ? 1 : 0;
        });

        empty?.classList.toggle('hidden', matches > 0);
        activeIndex = -1;
        search.removeAttribute('aria-activedescendant');
        setOpen(true);
    };

    const setActiveOption = (nextIndex) => {
        const visible = visibleOptions();

        if (visible.length === 0) {
            return;
        }

        activeIndex = (nextIndex + visible.length) % visible.length;
        options.forEach((option) => option.removeAttribute('data-active'));

        const active = visible[activeIndex];
        active.dataset.active = 'true';
        search.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    };

    search.addEventListener('focus', filterOptions);
    search.addEventListener('input', filterOptions);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
            setActiveOption(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setOpen(true);
            setActiveOption(activeIndex - 1);
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            const option = visibleOptions()[activeIndex];
            const checkbox = option?.querySelector('[data-collaborator-checkbox]');

            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                syncSummary();
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
    });

    options.forEach((option) => {
        option.querySelector('[data-collaborator-checkbox]')?.addEventListener('change', syncSummary);
    });

    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            setOpen(false);
        }
    });

    syncSummary();
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

document.querySelectorAll('[data-avatar-form]').forEach((form) => {
    const input = form.querySelector('[data-avatar-input]');
    const submit = form.querySelector('[data-avatar-submit]');
    const cancel = form.querySelector('[data-avatar-cancel]');
    const info = form.querySelector('[data-avatar-file-info]');
    const error = form.querySelector('[data-avatar-file-error]');
    const preview = document.querySelector('[data-avatar-preview]');

    if (!input || !preview) {
        return;
    }

    const currentSrc = preview.dataset.avatarCurrent || preview.src;
    const badge = document.querySelector('[data-avatar-preview-badge]');
    const maxKb = Number(form.dataset.avatarMaxKb || 0);
    const extensions = (form.dataset.avatarExtensions || '')
        .split(',')
        .map((extension) => extension.trim().toLowerCase())
        .filter(Boolean);

    let objectUrl = null;

    const releaseObjectUrl = () => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    };

    const formatSize = (bytes) => {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        const units = ['KB', 'MB', 'GB'];
        let value = bytes / 1024;
        let unitIndex = 0;

        while (value >= 1024 && unitIndex < units.length - 1) {
            value /= 1024;
            unitIndex += 1;
        }

        return `${value.toFixed(1)} ${units[unitIndex]}`;
    };

    const setMessage = (element, message) => {
        if (!element) {
            return;
        }

        element.textContent = message || '';
        element.hidden = !message;
    };

    const reset = () => {
        releaseObjectUrl();
        input.value = '';
        preview.src = currentSrc;
        setMessage(info, '');
        setMessage(error, '');

        if (badge) {
            badge.hidden = true;
        }

        if (cancel) {
            cancel.hidden = true;
        }

        if (submit) {
            submit.disabled = false;
        }
    };

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];

        if (!file) {
            reset();

            return;
        }

        const extension = (file.name.split('.').pop() || '').toLowerCase();
        const tooLarge = maxKb > 0 && file.size > maxKb * 1024;
        const wrongType = extensions.length > 0 && !extensions.includes(extension);

        releaseObjectUrl();

        // La validación del navegador sólo evita un envío inútil; el servidor
        // sigue siendo el que autoriza y rechaza.
        if (tooLarge || wrongType) {
            preview.src = currentSrc;

            if (badge) {
                badge.hidden = true;
            }

            setMessage(info, '');
            setMessage(
                error,
                tooLarge
                    ? `«${file.name}» pesa ${formatSize(file.size)} y el máximo es ${formatSize(maxKb * 1024)}.`
                    : `«${file.name}» no es un formato permitido (${extensions.join(', ').toUpperCase()}).`,
            );

            if (cancel) {
                cancel.hidden = false;
            }

            if (submit) {
                submit.disabled = true;
            }

            return;
        }

        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        setMessage(error, '');
        setMessage(info, `${file.name} · ${formatSize(file.size)}`);

        if (badge) {
            badge.hidden = false;
        }

        if (cancel) {
            cancel.hidden = false;
        }

        if (submit) {
            submit.disabled = false;
        }
    });

    cancel?.addEventListener('click', reset);

    form.addEventListener('submit', () => {
        if (submit) {
            submit.disabled = true;
            submit.classList.add('cursor-wait', 'opacity-70');
            submit.textContent = 'Guardando...';
        }
    });

    window.addEventListener('pagehide', releaseObjectUrl);
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
    const globalSearchIsOpen = globalSearch && !globalSearch.classList.contains('hidden');
    const activeModal = document.querySelector('[data-modal]:not(.hidden)');
    const openSidebar = document.querySelector('#mobile-sidebar:not(.hidden)');

    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();

        if (globalSearchIsOpen) {
            closeGlobalSearch();
        } else {
            openGlobalSearch(document.querySelector('[data-global-search-open]'));
        }

        return;
    }

    if (globalSearchIsOpen) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            syncGlobalSearchSelection(globalSearchActiveIndex + 1);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            syncGlobalSearchSelection(globalSearchActiveIndex - 1);
            return;
        }

        if (event.key === 'Enter' && globalSearchActiveIndex >= 0) {
            const activeItem = globalSearchItems[globalSearchActiveIndex];

            if (activeItem instanceof HTMLAnchorElement) {
                event.preventDefault();
                activeItem.click();
            }

            return;
        }

        if (event.key === 'Tab') {
            const focusableElements = [...globalSearch.querySelectorAll(focusableSelector)]
                .filter((element) => element.getClientRects().length > 0);
            const first = focusableElements[0];
            const last = focusableElements.at(-1);

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }

            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeGlobalSearch();
            return;
        }
    }

    if (event.key === 'Tab' && activeModal) {
        const focusableElements = [...activeModal.querySelectorAll(focusableSelector)]
            .filter((element) => element.getClientRects().length > 0);

        if (focusableElements.length === 0) {
            event.preventDefault();
            activeModal.querySelector('[data-modal-panel]')?.focus();
            return;
        }

        const first = focusableElements[0];
        const last = focusableElements.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }

        return;
    }

    if (event.key === 'Tab' && openSidebar) {
        const sidebarElements = [...openSidebar.querySelectorAll(focusableSelector)]
            .filter((element) => element.getClientRects().length > 0);
        const first = sidebarElements[0];
        const last = sidebarElements.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }

        return;
    }

    if (event.key !== 'Escape') {
        return;
    }

    if (activeModal) {
        closeModal(activeModal);
        return;
    }

    setSidebarState(false);
});
