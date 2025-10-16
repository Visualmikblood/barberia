// Cart functionality with PHP backend fallback
class ShoppingCart {
    constructor() {
        this.usePHP = this.detectPHP();
        this.cart = [];
        this.phpEndpoint = 'api/cart.php';
        this.init();
    }

    detectPHP() {
        // Auto-detect PHP availability
        return typeof window.phpEnabled !== 'undefined' && window.phpEnabled;
    }

    async init() {
        if (this.usePHP) {
            await this.loadFromPHP();
        } else {
            this.cart = JSON.parse(localStorage.getItem('barbex_cart')) || [];
        }
        this.updateCartCount();
    }

    // Add product to cart
    async addToCart(product) {
        if (this.usePHP) {
            return await this.addToCartPHP(product);
        } else {
            return this.addToCartLocal(product);
        }
    }

    addToCartLocal(product) {
        const existingProduct = this.cart.find(item => item.id === product.id);
        if (existingProduct) {
            existingProduct.quantity += product.quantity || 1;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity: product.quantity || 1
            });
        }
        this.saveCart();
        this.updateCartCount();
        this.showNotification('Producto agregado al carrito');
        return { success: true, message: 'Producto agregado al carrito' };
    }

    async addToCartPHP(product) {
        try {
            const response = await fetch(this.phpEndpoint + '?action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: product.id,
                    quantity: product.quantity || 1
                })
            });

            const result = await response.json();
            if (result.success) {
                await this.loadFromPHP();
                this.updateCartCount();
                this.showNotification(result.message);
            }
            return result;
        } catch (error) {
            console.error('Error adding to cart:', error);
            // Fallback to localStorage
            return this.addToCartLocal(product);
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

    // Save cart to localStorage
    saveCart() {
        if (!this.usePHP) {
            localStorage.setItem('barbex_cart', JSON.stringify(this.cart));
        }
    }

    // Load cart from PHP backend
    async loadFromPHP() {
        try {
            const response = await fetch(this.phpEndpoint);
            const result = await response.json();
            if (result.success) {
                // Convert PHP cart format to local format
                this.cart = result.data.items.map(item => ({
                    id: item.product_id,
                    name: item.name,
                    price: parseFloat(item.price),
                    image: item.image,
                    quantity: item.quantity
                }));
            }
        } catch (error) {
            console.error('Error loading cart from PHP:', error);
            // Fallback to localStorage
            this.cart = JSON.parse(localStorage.getItem('barbex_cart')) || [];
        }
    }

    // Update cart count display
    updateCartCount() {
        const cartCountElements = document.querySelectorAll('.cart-count');
        const count = this.getItemCount();
        cartCountElements.forEach(element => {
            element.textContent = count;
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
document.addEventListener('DOMContentLoaded', async function() {
    window.cart = new ShoppingCart();

    // Wait for cart to initialize
    await new Promise(resolve => setTimeout(resolve, 100));

    // Render cart if on cart page
    if (document.querySelector('.cart__area-table')) {
        window.cart.renderCart();
    }

    // Render checkout order if on checkout page
    if (document.querySelector('.checkout-order-items')) {
        window.cart.renderCheckoutOrder();
    }

    // Handle checkout form submission
    const checkoutForm = document.querySelector('form[action="#"]');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            console.log('Checkout form submitted'); // Debug

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Procesando...';
            submitBtn.disabled = true;

            try {
                // Get form data
                const formData = new FormData(this);
                const checkoutData = {
                    first_name: formData.get('first_name'),
                    last_name: formData.get('last_name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    address: formData.get('address'),
                    city: formData.get('city'),
                    state: formData.get('state'),
                    postcode: formData.get('postcode'),
                    country: formData.get('country'),
                    notes: formData.get('notes') || ''
                };

                console.log('Form data collected:', checkoutData); // Debug

                const result = await window.cart.processCheckout(checkoutData);
                console.log('Checkout result:', result); // Debug

                if (result.success) {
                    // Show success message and redirect
                    alert(`¡Pedido realizado exitosamente!\nNúmero de orden: ${result.order_number}\nTotal: $${result.total.toFixed(2)}`);
                    window.location.href = 'index.html';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert('Error al procesar el pedido. Inténtalo de nuevo.');
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }
});

// Test if JavaScript is loaded
console.log('Cart.js loaded successfully - Version 2.0');

// Test cart initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart...');
    if (window.cart) {
        console.log('Cart object exists:', window.cart);
    } else {
        console.log('Cart object not found');
    }
});

// Add to cart functionality for product buttons
document.addEventListener('click', function(e) {
    console.log('Global click detected on:', e.target.tagName, e.target.className); // Debug

    // Check for "Add to cart" button in product details page
    const isAddToCartButton = e.target.closest('button') &&
                             e.target.closest('button').textContent.toLowerCase().includes('add to cart');

    // Check for shopping basket icon in product page
    const isShoppingBasket = e.target.closest('a') &&
                            e.target.closest('a').querySelector('.fa-shopping-basket');

    if (isAddToCartButton || isShoppingBasket) {
        console.log('Add to cart button/link detected'); // Debug
        e.preventDefault();

        // Get product data from the clicked element or its parent
        let productCard;

        if (isAddToCartButton) {
            // Product details page
            productCard = e.target.closest('.product__details-title');
        } else {
            // Product page
            const basketLink = e.target.closest('a');
            productCard = basketLink.closest('.products__area-item');
        }

        console.log('Product card found:', !!productCard); // Debug

        if (productCard) {
            const img = productCard.querySelector('img');
            const nameElement = productCard.querySelector('h5 a') || productCard.querySelector('h3');
            const priceElement = productCard.querySelector('.products__area-item-content span') ||
                               productCard.querySelector('h4');

            const productData = {
                id: img ? img.src.split('/').pop().split('.')[0] : 'product-' + Date.now(),
                name: nameElement ? nameElement.textContent.trim() : 'Producto',
                price: priceElement ? parseFloat(priceElement.textContent.replace('$', '').replace(/<[^>]*>/g, '').trim()) : 56.00,
                image: img ? img.src : 'assets/img/products/products-1.jpg',
                quantity: 1
            };

            console.log('Product data extracted:', productData); // Debug

            if (window.cart) {
                window.cart.addToCart(productData);
            } else {
                console.error('Cart not initialized');
            }
        } else {
            console.log('No product card found'); // Debug
        }
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