// Address management (window.APP_BASE, window.ADDRESS_PAGE_I18N)
(function() {
    'use strict';

    const baseUrl = (window.APP_BASE || '').replace(/\/$/, '') || '';
    const I = window.ADDRESS_PAGE_I18N || {};

    window.openAddModal = function() {
        const modalLabel = document.getElementById('addressModalLabel');
        const form = document.getElementById('addressForm');
        if (!modalLabel || !form) return;
        modalLabel.textContent = I.modalAdd || '';
        form.reset();
        document.getElementById('addressId').value = '';
        const isDefault = document.getElementById('isDefault');
        if (isDefault) isDefault.checked = false;
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    };

    window.openEditModal = async function(addressId) {
        try {
            const response = await fetch(baseUrl + (baseUrl ? '/' : '') + 'api/address/get?id=' + encodeURIComponent(addressId));
            const data = await response.json();
            if (!data.success || !data.address) {
                alert(I.loadFailed || '');
                return;
            }
            const address = data.address;
            const modal = document.getElementById('addressModal');
            const modalLabel = document.getElementById('addressModalLabel');
            const form = document.getElementById('addressForm');
            if (!modal || !modalLabel || !form) return;
            modalLabel.textContent = I.modalEdit || '';
            document.getElementById('addressId').value = address.id;
            document.getElementById('addressLabel').value = address.address_label || '';
            document.getElementById('addressType').value = address.address_type || I.residentialDefault || '';
            document.getElementById('recipientName').value = address.recipient_name || '';
            document.getElementById('phone').value = address.phone || '';
            document.getElementById('region').value = address.region || '';
            document.getElementById('district').value = address.district || '';
            document.getElementById('villageEstate').value = address.village_estate || '';
            document.getElementById('street').value = address.street || '';
            document.getElementById('building').value = address.building || '';
            document.getElementById('floor').value = address.floor || '';
            document.getElementById('unit').value = address.unit || '';
            const isDefault = document.getElementById('isDefault');
            if (isDefault) isDefault.checked = address.is_default == 1;
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } catch (error) {
            console.error('Error loading address:', error);
            alert(I.loadError || '');
        }
    };

    window.saveAddress = async function() {
        const form = document.getElementById('addressForm');
        if (!form) return;
        const formData = new FormData(form);
        const addressId = formData.get('id');
        const data = Object.fromEntries(formData.entries());
        if (!data.recipient_name || !data.phone || !data.region || !data.district || !data.building || !data.unit) {
            alert(I.alertRequired || '');
            return;
        }
        if (!data.village_estate && !data.street) {
            alert(I.alertVillageOrStreet || '');
            return;
        }
        data.is_default = document.getElementById('isDefault').checked ? 1 : 0;
        try {
            const path = addressId ? 'api/address/update' : 'api/address/create';
            const url = baseUrl + (baseUrl ? '/' : '') + path;
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(addressId ? { ...data, id: addressId } : data)
            });
            const result = await response.json();
            if (result.success) {
                const modalEl = document.getElementById('addressModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                window.location.reload();
            } else {
                alert(result.error || I.saveFailed || '');
            }
        } catch (error) {
            console.error('Error saving address:', error);
            alert(I.saveError || '');
        }
    };

    window.deleteAddress = async function(addressId) {
        if (!confirm(I.confirmDelete || '')) return;
        try {
            const url = baseUrl + (baseUrl ? '/' : '') + 'api/address/delete';
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: addressId })
            });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.error || I.deleteFailed || '');
            }
        } catch (error) {
            console.error('Error deleting address:', error);
            alert(I.deleteError || '');
        }
    };

    window.setDefault = async function(addressId) {
        try {
            const url = baseUrl + (baseUrl ? '/' : '') + 'api/address/set-default';
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: addressId })
            });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.error || I.defaultFailed || '');
            }
        } catch (error) {
            console.error('Error setting default address:', error);
            alert(I.defaultError || '');
        }
    };
})();
