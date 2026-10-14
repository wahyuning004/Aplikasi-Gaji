// Main JS for Aplikasi Gaji
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle for mobile & desktop
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.getElementById('sidebar-wrapper');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggle && sidebarWrapper) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            sidebarWrapper.classList.toggle('toggled');
            if (sidebarBackdrop) {
                sidebarBackdrop.classList.toggle('show');
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            sidebarWrapper.classList.remove('toggled');
            sidebarBackdrop.classList.remove('show');
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const flashAlerts = document.querySelectorAll('.alert-auto-dismiss');
    flashAlerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirmation for delete actions
    document.querySelectorAll('.btn-confirm-delete').forEach(function (button) {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Currency input mask helper (for rupiah inputs)
    document.querySelectorAll('.input-currency').forEach(function (input) {
        input.addEventListener('keyup', function (e) {
            let value = this.value.replace(/[^,\d]/g, '').toString();
            let split = value.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            this.value = rupiah;
        });
    });
});
