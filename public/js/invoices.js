(() => {
    const list = document.querySelector('#line-items');
    const addButton = document.querySelector('#add-line');
    const template = document.querySelector('#line-item-template');
    const total = document.querySelector('#total-preview');

    if (!list || !addButton || !template || !total) {
        return;
    }

    const updateTotal = () => {
        const amount = [...list.querySelectorAll('[data-name="amount"], input[name$="[amount]"]')]
            .reduce((sum, input) => sum + (Number(input.value) || 0), 0);

        total.firstChild.textContent = `${new Intl.NumberFormat('fr-FR').format(amount)} `;
    };

    const renumberRows = () => {
        list.querySelectorAll('[data-line-item]').forEach((row, index) => {
            row.querySelectorAll('[data-name]').forEach((input) => {
                input.name = `items[${index}][${input.dataset.name}]`;
            });
        });
    };

    addButton.addEventListener('click', () => {
        list.append(template.content.cloneNode(true));
        renumberRows();
        updateTotal();
        list.querySelector('[data-line-item]:last-child input')?.focus();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-line');
        if (!button) {
            return;
        }

        const rows = list.querySelectorAll('[data-line-item]');
        if (rows.length === 1) {
            rows[0].querySelectorAll('input').forEach((input) => {
                input.value = '';
            });
        } else {
            button.closest('[data-line-item]').remove();
        }

        renumberRows();
        updateTotal();
    });

    list.addEventListener('input', (event) => {
        if (event.target.matches('input[name$="[amount]"], [data-name="amount"]')) {
            updateTotal();
        }
    });

    updateTotal();
})();
