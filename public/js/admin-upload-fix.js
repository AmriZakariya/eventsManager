(function () {
    // The host firewall (ModSecurity/Imunify) answers 403 before Laravel runs
    // when an uploaded file name contains characters like an apostrophe, e.g.
    // "CATALOGUE_D'HYGIENE.pdf". Rename the file in the browser to a safe name.
    function safeFileName(name) {
        const dot = name.lastIndexOf('.');
        const base = dot > 0 ? name.slice(0, dot) : name;
        const ext = dot > 0 ? name.slice(dot + 1) : '';

        const clean = function (value) {
            return value
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .replace(/[^A-Za-z0-9._-]+/g, '_')
                .replace(/_+/g, '_')
                .replace(/^[_.-]+|[_.-]+$/g, '');
        };

        const safeBase = clean(base) || 'file';
        const safeExt = clean(ext);

        return safeExt ? safeBase + '.' + safeExt : safeBase;
    }

    function sanitizeInput(input) {
        if (!input.files || input.files.length === 0 || typeof DataTransfer === 'undefined') {
            return;
        }

        let renamed = false;
        const transfer = new DataTransfer();

        Array.from(input.files).forEach(function (file) {
            const name = safeFileName(file.name);

            if (name !== file.name) {
                renamed = true;
                file = new File([file], name, { type: file.type, lastModified: file.lastModified });
            }

            transfer.items.add(file);
        });

        if (renamed) {
            input.files = transfer.files;
        }
    }

    document.addEventListener('change', function (event) {
        const input = event.target;

        if (input instanceof HTMLInputElement && input.type === 'file') {
            sanitizeInput(input);
        }
    }, true);

    function toast(message) {
        try {
            const wrapper = document.querySelector('[data-controller="toast"]');
            const controller = window.application.getControllerForElementAndIdentifier(wrapper, 'toast');
            controller.toast(message, 'danger');
        } catch (error) {
            window.alert(message);
        }
    }

    function resetSubmitButtons(form) {
        form.removeAttribute('data-form-submit');

        document.querySelectorAll('button.btn-loading').forEach(function (button) {
            button.disabled = false;
            button.classList.remove('cursor-wait', 'btn-loading');
            button.querySelectorAll('.spinner-loading').forEach(function (spinner) {
                spinner.remove();
            });
        });
    }

    // Orchid ignores failed saves (403/413/5xx), so the page looked like it
    // simply reloaded. Stop Turbo from handling the error page and tell the
    // user what happened instead.
    document.addEventListener('turbo:before-fetch-response', function (event) {
        const form = event.target;
        const response = event.detail && event.detail.fetchResponse;

        if (!(form instanceof HTMLFormElement) || !response) {
            return;
        }

        const status = response.statusCode;
        let message;

        if (status === 403) {
            message = 'The server blocked this save (403). If you attached a file, rename it '
                + 'without special characters such as apostrophes, quotes or parentheses and try again.';
        } else if (status === 413) {
            message = 'The uploaded file is too large for the server.';
        } else if (status >= 500) {
            message = 'Server error (' + status + '). The changes were not saved.';
        } else {
            return; // Redirects and validation errors are handled by Orchid/Laravel
        }

        event.preventDefault();
        resetSubmitButtons(form);
        toast(message);
    });
})();
