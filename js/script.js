// js/script.js - Add to Cart functionality

console.log('🛒 Add to Cart script loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM loaded, attaching event listeners');
    
    // Add to cart functionality
    function initializeAddToCart() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        console.log(`Found ${addToCartButtons.length} Add to Cart buttons`);
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                
                console.log(`🛒 Adding product to cart: ${productName} (ID: ${productId})`);
                
                // Show loading state
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
                this.disabled = true;
                
                fetch('includes/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    // Reset button state
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                    
                    if (data.success) {
                        // Show success message
                        showNotification('"' + productName + '" added to cart!', 'success');
                        
                        // Update cart count
                        updateCartCount(data.cart_count);
                        console.log('✅ Cart count updated to:', data.cart_count);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                        console.error('❌ Error:', data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Fetch error:', error);
                    showNotification('Error adding product to cart', 'error');
                    
                    // Reset button state
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                });
            });
        });
    }
    
    // Initialize when page loads
    initializeAddToCart();
    
    // Re-initialize when products are loaded via AJAX
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                initializeAddToCart();
            }
        });
    });
    
    observer.observe(document.getElementById('featured-products'), {
        childList: true,
        subtree: true
    });
});

// Global functions
function showNotification(message, type) {
    console.log(`📢 Notification: ${message} (${type})`);
    
    // Remove existing notifications
    document.querySelectorAll('.alert.position-fixed').forEach(alert => alert.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

function updateCartCount(count) {
    console.log(`🔄 Updating cart count to: ${count}`);
    const cartBadge = document.querySelector('.navbar .badge');
    if (cartBadge) {
        cartBadge.textContent = count;
        console.log('✅ Cart badge updated');
    } else {
        console.log('❌ Cart badge not found');
    }
}


