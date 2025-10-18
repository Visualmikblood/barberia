// Ultra-simple cart using only localStorage
console.log('=== LOADING CART_V4.JS FILE ===');

class ShoppingCart {
    constructor() {
        this.cart = [];
        this.storageKey = 'barbex_cart_v4'; // Changed key to force fresh start
        console.log('=== NEW SIMPLE CART V4 INITIALIZED ===');
        this.init();
    }

    init() {
        console.log('Loading cart from localStorage...');
        this.loadCart();
        this.updateCartCount();
        console.log('Cart initialization complete. Items:', this.cart.length);
    }

    addToCart(product) {
        console.log('=== ADD TO CART CALLED ===');
        console.log('Product:', product);

        const existingProduct = this.cart.find(item => item.id === product.id);
        if (existingProduct) {
            existingProduct.quantity += (product.quantity || 1);
            console.log('Updated quantity to:', existingProduct.quantity);
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                quantity: product.quantity || 1
            });
            console.log('Added new product');
        }

        this.saveCart();
        this.updateCartCount();
        this.showNotification('Producto agregado al carrito');
        console.log('=== CART NOW HAS', this.cart.length, 'ITEMS ===');
        return { success: true, message: 'Producto agregado al carrito' };
    }

    loadCart() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                this.cart = JSON.parse(saved);
                console.log('Loaded cart with', this.cart.length, 'items');
            } else {
                this.cart = [];
                console.log('No saved cart found, starting empty');
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            this.cart = [];
        }
    }

    saveCart() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
            console.log('Saved cart with', this.cart.length, 'items');
        } catch (error) {
            console.error('Error saving cart:', error);
        }
    }

    getItemCount() {
        return this.cart.reduce((count, item) => count + item.quantity, 0);
    }

    updateCartCount() {
        const count = this.getItemCount();
        console.log('=== UPDATING CART COUNT TO:', count, '===');

        const cartCountElements = document.querySelectorAll('.cart-count');
        console.log('Found', cartCountElements.length, 'cart count elements');

        cartCountElements.forEach((element, index) => {
            element.textContent = count;
            console.log('Element', index, 'updated to:', count);

            // Force show/hide
            if (count > 0) {
                element.style.display = 'inline-flex';
                element.style.alignItems = 'center';
                element.style.justifyContent = 'center';
                element.style.visibility = 'visible';
                element.style.opacity = '1';
                element.style.background = 'red';
                element.style.color = 'white';
                element.style.borderRadius = '50%';
                element.style.width = '20px';
                element.style.height = '20px';
                element.style.fontSize = '12px';
                element.style.fontWeight = 'bold';
            } else {
                element.style.display = 'none';
            }
        });
    }

    showNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, creating simple cart V4...');
    window.cart = new ShoppingCart();
    console.log('Simple cart V4 created and initialized');
});

// Ultra-simple click handler
document.addEventListener('click', function(e) {
    console.log('=== GLOBAL CLICK DETECTED ===');
    console.log('Target:', e.target);
    console.log('Target classes:', e.target.className);

    // Look for add-to-cart-btn class (most specific)
    const addToCartBtn = e.target.closest('.add-to-cart-btn');

    if (addToCartBtn) {
        console.log('=== ADD TO CART BUTTON FOUND ===');
        e.preventDefault();

        // Get product data
        const productId = addToCartBtn.getAttribute('data-product-id');
        const productName = addToCartBtn.getAttribute('data-product-name');
        const productPrice = addToCartBtn.getAttribute('data-product-price');
        const productImage = addToCartBtn.getAttribute('data-product-image');

        console.log('=== PRODUCT DATA EXTRACTED ===');
        console.log('ID:', productId);
        console.log('Name:', productName);
        console.log('Price:', productPrice);
        console.log('Image:', productImage);
        console.log('Cart exists:', !!window.cart);

        if (productId && window.cart) {
            const product = {
                id: parseInt(productId),
                name: productName || 'Producto',
                price: parseFloat(productPrice) || 0,
                image: productImage || 'assets/img/products/products-1.jpg',
                quantity: 1
            };

            console.log('=== CALLING CART.ADDTOCART ===');
            const result = window.cart.addToCart(product);
            console.log('=== RESULT:', result, '===');
        } else {
            console.log('=== ERROR: Missing data ===');
        }
    } else {
        console.log('No add-to-cart-btn found');
    }
});

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);