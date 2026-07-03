function registerLivewireLoadingOverlay(name, scopeAttribute, registryKey, hookFlag) {
    if (window[hookFlag]) {
        return;
    }

    window[hookFlag] = true;
    window[registryKey] = new Set();

    const registerHook = () => {
        if (typeof Livewire === 'undefined' || typeof Livewire.hook !== 'function') {
            return false;
        }

        Livewire.hook('commit', ({ component, succeed, fail }) => {
            window[registryKey].forEach((cb) => {
                cb.onCommit && cb.onCommit(component);
            });
            const done = () => {
                window[registryKey].forEach((cb) => {
                    cb.onDone && cb.onDone(component);
                });
            };
            succeed(() => done());
            fail(() => done());
        });

        return true;
    };

    if (!registerHook()) {
        document.addEventListener('livewire:init', () => registerHook(), { once: true });
    }

    Alpine.data(name, (scopeAttr = scopeAttribute) => ({
        active: 0,
        _timers: new WeakMap(),
        _hookRef: null,
        get visible() {
            return this.active > 0;
        },
        init() {
            const overlayEl = this.$el;

            const findScope = () => overlayEl.closest(`[${scopeAttr}]`)
                || overlayEl.parentElement
                || document.body;

            const componentInScope = (component) => {
                const scope = findScope();
                if (!scope || !component || !component.el) {
                    return false;
                }
                return scope.contains(component.el);
            };

            const cb = {
                onCommit: (component) => {
                    if (!componentInScope(component)) return;
                    const timer = setTimeout(() => {
                        this.active += 1;
                        this._timers.set(component, { shown: true });
                    }, 200);
                    this._timers.set(component, { shown: false, timer });
                },
                onDone: (component) => {
                    const state = this._timers.get(component);
                    if (!state) return;
                    if (state.timer) clearTimeout(state.timer);
                    if (state.shown) {
                        this.active = Math.max(0, this.active - 1);
                    }
                    this._timers.delete(component);
                },
            };

            this._hookRef = cb;
            window[registryKey].add(cb);

            window.addEventListener('livewire:navigating', () => {
                this.active = 0;
                this._timers = new WeakMap();
            });
        },
        destroy() {
            if (this._hookRef) {
                window[registryKey].delete(this._hookRef);
            }
        },
    }));
}

export function registerLoadingOverlays() {
    registerLivewireLoadingOverlay(
        'portalPageLoadingOverlay',
        'data-portal-page-scope',
        '__portalPageLoadingOverlays',
        '__portalPageLoadingHookRegistered',
    );

    registerLivewireLoadingOverlay(
        'crmCalendarLoadingOverlay',
        'data-crm-calendar-scope',
        '__crmCalendarLoadingOverlays',
        '__crmCalendarLoadingHookRegistered',
    );
}
