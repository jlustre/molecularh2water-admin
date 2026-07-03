import { registerLoadingOverlays } from './loading-overlays';

document.addEventListener('alpine:init', () => {
    Alpine.data('layoutSidebar', () => ({
        sidebarOpen: window.matchMedia('(min-width: 1024px)').matches,

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        closeSidebar() {
            this.sidebarOpen = false;
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
