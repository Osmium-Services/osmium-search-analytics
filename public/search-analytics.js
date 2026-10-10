document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('synonymModal'));
    const termInput = document.getElementById('synonym-term');
    const targetInput = document.getElementById('synonym-target');
    const suggestionText = document.getElementById('synonym-suggestion');
    const saveBtn = document.getElementById('save-synonym-btn');

    // "Add synonym" menu item (rendered by ListView) fires list:action with the row's data-* attributes
    document.addEventListener('list:action', function(event) {
        const { action, row } = event.detail;
        if (action !== 'add-synonym') return;

        const term = row.dataset.search_term;
        const suggested = row.dataset.suggestion;
        termInput.value = term;
        targetInput.value = suggested;

        const strong = document.createElement('strong');
        strong.textContent = suggested;
        suggestionText.replaceChildren('Did you mean ', strong, '?');
        modal.show();
    });

    saveBtn.addEventListener('click', async function() {
        const term = termInput.value.trim();
        const target = targetInput.value.trim();

        if (!target) {
            adminAlert('Please enter a target term');
            return;
        }

        saveBtn.disabled = true;
        try {
            const resp = await fetch(window.searchAnalyticsConfig.actionUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'add_synonym', term, target, csrf_token: window.csrfToken})
            });
            const data = await resp.json();

            if (data.success) {
                modal.hide();
                location.reload();
            } else {
                adminAlert(data.error || 'Failed to save');
            }
        } finally {
            saveBtn.disabled = false;
        }
    });
});
