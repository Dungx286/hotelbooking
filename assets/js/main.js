// Menu Toggle for Mobile
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.nav');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // Room Filter
    const filterButton = document.getElementById('filterButton');
    
    if (filterButton) {
        filterButton.addEventListener('click', filterRooms);
    }
    
    function filterRooms() {
        const typeFilter = document.getElementById('roomTypeFilter').value;
        const priceFilter = document.getElementById('priceFilter').value;
        const rooms = document.querySelectorAll('.room-card');
        
        rooms.forEach(room => {
            const roomPrice = parseInt(room.getAttribute('data-price'));
            const roomType = room.getAttribute('data-type');
            let showRoom = true;
            
            // Filter by room type
            if (typeFilter !== 'all' && roomType !== typeFilter) {
                showRoom = false;
            }
            
            // Filter by price
            if (priceFilter !== 'all') {
                const priceParts = priceFilter.split('-');
                
                if (priceParts.length === 2) {
                    const minPrice = parseInt(priceParts[0]);
                    const maxPrice = parseInt(priceParts[1]);
                    
                    if (roomPrice < minPrice || roomPrice > maxPrice) {
                        showRoom = false;
                    }
                } else if (priceParts[0] === '0') {
                    // Below price
                    const maxPrice = parseInt(priceParts[1]);
                    if (roomPrice > maxPrice) {
                        showRoom = false;
                    }
                } else if (priceParts[0].includes('+')) {
                    // Above price
                    const minPrice = parseInt(priceParts[0]);
                    if (roomPrice < minPrice) {
                        showRoom = false;
                    }
                }
            }
            
            // Show or hide room
            if (showRoom) {
                room.style.display = 'block';
            } else {
                room.style.display = 'none';
            }
        });
    }

    // Form validation for contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            let isValid = true;
            
            if (name === '') {
                showError('name', 'Vui lòng nhập họ tên');
                isValid = false;
            } else {
                removeError('name');
            }
            
            if (email === '') {
                showError('email', 'Vui lòng nhập email');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showError('email', 'Email không hợp lệ');
                isValid = false;
            } else {
                removeError('email');
            }
            
            if (subject === '') {
                showError('subject', 'Vui lòng nhập tiêu đề');
                isValid = false;
            } else {
                removeError('subject');
            }
            
            if (message === '') {
                showError('message', 'Vui lòng nhập nội dung');
                isValid = false;
            } else {
                removeError('message');
            }
            
            if (isValid) {
                // Submit form if valid
                this.submit();
            }
        });
    }

    function showError(id, message) {
        const inputElement = document.getElementById(id);
        let errorElement = inputElement.nextElementSibling;
        
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('div');
            errorElement.classList.add('error-message');
            errorElement.style.color = 'red';
            errorElement.style.fontSize = '0.8rem';
            errorElement.style.marginTop = '5px';
            inputElement.parentNode.insertBefore(errorElement, inputElement.nextSibling);
        }
        
        errorElement.textContent = message;
        inputElement.style.borderColor = 'red';
    }

    function removeError(id) {
        const inputElement = document.getElementById(id);
        const errorElement = inputElement.nextElementSibling;
        
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
        }
        
        inputElement.style.borderColor = '';
    }

    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Date validation for booking form
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    
    if (checkInInput && checkOutInput) {
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        checkInInput.setAttribute('min', today);
        
        checkInInput.addEventListener('change', function() {
            // Set check-out min date to check-in date
            checkOutInput.setAttribute('min', this.value);
            
            // If check-out date is before new check-in date, update it
            if (checkOutInput.value && checkOutInput.value < this.value) {
                checkOutInput.value = this.value;
            }
        });
    }
    
    // Profile tabs
    const profileTabs = document.querySelectorAll('.profile-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    if (profileTabs.length > 0) {
        profileTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                profileTabs.forEach(tab => tab.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to current tab and content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    }
});