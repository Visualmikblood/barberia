// FINAL WORKING CART - NO CACHE ISSUES
console.log('=== CART_FINAL.JS LOADED SUCCESSFULLY ===');

class ShoppingCart {
    constructor() {
        this.cart = [];
        this.storageKey = 'barbex_cart_final';
        console.log('=== CART FINAL INITIALIZED ===');
        this.init();
    }

    init() {
        console.log('Loading final cart...');
        this.loadCart();
        this.updateCartCount();
        // this.loadFromDatabase(); // Cargar datos de BD al inicializar - comentado para evitar conflictos
        console.log('Final cart ready with', this.cart.length, 'items');
    }

    addToCart(product) {
        console.log('=== ADDING TO FINAL CART ===');
        console.log('Product ID:', product.id, 'Name:', product.name);

        const existing = this.cart.find(item => item.id === product.id);
        if (existing) {
            existing.quantity += 1;
            console.log('Updated quantity to:', existing.quantity);
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                quantity: 1
            });
            console.log('Added new product to final cart');
        }

        this.saveCart();
        this.updateCartCount();
        this.showNotification('✅ Producto agregado!');

        // Sincronizar con base de datos
        this.syncWithDatabase(product.id, 1);

        console.log('=== FINAL CART NOW HAS', this.cart.length, 'ITEMS ===');
        return { success: true };
    }

    loadCart() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            this.cart = saved ? JSON.parse(saved) : [];
            console.log('Loaded', this.cart.length, 'items from final cart');
        } catch (error) {
            this.cart = [];
            console.error('Error loading final cart:', error);
        }
    }

    saveCart() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
            console.log('Saved final cart with', this.cart.length, 'items');
        } catch (error) {
            console.error('Error saving final cart:', error);
        }
    }

    getItemCount() {
        return this.cart.reduce((total, item) => total + item.quantity, 0);
    }

    updateCartCount() {
        const count = this.getItemCount();
        console.log('=== UPDATING FINAL CART COUNT TO:', count, '===');

        const elements = document.querySelectorAll('.cart-count');
        console.log('Found', elements.length, 'cart count elements');

        elements.forEach((el, i) => {
            el.textContent = count;
            console.log('Updated element', i, 'to count:', count);

            if (count > 0) {
                el.classList.add('cart-count-visible');
            } else {
                el.classList.remove('cart-count-visible');
            }
        });
    }

    syncWithDatabase(productId, quantity) {
        console.log('=== SYNCING WITH DATABASE ===');
        console.log('Product ID:', productId, 'Quantity:', quantity);

        fetch('/api/cart.php?action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include', // IMPORTANTE: Enviar cookies de sesión
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Database sync result:', data);
            if (data.success) {
                console.log('✅ Product synced with database');
            } else {
                console.error('❌ Database sync failed:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Database sync error:', error);
        });
    }

    loadFromDatabase() {
        console.log('=== LOADING CART FROM DATABASE ===');

        fetch('/api/cart.php?action=get', {
            credentials: 'include' // IMPORTANTE: Enviar cookies de sesión
        })
        .then(response => response.json())
        .then(data => {
            console.log('Database cart data:', data);
            if (data.success && data.data && data.data.items) {
                // Sincronizar localStorage con datos de BD
                this.cart = data.data.items.map(item => ({
                    id: item.product_id,
                    name: item.name,
                    price: parseFloat(item.price),
                    image: item.image,
                    quantity: item.quantity
                }));
                this.saveCart();
                this.updateCartCount();
                console.log('✅ Cart loaded from database:', this.cart.length, 'items');
            }
        })
        .catch(error => {
            console.error('❌ Error loading cart from database:', error);
        });
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            animation: slideIn 0.4s ease-out;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.4s ease-out';
            setTimeout(() => notification.remove(), 400);
        }, 3000);
    }
}

// Initialize when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, creating final cart...');
    window.cart = new ShoppingCart();
    console.log('Final cart created successfully!');
});

// Click handler for add to cart buttons
document.addEventListener('click', function(e) {
    console.log('=== CLICK DETECTED ON FINAL CART ===');

    const btn = e.target.closest('.add-to-cart-btn');
    if (btn) {
        console.log('=== ADD TO CART BUTTON CLICKED ===');
        e.preventDefault();

        const productId = btn.getAttribute('data-product-id');
        const productName = btn.getAttribute('data-product-name');
        const productPrice = btn.getAttribute('data-product-price');
        const productImage = btn.getAttribute('data-product-image');

        console.log('=== EXTRACTED PRODUCT DATA ===');
        console.log('ID:', productId, 'Name:', productName, 'Price:', productPrice);

        if (productId && window.cart) {
            const product = {
                id: parseInt(productId),
                name: productName || 'Producto',
                price: parseFloat(productPrice) || 0,
                image: productImage || 'assets/img/products/products-1.jpg'
            };

            console.log('=== CALLING FINAL CART.ADDTOCART ===');
            window.cart.addToCart(product);
            console.log('=== PRODUCT ADDED TO FINAL CART ===');
        }
    }
});

// Add animations
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

console.log('=== CART_FINAL.JS SETUP COMPLETE ===');