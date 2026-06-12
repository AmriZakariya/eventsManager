(function () {
    function clearCurrentTabsState() {
        try {
            const raw = localStorage.getItem('tabs');
            const tabs = raw ? JSON.parse(raw) : null;

            if (!tabs || typeof tabs !== 'object') {
                return;
            }

            const currentUrl = window.location.href.split(/[?#]/)[0];

            if (tabs[currentUrl]) {
                delete tabs[currentUrl];
                localStorage.setItem('tabs', JSON.stringify(tabs));
            }
        } catch (error) {
            localStorage.removeItem('tabs');
        }
    }

    function clearTurboCache() {
        if (window.Turbo && typeof window.Turbo.clearCache === 'function') {
            window.Turbo.clearCache();
        }
    }

    document.addEventListener('turbo:submit-end', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.id !== 'post-form') {
            return;
        }

        if (event.detail && event.detail.success) {
            clearCurrentTabsState();
            clearTurboCache();
        }
    });

    document.addEventListener('turbo:before-cache', function () {
        const form = document.getElementById('post-form');

        if (form) {
            form.setAttribute('data-turbo-temporary', '');
        }
    });
})();
