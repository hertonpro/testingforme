// ============================================================
// APP.JS — Testing Réseau VDI
// JavaScript minimal pour interactions mobile
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // --- SIDEBAR TOGGLE ---
    const menuBtn = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (sidebarOverlay) sidebarOverlay.classList.add('active');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    }

    if (menuBtn) menuBtn.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // --- PHOTO UPLOAD PREVIEW ---
    const dropZone = document.getElementById('dropZone');
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');

    if (dropZone && photoInput) {
        // Click to upload
        dropZone.addEventListener('click', function () {
            photoInput.click();
        });

        // File selected
        photoInput.addEventListener('change', function () {
            previewFiles(this.files);
        });

        // Drag and drop
        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', function () {
            this.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                photoInput.files = e.dataTransfer.files;
                previewFiles(e.dataTransfer.files);
            }
        });
    }

    function previewFiles(files) {
        if (!photoPreview) return;
        photoPreview.innerHTML = '';

        Array.from(files).forEach(function (file) {
            if (!file.type.startsWith('image/')) return;

            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Preview';
                img.loading = 'lazy';
                photoPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    // --- AUTO-DISMISS ALERTS ---
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            if (alert.parentNode) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(function () {
                    if (alert.parentNode) alert.remove();
                }, 300);
            }
        }, 5000);
    });

    // --- CONFIRM DANGEROUS ACTIONS ---
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

});
