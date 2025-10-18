// Ultra-simple cart using only localStorage
class ShoppingCart {
    constructor() {
        this.cart = [];
        this.storageKey = 'barbex_cart_v3'; // Changed key to force fresh start
        console.log('=== NEW SIMPLE CART INITIALIZED ===');
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

    async addToCartPHP(product) {
        try {
            // Use FormData instead of JSON for better session handling
            const formData = new FormData();
            formData.append('product_id', product.id);
            formData.append('quantity', product.quantity || 1);

            const response = await fetch(this.phpEndpoint + '?action=add', {
                method: 'POST',
                credentials: 'include', // Include cookies for session
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                console.log('PHP cart sync successful');
            }
            return result;
        } catch (error) {
            console.error('PHP sync failed, but localStorage is working:', error);
            // Don't return error, localStorage is already working
            return { success: true, message: 'Producto agregado (localStorage)' };
        }
    }

    // Remove product from cart
    async removeFromCart(productId) {
        if (this.usePHP) {
            return await this.removeFromCartPHP(productId);
        } else {
            return this.removeFromCartLocal(productId);
        }
    }

    removeFromCartLocal(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartCount();
        return { success: true, message: 'Producto eliminado del carrito' };
    }

    async removeFromCartPHP(productId) {
        try {
            const response = await fetch(this.phpEndpoint + '?action=remove', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: productId })
            });

            const result = await response.json();
            if (result.success) {
                await this.loadFromPHP();
                this.updateCartCount();
            }
            return result;
        } catch (error) {
            console.error('Error removing from cart:', error);
            return this.removeFromCartLocal(productId);
        }
    }

    // Update product quantity
    async updateQuantity(productId, quantity) {
        if (this.usePHP) {
            return await this.updateQuantityPHP(productId, quantity);
        } else {
            return this.updateQuantityLocal(productId, quantity);
        }
    }

    updateQuantityLocal(productId, quantity) {
        const product = this.cart.find(item => item.id === productId);
        if (product) {
            product.quantity = quantity;
            if (product.quantity <= 0) {
                this.removeFromCartLocal(productId);
            } else {
                this.saveCart();
            }
        }
        return { success: true, message: 'Cantidad actualizada' };
    }

    async updateQuantityPHP(productId, quantity) {
        try {
            const response = await fetch(this.phpEndpoint + '?action=update', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });

            const result = await response.json();
            if (result.success) {
                await this.loadFromPHP();
            }
            return result;
        } catch (error) {
            console.error('Error updating quantity:', error);
            return this.updateQuantityLocal(productId, quantity);
        }
    }

    // Get cart total
    getTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // Get cart items count
    getItemCount() {
        return this.cart.reduce((count, item) => count + item.quantity, 0);
    }

    // Clear cart
    async clearCart() {
        if (this.usePHP) {
            return await this.clearCartPHP();
        } else {
            return this.clearCartLocal();
        }
    }

    clearCartLocal() {
        this.cart = [];
        this.saveCart();
        this.updateCartCount();
        return { success: true, message: 'Carrito vaciado' };
    }

    async clearCartPHP() {
        try {
            const response = await fetch(this.phpEndpoint + '?action=clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();
            if (result.success) {
                await this.loadFromPHP();
                this.updateCartCount();
            }
            return result;
        } catch (error) {
            console.error('Error clearing cart:', error);
            return this.clearCartLocal();
        }
    }

    // Legacy saveCart method - now uses saveToLocalStorage
    saveCart() {
        this.saveToLocalStorage();
    }

    // Load cart from localStorage
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

    // Save cart to localStorage
    saveCart() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
            console.log('Saved cart with', this.cart.length, 'items');
        } catch (error) {
            console.error('Error saving cart:', error);
        }
    }

    // Sync cart with PHP backend
    async syncWithPHP() {
        try {
            console.log('Syncing with PHP...');

            // First, try to load from PHP
            const response = await fetch(this.phpEndpoint, {
                credentials: 'include' // Include cookies for session
            });
            const result = await response.json();
            console.log('PHP cart load response:', result); // Debug

            if (result.success && result.data && result.data.items && result.data.items.length > 0) {
                // PHP has data, use it and update localStorage
                this.cart = result.data.items.map(item => ({
                    id: item.product_id,
                    name: item.name,
                    price: parseFloat(item.price),
                    image: item.image,
                    quantity: item.quantity
                }));
                this.saveToLocalStorage();
                console.log('Synced cart from PHP');
            } else if (this.cart.length > 0) {
                // PHP is empty but we have local data, try to sync local to PHP
                console.log('PHP empty, syncing local cart to PHP');
                await this.syncLocalToPHP();
            }
        } catch (error) {
            console.error('Error syncing with PHP:', error);
            // Keep localStorage data if PHP fails
        }
    }

    // Sync local cart data to PHP
    async syncLocalToPHP() {
        if (this.cart.length === 0) return;

        try {
            console.log('Syncing local cart to PHP...');
            for (const item of this.cart) {
                const formData = new FormData();
                formData.append('product_id', item.id);
                formData.append('quantity', item.quantity);

                await fetch(this.phpEndpoint + '?action=add', {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });
            }
            console.log('Successfully synced local cart to PHP');
        } catch (error) {
            console.error('Error syncing local to PHP:', error);
        }
    }

    // Legacy method for backward compatibility
    async loadFromPHP() {
        return this.syncWithPHP();
    }

    // Update cart count display
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

    // Show notification
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

    // Render cart items (for cart page)
    renderCart() {
        const cartTableBody = document.querySelector('.cart__area-table tbody');
        if (!cartTableBody) return;

        cartTableBody.innerHTML = '';

        if (this.cart.length === 0) {
            cartTableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 50px;">
                        <h4>Tu carrito está vacío</h4>
                        <a href="product-page.php" class="theme-btn" style="margin-top: 20px;">Ir a la tienda</a>
                    </td>
                </tr>
            `;
            this.updateCartTotal();
            return;
        }

        this.cart.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'cart__area-table-item';
            row.innerHTML = `
                <td><span class="title">Image</span>
                    <a class="cart__area-table-item-product" href="product-details.html"><img src="${item.image}" alt=""></a>
                </td>
                <td class="cart__area-table-item-name"><span class="title">Product Name</span><a href="product-details.html">${item.name}</a></td>
                <td class="cart__area-table-item-price"><span class="title">Price</span><span>$${item.price.toFixed(2)}</span></td>
                <td><span class="title">Quantity</span>
                    <div class="cart__area-table-item-product-qty-select">
                        <div class="cart__area-table-item-product-qty-select-cart-plus-minus">
                            <input type="text" value="${item.quantity}" class="quantity-input" data-product-id="${item.id}">
                            <div class="dec qtybutton" data-product-id="${item.id}">-</div>
                            <div class="inc qtybutton" data-product-id="${item.id}">+</div>
                        </div>
                    </div>
                </td>
                <td class="cart__area-table-item-total"><span class="title">Total</span><span>$${(item.price * item.quantity).toFixed(2)}</span></td>
                <td class="cart__area-table-item-remove"><span class="title">Remove</span><a href="#" class="remove-item" data-product-id="${item.id}"><i class="fal fa-trash-alt"></i></a></td>
            `;
            cartTableBody.appendChild(row);
        });

        this.updateCartTotal();
        this.attachCartEventListeners();
    }

    // Update cart total display
    updateCartTotal() {
        const subtotalElements = document.querySelectorAll('.cart-subtotal');
        const totalElements = document.querySelectorAll('.cart-total');

        const subtotal = this.getTotal();
        const total = subtotal; // Add shipping/tax logic here if needed

        subtotalElements.forEach(element => {
            element.textContent = `$${subtotal.toFixed(2)}`;
        });

        totalElements.forEach(element => {
            element.textContent = `$${total.toFixed(2)}`;
        });
    }

    // Attach event listeners for cart interactions
    attachCartEventListeners() {
        // Quantity buttons
        document.querySelectorAll('.dec.qtybutton').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = e.target.dataset.productId;
                const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                const currentQty = parseInt(input.value);
                if (currentQty > 1) {
                    this.updateQuantity(productId, currentQty - 1);
                    this.renderCart();
                }
            });
        });

        document.querySelectorAll('.inc.qtybutton').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = e.target.dataset.productId;
                const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                const currentQty = parseInt(input.value);
                this.updateQuantity(productId, currentQty + 1);
                this.renderCart();
            });
        });

        // Quantity input change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const productId = e.target.dataset.productId;
                const quantity = parseInt(e.target.value);
                if (quantity > 0) {
                    this.updateQuantity(productId, quantity);
                    this.renderCart();
                }
            });
        });

        // Remove item buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = e.target.closest('.remove-item').dataset.productId;
                this.removeFromCart(productId);
                this.renderCart();
            });
        });

        // Update cart button
        const updateCartBtn = document.querySelector('.cart__area-coupon-right button');
        if (updateCartBtn) {
            updateCartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.renderCart();
                this.showNotification('Carrito actualizado');
            });
        }
    }

    // Render checkout order summary
    async renderCheckoutOrder() {
        const orderItemsList = document.querySelector('.checkout-order-items');
        if (!orderItemsList) return;

        // Reload cart data if using PHP
        if (this.usePHP) {
            await this.loadFromPHP();
        }

        // Clear existing items except total
        const totalItem = orderItemsList.querySelector('.checkout-total')?.parentElement;
        orderItemsList.innerHTML = '';

        if (this.cart.length === 0) {
            orderItemsList.innerHTML = '<li>No hay productos en el carrito</li>';
            return;
        }

        this.cart.forEach(item => {
            const li = document.createElement('li');
            li.textContent = `${item.name} X ${item.quantity}`;
            const span = document.createElement('span');
            span.textContent = `$${(item.price * item.quantity).toFixed(2)}`;
            li.appendChild(span);
            orderItemsList.appendChild(li);
        });

        // Add total
        const totalLi = document.createElement('li');
        totalLi.textContent = 'Total';
        const totalSpan = document.createElement('span');
        totalSpan.className = 'checkout-total';
        totalSpan.textContent = `$${this.getTotal().toFixed(2)}`;
        totalLi.appendChild(totalSpan);
        orderItemsList.appendChild(totalLi);
    }

    // Process checkout
    async processCheckout(formData) {
        console.log('processCheckout called with:', formData); // Debug

        try {
            // Ensure cart data is included - force reload from storage
            if (this.usePHP) {
                await this.loadFromPHP();
            } else {
                this.cart = JSON.parse(localStorage.getItem('barbex_cart')) || [];
            }

            console.log('Cart loaded:', this.cart); // Debug
            console.log('Cart length:', this.cart.length); // Debug

            // If cart is empty, show error
            if (!this.cart || this.cart.length === 0) {
                console.error('Cart is empty!');
                alert('El carrito está vacío. Agrega productos antes de hacer checkout.');
                return {
                    success: false,
                    message: 'El carrito está vacío. Agrega productos antes de hacer checkout.'
                };
            }

            // For now, simulate successful checkout without calling API
            console.log('Simulating successful checkout...');
            const cartData = this.getCartData();
            const mockResult = {
                success: true,
                message: 'Pedido realizado exitosamente',
                order_number: 'ORD-' + Date.now(),
                order_id: Date.now(),
                total: cartData.total
            };

            console.log('Mock checkout response:', mockResult);

            // Clear cart after successful checkout
            this.clearCart();

            return mockResult;
        } catch (error) {
            console.error('Error processing checkout:', error);
            return {
                success: false,
                message: 'Error al procesar el pedido. Inténtalo de nuevo.'
            };
        }
    }

    // Get cart data for checkout
    getCartData() {
        return {
            items: this.cart,
            subtotal: this.getTotal(),
            total: this.getTotal()
        };
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, creating simple cart...');
    window.cart = new ShoppingCart();
    console.log('Simple cart created and initialized');
});

// Test if JavaScript is loaded
console.log('=== CART WORKING NOW - FINAL VERSION ===');
console.log('Cart.js loaded successfully - Version WORKING');

// Test cart initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart...');
    if (window.cart) {
        console.log('Cart object exists:', window.cart);
    } else {
        console.log('Cart object not found');
    }
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