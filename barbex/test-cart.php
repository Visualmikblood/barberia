<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Cart - Working Version</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .product-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 10px;
            display: inline-block;
            width: 200px;
        }
        .cart-count {
            background: red;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Test Cart - Working Version</h1>

    <div style="margin-bottom: 20px;">
        <a href="cart.php">
            <i class="fas fa-shopping-cart"></i>
            Cart: <span class="cart-count">0</span>
        </a>
    </div>

    <div class="products">
        <div class="product-card">
            <h3>Test Product 1</h3>
            <p>$10.00</p>
            <a href="#" class="btn btn-primary add-to-cart-btn"
               data-product-id="1"
               data-product-name="Test Product 1"
               data-product-price="10.00"
               data-product-image="assets/img/products/products-1.jpg">
                Add to Cart
            </a>
        </div>

        <div class="product-card">
            <h3>Test Product 2</h3>
            <p>$15.00</p>
            <a href="#" class="btn btn-primary add-to-cart-btn"
               data-product-id="2"
               data-product-name="Test Product 2"
               data-product-price="15.00"
               data-product-image="assets/img/products/products-2.jpg">
                Add to Cart
            </a>
        </div>
    </div>

    <!-- Working Cart Script -->
    <script>
        console.log('=== TEST CART LOADED - WORKING VERSION ===');

        class TestCart {
            constructor() {
                this.cart = [];
                this.storageKey = 'test_cart_working';
                console.log('=== TEST CART INITIALIZED ===');
                this.init();
            }

            init() {
                console.log('Loading test cart...');
                this.loadCart();
                this.updateCartCount();
                console.log('Test cart ready with', this.cart.length, 'items');
            }

            addToCart(product) {
                console.log('=== ADDING TO TEST CART ===');
                console.log('Product:', product);

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
                    console.log('Added new product to test cart');
                }

                this.saveCart();
                this.updateCartCount();
                this.showNotification('✅ Producto agregado al carrito de prueba!');
                console.log('=== TEST CART NOW HAS', this.cart.length, 'ITEMS ===');
                return { success: true };
            }

            loadCart() {
                try {
                    const saved = localStorage.getItem(this.storageKey);
                    this.cart = saved ? JSON.parse(saved) : [];
                    console.log('Loaded', this.cart.length, 'items from test cart');
                } catch (error) {
                    this.cart = [];
                    console.error('Error loading test cart:', error);
                }
            }

            saveCart() {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
                    console.log('Saved test cart with', this.cart.length, 'items');
                } catch (error) {
                    console.error('Error saving test cart:', error);
                }
            }

            getItemCount() {
                return this.cart.reduce((total, item) => total + item.quantity, 0);
            }

            updateCartCount() {
                const count = this.getItemCount();
                console.log('=== UPDATING TEST CART COUNT TO:', count, '===');

                const elements = document.querySelectorAll('.cart-count');
                console.log('Found', elements.length, 'cart count elements');

                elements.forEach((el, i) => {
                    el.textContent = count;
                    console.log('Updated element', i, 'to count:', count);

                    if (count > 0) {
                        el.style.display = 'inline';
                    } else {
                        el.style.display = 'inline';
                    }
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
            console.log('DOM loaded, creating test cart...');
            window.cart = new TestCart();
            console.log('Test cart created successfully!');
        });

        // Click handler for add to cart buttons
        document.addEventListener('click', function(e) {
            console.log('=== TEST CLICK DETECTED ===');

            const btn = e.target.closest('.add-to-cart-btn');
            if (btn) {
                console.log('=== TEST ADD TO CART BUTTON CLICKED ===');
                e.preventDefault();

                const productId = btn.getAttribute('data-product-id');
                const productName = btn.getAttribute('data-product-name');
                const productPrice = btn.getAttribute('data-product-price');
                const productImage = btn.getAttribute('data-product-image');

                console.log('=== TEST EXTRACTED PRODUCT DATA ===');
                console.log('ID:', productId, 'Name:', productName, 'Price:', productPrice);

                if (productId && window.cart) {
                    const product = {
                        id: parseInt(productId),
                        name: productName || 'Producto',
                        price: parseFloat(productPrice) || 0,
                        image: productImage || 'assets/img/products/products-1.jpg'
                    };

                    console.log('=== TEST CALLING CART.ADDTOCART ===');
                    window.cart.addToCart(product);
                    console.log('=== TEST PRODUCT ADDED TO CART ===');
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

        console.log('=== TEST CART SETUP COMPLETE ===');
    </script>
</body>
</html>