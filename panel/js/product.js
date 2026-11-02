window.updateAddExtraPricesVisibility = function () {
    var sel = document.getElementById('add_limit_ip');
    var wrap = document.getElementById('add_extra_prices_wrap');
    if (sel && wrap) {
        wrap.style.display = (sel.value === '-1') ? 'block' : 'none';
    }
};

window.updateEditExtraPricesVisibility = function () {
    var sel = document.getElementById('edit_limit_ip');
    var wrap = document.getElementById('edit_extra_prices_wrap');
    if (sel && wrap) {
        wrap.style.display = (sel.value === '-1') ? 'block' : 'none';
    }
};

window.openEditModal = function (p) {
    document.getElementById('edit_id').value = p.id || '';
    document.getElementById('edit_name').value = p.name_product || '';
    document.getElementById('edit_price').value = p.price_product || '';
    document.getElementById('edit_volume').value = p.Volume_constraint || '';
    document.getElementById('edit_time').value = p.Service_time || '';
    document.getElementById('edit_cat').value = p.category || '';
    document.getElementById('edit_agent').value = p.agent || '';
    document.getElementById('edit_note').value = p.note || '';
    document.getElementById('edit_position').value = p.position || '';

    var lipSel = document.getElementById('edit_limit_ip');
    if (lipSel) {
        lipSel.value = (p.limit_ip !== undefined && p.limit_ip !== null) ? String(p.limit_ip) : '1';
    }

    var extraPrices = {};
    if (p.extra_user_prices) {
        if (typeof p.extra_user_prices === 'string') {
            try {
                extraPrices = JSON.parse(p.extra_user_prices);
            } catch (e) {
                extraPrices = {};
            }
        } else if (typeof p.extra_user_prices === 'object') {
            extraPrices = p.extra_user_prices;
        }
    }
    var e2 = document.getElementById('edit_extra_user_2');
    var e3 = document.getElementById('edit_extra_user_3');
    var e4 = document.getElementById('edit_extra_user_4');
    var e5 = document.getElementById('edit_extra_user_5');
    if (e2) e2.value = (extraPrices && extraPrices['2'] !== undefined) ? extraPrices['2'] : 150000;
    if (e3) e3.value = (extraPrices && extraPrices['3'] !== undefined) ? extraPrices['3'] : 100000;
    if (e4) e4.value = (extraPrices && extraPrices['4'] !== undefined) ? extraPrices['4'] : 100000;
    if (e5) e5.value = (extraPrices && extraPrices['5'] !== undefined) ? extraPrices['5'] : 100000;

    window.updateEditExtraPricesVisibility();

    var sel = document.getElementById('edit_panel');
    if (sel) {
        for (var i = 0; i < sel.options.length; i++) {
            sel.options[i].selected = sel.options[i].value === (p.Location || '');
        }
    }

    openModal('editModal');
};

document.addEventListener('DOMContentLoaded', function () {
    window.updateAddExtraPricesVisibility();
    window.updateEditExtraPricesVisibility();
});
