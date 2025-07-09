document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle for Mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Confirm Actions
    const confirmActions = document.querySelectorAll('.confirm-action');
    
    confirmActions.forEach(action => {
        action.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện hành động này?')) {
                e.preventDefault();
            }
        });
    });
    
    // Form Validation
    const adminForms = document.querySelectorAll('.admin-form');
    
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    isValid = false;
                    showError(field, 'Trường này không được để trống');
                } else {
                    removeError(field);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    function showError(field, message) {
        removeError(field);
        
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.innerHTML = message;
        errorElement.style.color = 'red';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '5px';
        
        field.parentNode.appendChild(errorElement);
        field.style.borderColor = 'red';
    }
    
    function removeError(field) {
        const parent = field.parentNode;
        const errorElement = parent.querySelector('.error-message');
        
        if (errorElement) {
            parent.removeChild(errorElement);
        }
        
        field.style.borderColor = '';
    }
    
    // Date Range Picker (if available)
    const dateRange = document.querySelector('.date-range-picker');
    if (dateRange) {
        // Initialize date range picker if library available
        // This is just a placeholder - actual implementation depends on the library you use
    }
    
    // Chart initialization for reports
    const salesChart = document.getElementById('salesChart');
    const bookingChart = document.getElementById('bookingChart');
    
    if (salesChart) {
        // Initialize chart if library available (e.g., Chart.js)
        // This is just a placeholder - actual implementation depends on the chart library you use
    }
    
    // Image preview on file upload
    const imageInputs = document.querySelectorAll('.image-upload');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const preview = this.nextElementSibling;
            if (preview && preview.classList.contains('image-preview')) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            }
        });
    });
});