window.toggleProductType = function (mode) {
    var sel = document.getElementById(mode + '_product_type');
    if (!sel) return;
    var val = sel.value;
    var volWrap = document.getElementById(mode + '_volume_wrap');
    var priceWrap = document.getElementById(mode + '_price_wrap');
    var customWrap = document.getElementById(mode + '_custom_volume_wrap');
    var volInput = (mode === 'add') ? document.querySelector('#addModal input[name="volume_product"]') : document.getElementById('edit_volume');

    if (val === 'custom') {
        if (volWrap) volWrap.style.display = 'none';
        if (priceWrap) priceWrap.style.display = 'none';
        if (customWrap) customWrap.style.display = 'block';
    } else if (val === 'unlimited' || val === '1') {
        if (volWrap) {
            volWrap.style.display = 'none';
            if (volInput) volInput.value = '0';
        }
        if (priceWrap) priceWrap.style.display = 'block';
        if (customWrap) customWrap.style.display = 'none';
    } else {
        if (volWrap) {
            volWrap.style.display = 'block';
            if (volInput && volInput.value === '0') volInput.value = '50';
        }
        if (priceWrap) priceWrap.style.display = 'block';
        if (customWrap) customWrap.style.display = 'none';
    }
};

window.toggleUnlimitedFields = window.toggleProductType;

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

    var provSel = document.getElementById('edit_provider');
    if (provSel) {
        provSel.value = p.provider || 'sanayi';
    }

    var pTypeSel = document.getElementById('edit_product_type');
    if (pTypeSel) {
        if (p.custom_volume_enabled == 1) {
            pTypeSel.value = 'custom';
        } else if (p.is_unlimited == 1) {
            pTypeSel.value = 'unlimited';
        } else {
            pTypeSel.value = 'volume';
        }
        window.toggleProductType('edit');
    }

    var minV = document.getElementById('edit_min_volume_gb');
    var maxV = document.getElementById('edit_max_volume_gb');
    var pGb = document.getElementById('edit_price_per_gb');
    if (minV) minV.value = (p.min_volume_gb !== undefined && p.min_volume_gb !== null) ? p.min_volume_gb : 1;
    if (maxV) maxV.value = (p.max_volume_gb !== undefined && p.max_volume_gb !== null) ? p.max_volume_gb : 200;
    if (pGb) pGb.value = (p.price_per_gb !== undefined && p.price_per_gb !== null) ? p.price_per_gb : '';

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
    if (typeof window.toggleProductType === 'function') {
        window.toggleProductType('add');
    }
});
