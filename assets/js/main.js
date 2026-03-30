/**
 * TechStore - Ana JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Mobile Menu ----
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mainNav = document.getElementById('mainNav');
    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    }

    // Mobile dropdown toggle
    document.querySelectorAll('.has-dropdown > a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                this.parentElement.classList.toggle('open');
            }
        });
    });

    // ---- Hero Slider ----
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');
    let currentSlide = 0;
    let slideInterval;

    function showSlide(index) {
        if (slides.length === 0) return;
        slides.forEach(function(s) { s.classList.remove('active'); });
        dots.forEach(function(d) { d.classList.remove('active'); });
        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    function startSlider() {
        if (slides.length <= 1) return;
        slideInterval = setInterval(function() { showSlide(currentSlide + 1); }, 5000);
    }

    function stopSlider() { clearInterval(slideInterval); }

    if (prevBtn) prevBtn.addEventListener('click', function() { stopSlider(); showSlide(currentSlide - 1); startSlider(); });
    if (nextBtn) nextBtn.addEventListener('click', function() { stopSlider(); showSlide(currentSlide + 1); startSlider(); });
    dots.forEach(function(dot, i) {
        dot.addEventListener('click', function() { stopSlider(); showSlide(i); startSlider(); });
    });
    startSlider();

    // ---- Scroll Top ----
    const scrollTopBtn = document.getElementById('scrollTop');
    if (scrollTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        });
        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ---- Live Search ----
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                searchResults.classList.remove('active');
                searchResults.innerHTML = '';
                return;
            }
            searchTimeout = setTimeout(function() {
                fetch(SITE_URL + '/api/search.php?q=' + encodeURIComponent(query))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.products.length > 0) {
                            var html = '';
                            data.products.forEach(function(p) {
                                html += '<a href="' + SITE_URL + '/product.php?slug=' + p.slug + '" class="search-result-item">';
                                if (p.image) {
                                    html += '<img src="' + p.image + '" alt="">';
                                } else {
                                    html += '<div style="width:50px;height:50px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fas fa-laptop"></i></div>';
                                }
                                html += '<div class="search-result-info">';
                                html += '<strong>' + p.name + '</strong>';
                                html += '<span>' + p.price + '</span>';
                                html += '</div></a>';
                            });
                            searchResults.innerHTML = html;
                            searchResults.classList.add('active');
                        } else {
                            searchResults.innerHTML = '<div style="padding:16px;text-align:center;color:#999;">Sonuc bulunamadi</div>';
                            searchResults.classList.add('active');
                        }
                    })
                    .catch(function() {
                        searchResults.classList.remove('active');
                    });
            }, 300);
        });

        // Close search on click outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
    }

    // ---- Product Tabs ----
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = this.getAttribute('data-tab');
            if (!target) return; // skip if no data-tab (inline handler used)
            document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            var pane = document.getElementById(target);
            if (pane) pane.classList.add('active');
        });
    });

    // ---- Filter Sidebar Mobile ----
    var filterToggle = document.querySelector('.filter-toggle-btn');
    var filterSidebar = document.querySelector('.products-sidebar');
    var filterClose = document.querySelector('.filter-close-btn');

    if (filterToggle && filterSidebar) {
        filterToggle.addEventListener('click', function() {
            filterSidebar.classList.add('active');
        });
    }
    if (filterClose && filterSidebar) {
        filterClose.addEventListener('click', function() {
            filterSidebar.classList.remove('active');
        });
    }

    // ---- Form Validation ----
    var registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            var valid = true;
            clearErrors();

            var firstName = document.getElementById('first_name');
            var lastName = document.getElementById('last_name');
            var email = document.getElementById('email');
            var phone = document.getElementById('phone');
            var password = document.getElementById('password');
            var passwordConfirm = document.getElementById('password_confirm');

            if (firstName && !firstName.value.trim()) { showError('firstNameError', 'Isim zorunludur.'); valid = false; }
            if (lastName && !lastName.value.trim()) { showError('lastNameError', 'Soyisim zorunludur.'); valid = false; }
            if (email && !isValidEmail(email.value)) { showError('emailError', 'Gecerli bir e-posta girin.'); valid = false; }
            if (phone && !phone.value.trim()) { showError('phoneError', 'Telefon zorunludur.'); valid = false; }
            if (password && password.value.length < 6) { showError('passwordError', 'Sifre en az 6 karakter olmalidir.'); valid = false; }
            if (passwordConfirm && password && password.value !== passwordConfirm.value) { showError('passwordConfirmError', 'Sifreler eslesmiyor.'); valid = false; }

            if (!valid) e.preventDefault();
        });
    }

    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            var valid = true;
            clearErrors();

            var email = document.getElementById('email');
            var password = document.getElementById('password');

            if (email && !isValidEmail(email.value)) { showError('emailError', 'Gecerli bir e-posta girin.'); valid = false; }
            if (password && !password.value) { showError('passwordError', 'Sifre zorunludur.'); valid = false; }

            if (!valid) e.preventDefault();
        });
    }

    // ---- Alert Auto Close ----
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });

    // ---- Sorting ----
    var sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            var url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

});

// ---- Global Functions ----

// Add to cart
function addToCart(productId, quantity) {
    quantity = quantity || 1;
    var formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(SITE_URL + '/api/cart.php', {
        method: 'POST',
        body: formData,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            updateCartBadge(data.cart_count);
            showToast(data.message);
        } else {
            showToast(data.message || 'Bir hata olustu.', 'error');
        }
    })
    .catch(function() { showToast('Baglanti hatasi.', 'error'); });
}

// Update cart quantity
function updateCartQty(itemId, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 0) quantity = 0;

    var formData = new FormData();
    formData.append('action', 'update');
    formData.append('item_id', itemId);
    formData.append('quantity', quantity);

    fetch(SITE_URL + '/api/cart.php', {
        method: 'POST',
        body: formData,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            updateCartBadge(data.cart_count);
            if (quantity === 0) {
                var row = document.getElementById('cartRow-' + itemId);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(function() { row.remove(); checkEmptyCart(); }, 300);
                }
            } else {
                location.reload();
            }
        }
    })
    .catch(function() { showToast('Baglanti hatasi.', 'error'); });
}

// Remove from cart
function removeFromCart(itemId) {
    var formData = new FormData();
    formData.append('action', 'remove');
    formData.append('item_id', itemId);

    fetch(SITE_URL + '/api/cart.php', {
        method: 'POST',
        body: formData,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            updateCartBadge(data.cart_count);
            showToast(data.message);
            var row = document.getElementById('cartRow-' + itemId);
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(function() { row.remove(); checkEmptyCart(); }, 300);
            }
        }
    })
    .catch(function() { showToast('Baglanti hatasi.', 'error'); });
}

function checkEmptyCart() {
    var rows = document.querySelectorAll('.cart-row');
    if (rows.length === 0) {
        location.reload();
    }
}

// Update cart badge
function updateCartBadge(count) {
    var badges = document.querySelectorAll('.cart-badge');
    badges.forEach(function(badge) {
        badge.textContent = count;
        if (count > 0) {
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    });
}

// Toast notification
function showToast(message, type) {
    type = type || 'success';
    var existing = document.querySelector('.toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;

    document.body.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 50);
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 400);
    }, 3000);
}

// Toggle password visibility
function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var btn = input.parentElement.querySelector('.toggle-password i');
    if (input.type === 'password') {
        input.type = 'text';
        if (btn) { btn.classList.remove('fa-eye'); btn.classList.add('fa-eye-slash'); }
    } else {
        input.type = 'password';
        if (btn) { btn.classList.remove('fa-eye-slash'); btn.classList.add('fa-eye'); }
    }
}

// Form validation helpers
function showError(elementId, message) {
    var el = document.getElementById(elementId);
    if (el) { el.textContent = message; el.style.display = 'block'; }
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(function(el) {
        el.textContent = '';
        el.style.display = 'none';
    });
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Product detail quantity
function changeQty(delta) {
    var input = document.getElementById('productQty');
    if (!input) return;
    var newVal = parseInt(input.value) + delta;
    var max = parseInt(input.getAttribute('max')) || 999;
    if (newVal < 1) newVal = 1;
    if (newVal > max) newVal = max;
    input.value = newVal;
}
