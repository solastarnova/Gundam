// Account address page wrapper over shared module
(function() {
    'use strict';
    if (typeof window.createAddressModalManager !== 'function') {
        console.error('address-modal.shared.js not loaded');
        return;
    }
    var manager = window.createAddressModalManager({
        baseUrl: window.APP_BASE || '/',
        i18n: window.ADDRESS_PAGE_I18N || {},
        requireMapPin: true,
        onSaveSuccess: function() { window.location.reload(); },
        onDeleteSuccess: function() { window.location.reload(); },
        onSetDefaultSuccess: function() { window.location.reload(); }
    });

    window.openAddModal = manager.openAddModal;
    window.openEditModal = manager.openEditModal;
    window.saveAddress = manager.saveAddress;
    window.deleteAddress = manager.deleteAddress;
    window.setDefault = manager.setDefault;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            manager.initBindings();
        });
    } else {
        manager.initBindings();
    }
})();
