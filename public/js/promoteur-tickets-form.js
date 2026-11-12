/* Formulaire des types de billets (promoteur) : ajout/suppression de blocs, modèles,
   estimation de recette, cohérence des dates. Aucune donnée utilisateur n'est injectée via innerHTML. */
(function () {
    'use strict';

    var form = document.querySelector('[data-ticket-form]');
    if (!form) { return; }

    var container = form.querySelector('[data-ticket-blocks]');
    var template = form.querySelector('template[data-ticket-template]');
    var addButton = form.querySelector('[data-add-block]');
    var nextIndex = parseInt(container.getAttribute('data-next-index') || '0', 10);
    var money = new Intl.NumberFormat('fr-FR');

    function blocks() { return container.querySelectorAll('[data-ticket-block]'); }

    function renumber() {
        var list = blocks();
        list.forEach(function (block, i) {
            var label = block.querySelector('[data-block-number]');
            if (label) { label.textContent = '#' + (i + 1); }
            var remove = block.querySelector('[data-remove-block]');
            if (remove) { remove.hidden = list.length < 2; }
        });
    }

    function field(block, name) { return block.querySelector('[data-field="' + name + '"]'); }

    function updateRevenue(block) {
        var price = parseFloat(field(block, 'price').value) || 0;
        var qty = parseInt(field(block, 'quantity').value, 10) || 0;
        block.querySelector('[data-revenue]').textContent = money.format(price * qty) + ' FCFA';
    }

    function checkDates(block) {
        var start = field(block, 'start');
        var end = field(block, 'end');
        if (!start || !end) { return; }
        if (start.value) { end.min = start.value; }
        end.setCustomValidity(start.value && end.value && end.value <= start.value
            ? 'La date de fin de vente doit être après la date de début.' : '');
    }

    function addBlock() {
        var fragment = template.content.cloneNode(true);
        var index = String(nextIndex++);
        fragment.querySelectorAll('*').forEach(function (el) {
            Array.prototype.slice.call(el.attributes).forEach(function (attr) {
                if (attr.value.indexOf('__INDEX__') !== -1) {
                    el.setAttribute(attr.name, attr.value.split('__INDEX__').join(index));
                }
            });
        });
        var block = fragment.querySelector('[data-ticket-block]');
        container.appendChild(fragment);
        renumber();
        updateRevenue(block);
        checkDates(block);
        return block;
    }

    function isPristine(block) {
        var name = block.querySelector('input[name$="[name]"], input[name="name"]').value;
        return (!name || name === 'Standard') && !field(block, 'price').value;
    }

    function applyPreset(button) {
        var list = blocks();
        var target = (list.length === 1 && isPristine(list[0])) ? list[0] : addBlock();
        target.querySelector('input[name$="[name]"], input[name="name"]').value = button.dataset.name || '';
        target.querySelector('textarea').value = button.dataset.description || '';
        field(target, 'price').value = button.dataset.price || '';
        field(target, 'quantity').value = button.dataset.quantity || '';
        field(target, 'max_per_order').value = button.dataset.max || '';
        updateRevenue(target);
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    form.addEventListener('input', function (e) {
        var block = e.target.closest('[data-ticket-block]');
        if (!block) { return; }
        if (e.target.matches('[data-field="price"], [data-field="quantity"]')) { updateRevenue(block); }
        if (e.target.matches('[data-field="start"], [data-field="end"]')) { checkDates(block); }
    });

    form.addEventListener('click', function (e) {
        var remove = e.target.closest('[data-remove-block]');
        if (remove && blocks().length > 1) {
            remove.closest('[data-ticket-block]').remove();
            renumber();
        }
    });

    if (addButton && template) { addButton.addEventListener('click', function () { addBlock().scrollIntoView({ behavior: 'smooth', block: 'start' }); }); }

    document.querySelectorAll('[data-preset]').forEach(function (button) {
        button.addEventListener('click', function () { applyPreset(button); });
    });

    blocks().forEach(function (block) { updateRevenue(block); checkDates(block); });
    renumber();

    var summary = document.getElementById('tt-error-summary');
    if (summary) { summary.focus(); }
})();
