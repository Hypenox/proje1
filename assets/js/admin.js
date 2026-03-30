/**
 * TechStore Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {

    // Sidebar Toggle
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');
    var sidebarClose = document.getElementById('sidebarClose');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Auto close alerts
    document.querySelectorAll('.admin-alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });

    // Confirm delete links
    document.querySelectorAll('a[onclick*="confirm"]').forEach(function(link) {
        // Already has onclick, no need for additional handler
    });

    // Image preview for file inputs
    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var preview = this.parentElement.querySelector('.image-preview');
            if (preview && this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Table row hover effect
    document.querySelectorAll('.admin-table tbody tr').forEach(function(row) {
        row.style.transition = 'background-color 0.2s ease';
    });

});
