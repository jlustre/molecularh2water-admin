import { registerLoadingOverlays } from './loading-overlays';

document.addEventListener('alpine:init', () => {
    Alpine.data('layoutSidebar', () => ({
        sidebarOpen: false,
        desktopQuery: null,

        init() {
            this.desktopQuery = window.matchMedia('(min-width: 1024px)');
            this.syncSidebarToViewport(true);
            this._onViewportChange = () => this.syncSidebarToViewport(false);
            this.desktopQuery.addEventListener('change', this._onViewportChange);

            this.$el.addEventListener('alpine:destroy', () => {
                this.desktopQuery?.removeEventListener('change', this._onViewportChange);
            });
        },

        isDesktop() {
            return this.desktopQuery?.matches ?? window.matchMedia('(min-width: 1024px)').matches;
        },

        syncSidebarToViewport(isInitial = false) {
            if (this.isDesktop()) {
                if (isInitial) {
                    this.sidebarOpen = true;
                }
            } else {
                this.sidebarOpen = false;
            }
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        closeSidebar() {
            this.sidebarOpen = false;
        },

        closeSidebarOnMobile() {
            if (! this.isDesktop()) {
                this.sidebarOpen = false;
            }
        },
    }));

    Alpine.data('sidebarNavGroups', (storageKey, activeSections = []) => ({
        storageKey,
        groups: JSON.parse(localStorage.getItem(storageKey) || '{}'),

        init() {
            activeSections.forEach((section) => {
                this.groups[section] = true;
            });

            localStorage.setItem(this.storageKey, JSON.stringify(this.groups));
        },

        isOpen(section) {
            return this.groups[section] !== false;
        },

        toggle(section) {
            if (this.isOpen(section)) {
                this.groups[section] = false;
            } else {
                delete this.groups[section];
            }

            localStorage.setItem(this.storageKey, JSON.stringify(this.groups));
        },
    }));

    registerLoadingOverlays();
});
