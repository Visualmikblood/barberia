<?php
// No necesitamos PHP para el carrito, usaremos JavaScript con localStorage
// como en product-page.php para mantener consistencia
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Start Meta -->
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="description" content="BarbeX - Hair Salon HTML5 Template"/>
	<meta name="keywords" content="Creative, Digital, multipage, landing, freelancer template"/>
	<meta name="author" content="ThemeOri">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Title of Site -->
	<title>Cart - BarbeX Hair Salon</title>
	<!-- Favicons -->
	<link rel="icon" type="image/png" href="assets/img/favicon.png">
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<!-- font awesome -->
	<link rel="stylesheet" href="assets/css/all.css">
	<!-- Animate CSS -->
	<link rel="stylesheet" href="assets/css/animate.css">
	<!-- Swiper -->
	<link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
	<!-- Magnific -->
	<link rel="stylesheet" href="assets/css/magnific-popup.css">
	<!-- Mean menu -->
	<link rel="stylesheet" href="assets/css/meanmenu.min.css">
	<!-- Custom CSS -->
	<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
	<!-- Preloader start -->
	<div class="theme-loader">
		<div class="spinner">
			<div class="double-bounce1"></div>
			<div class="double-bounce2"></div>
		</div>
	</div>
	<!-- Preloader end -->
	<!-- Header Area Start -->
	<div class="header__sticky">
		<div class="header__area two">
			<div class="container custom__container">
				<div class="header__area-menubar">
					<div class="header__area-menubar-left">
						<div class="header__area-menubar-left-logo">
							<a href="index.php"><img src="assets/img/logo-2.png" alt=""></a>
							<div class="responsive-menu"></div>
						</div>
					</div>
					<div class="header__area-menubar-right two">
						<div class="header__area-menubar-right-menu menu-responsive">
							<ul id="mobilemenu">
								<li class="menu-item-has-children"><a href="#">Home</a>
									<ul class="sub-menu">
										<li><a href="index.html">Home 01</a></li>
										<li><a href="index-2.html">Home 02</a></li>
										<li><a href="index-3.html">Home 03</a></li>
									</ul>
								</li>
								<li class="menu-item-has-children"><a href="#">Pages</a>
									<ul class="sub-menu">
										<li><a href="about.html">About</a></li>
										<li><a href="price.html">Price</a></li>
										<li><a href="team.html">Team</a></li>
										<li><a href="services.html">Services</a></li>
										<li><a href="services-details.html">Services Details</a></li>
									</ul>
								</li>
								<li class="menu-item-has-children"><a href="#">Shop</a>
									<ul class="sub-menu">
										<li><a href="product-page.php">Product Page</a></li>
										<li><a href="product-details.php">Product Details</a></li>
										<li><a href="cart.php">Cart</a></li>
										<li><a href="checkout.php">Checkout</a></li>
									</ul>
								</li>
								<li class="menu-item-has-children"><a href="#">Blog</a>
									<ul class="sub-menu">
										<li><a href="blog-grid.html">Blog Grid</a></li>
										<li><a href="blog-standard.html">Blog Standard</a></li>
										<li><a href="blog-details.html">Blog Details</a></li>
									</ul>
								</li>
								<li><a href="contact.html">Contact</a></li>
							</ul>
						</div>
					</div>
					<div class="header__area-menubar-right-box">
						<div class="header__area-menubar-right-box-search">
							<div class="search">
								<span class="header__area-menubar-right-box-search-icon two open"><i class="fal fa-search"></i></span>
							</div>
							<div class="header__area-menubar-right-box-search-box">
								<form>
									<input type="search" placeholder="Search Here.....">
									<button type="submit"><i class="fal fa-search"></i>
									</button>
								</form> <span class="header__area-menubar-right-box-search-box-icon"><i class="fal fa-times"></i></span>
							</div>
						</div>
						<div class="header__area-menubar-right-box-cart">
<a href="cart.php" class="header__area-menubar-right-box-cart-link">
<i class="fal fa-shopping-cart"></i>
<span class="cart-count">0</span>
</a>
</div>
						<div class="header__area-menubar-right-box-btn">
							<a href="login.php" class="theme-border-btn">Login<i class="far fa-angle-double-right"></i></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Header Area End -->
    <!-- Page Banner Start -->
    <div class="page__banner" data-background="assets/img/bg/page.jpg">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page__banner-title">
                        <h1>Cart</h1>
                        <div class="page__banner-title-menu">
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><span>_</span>Cart</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Banner End -->
    <!-- Cart Area Start -->
    <div class="cart__area section-padding">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <form class="cart__area-form">
                        <table class="cart__area-table">
                            <thead>
                                <tr>
                                    <th class="cart-col-image">Image</th>
                                    <th class="cart-col-productname">Product Name</th>
                                    <th class="cart-col-price">Price</th>
                                    <th class="cart-col-quantity">Quantity</th>
                                    <th class="cart-col-total">Total</th>
                                    <th class="cart-col-remove">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <!-- Cart items will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </form>
                    <div class="cart__area-coupon">
                        <div class="cart__area-coupon-left">
                            <input type="text" class="form-control" placeholder="Coupon Code...">
                            <button class="theme-btn" type="submit">Apply Coupon</button>
                        </div>
                        <div class="cart__area-coupon-right">
                            <button class="theme-btn" type="submit">Update Cart</button>
                        </div>
                    </div>
                </div>
            </div>
			<div class="row justify-content-end">
				<div class="col-xl-3 col-lg-4">
			                 <div class="all__sidebar mt-45">
			                     <div class="all__sidebar-item">
			                         <h5>Cart Total</h5>
			                         <div class="all__sidebar-item-cart">
			                             <ul>
			                               <li>Subtotal<span class="cart-subtotal">$0.00</span></li>
			                               <li>Total<span class="cart-total">$0.00</span></li>
			                             </ul>
			                         </div>
			    <a href="checkout.php" class="theme-btn" id="checkout-btn">CheckOut</a>
			                     </div>
			                 </div>
				</div>
			</div>
        </div>
    </div>
    <!-- Cart Area End -->
	<!-- Newsletter Area Start -->
    <div class="newsletter__area">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-7 col-lg-7 lg-mb-30">
                    <div class="newsletter__area-left">
                        <h2>Subscribe Our Newsletter</h2>
                    </div>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="newsletter__area-right">
						<form action="#">
							<input type="text" placeholder="Email Address">
							<button type="submit"><i class="fal fa-hand-pointer"></i></button>
						</form>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<!-- Newsletter Area End -->
	<!-- Footer Two Start -->
	<div class="footer__two">
		<div class="footer__area-shape">
			<img src="assets/img/shape/foorer.png" alt="">
		</div>
		<div class="container">
			<div class="row">
				<div class="col-xl-3 col-lg-3 col-md-4 col-sm-8 sm-mb-30">
					<div class="footer__two-widget">
						<div class="footer__two-widget-logo">
							<a href="index.php"><img src="assets/img/logo.png" alt=""></a>
						</div>
                        <p>Phasellus vitae purus ac urna consequat facilisis a vel leo.</p>
					</div>
				</div>
				<div class="col-xl-3 col-lg-2 col-md-3 col-sm-4 lg-mb-30">
					<div class="footer__two-widget pl-25 xl-pl-0">
						<h5>Services</h5>
                        <div class="footer__two-widget-menu">
                            <ul>
                                <li><a href="services-details.php">Trend Haircut</a></li>
                                <li><a href="services-details.php">Hair Washing</a></li>
                                <li><a href="services-details.php">Hair Coloring</a></li>
                                <li><a href="services-details.php">Facial hair Trim</a></li>
                            </ul>
                        </div>
					</div>
				</div>
				<div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 sm-mb-30">
					<div class="footer__two-widget pl-10">
						<h5>Contact Us</h5>
						<div class="footer__two-widget-contact">
							<div class="footer__two-widget-contact-item">
								<div class="footer__two-widget-contact-item-icon">
									<i class="fal fa-map-marker-alt"></i>
								</div>
								<div class="footer__two-widget-contact-item-content">
									<h6><a href="#">PV3M+X68 Welshpool United Kingdom</a></h6>
								</div>
							</div>
							<div class="footer__two-widget-contact-item">
								<div class="footer__two-widget-contact-item-icon">
									<i class="fal fa-phone-alt"></i>
								</div>
								<div class="footer__two-widget-contact-item-content">
									<h6><a href="tel:+125(895)658568">+125 (895) 658 568</a></h6>
								</div>
							</div>
							<div class="footer__two-widget-contact-item">
								<div class="footer__two-widget-contact-item-icon">
									<i class="fal fa-envelope-open-text"></i>
								</div>
								<div class="footer__two-widget-contact-item-content">
									<h6><a href="mailto:info.help@gmail.com">info.help@gmail.com</a></h6>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-lg-3 col-md-5 col-sm-6">
					<div class="footer__two-widget last">
						<h5>Follow Us</h5>
						<div class="footer__two-widget-follow">
                            <ul>
								<li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
								<li><a href="#"><i class="fab fa-twitter"></i></a></li>
								<li><a href="#"><i class="fab fa-snapchat"></i></a></li>
								<li><a href="#"><i class="fab fa-pinterest-p"></i></a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="copyright__two">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-xl-12">
						<div class="copyright__two-center">
							<p>Copyright © 2022<a href="index.php"> ThemeOri</a> Website by Barbex</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Footer Two End -->
	<!-- Scroll Btn Start -->
	<div class="scroll-up">
		<svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102"><path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" /> </svg>
	</div>
	<!-- Scroll Btn End -->
	<!-- Main JS -->
	<script src="assets/js/jquery-3.6.0.min.js"></script>
	<!-- Bootstrap JS -->
	<script src="assets/js/bootstrap.min.js"></script>
	<!-- Counter up -->
	<script src="assets/js/jquery.counterup.min.js"></script>
	<!-- Popper JS -->
	<script src="assets/js/popper.min.js"></script>
	<!-- Magnific JS -->
	<script src="assets/js/jquery.magnific-popup.min.js"></script>
	<!-- Swiper JS -->
	<script src="assets/js/swiper-bundle.min.js"></script>
	<!-- Waypoints JS -->
	<script src="assets/js/jquery.waypoints.min.js"></script>
	<!-- Mean menu -->
	<script src="assets/js/jquery.meanmenu.min.js"></script>
	<!-- Custom JS -->
	<script src="assets/js/custom.js"></script>
	<!-- Cart JavaScript - Consistent with product-page.php -->
	<script>
		console.log('=== CART PAGE LOADED ===');

		class CartPage {
			constructor() {
				this.cart = [];
				this.storageKey = 'barbex_final_cart';
				console.log('=== CART PAGE INITIALIZED ===');
				this.init();
			}

			init() {
				console.log('Loading cart page...');
				this.loadCart();
				console.log('Cart loaded:', this.cart);

				// Clear any old cart data that might be conflicting
				const oldKeys = ['cart', 'barbex_cart', 'shopping_cart'];
				oldKeys.forEach(key => {
					if (localStorage.getItem(key)) {
						console.log('Removing old cart data from key:', key);
						localStorage.removeItem(key);
					}
				});

				this.renderCart();
				this.updateCartCount();
				this.bindEvents();
				console.log('Cart page ready');
			}

			loadCart() {
				try {
					const saved = localStorage.getItem(this.storageKey);
					this.cart = saved ? JSON.parse(saved) : [];
					console.log('Loaded', this.cart.length, 'items from cart');
				} catch (error) {
					this.cart = [];
					console.error('Error loading cart:', error);
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

			renderCart() {
				const tbody = document.getElementById('cart-items');
				if (!tbody) {
					console.log('Cart items tbody not found');
					return;
				}

				console.log('Rendering cart with', this.cart.length, 'items');

				if (this.cart.length === 0) {
					tbody.innerHTML = `
						<tr>
							<td colspan="6" style="text-align: center; padding: 50px;">
								<h4>Your cart is empty</h4>
								<p>Add some products to get started!</p>
								<a href="product-page.php" class="theme-btn">Continue Shopping</a>
							</td>
						</tr>
					`;
					this.updateTotals(0, 0);
					return;
				}

				let html = '';
				let subtotal = 0;

				this.cart.forEach((item, index) => {
					const total = item.price * item.quantity;
					subtotal += total;

					console.log('Rendering item:', item.name, 'qty:', item.quantity);

					html += `
						<tr class="cart__area-table-item">
							<td><span class="title">Image</span>
								<a class="cart__area-table-item-product" href="product-details.php?id=${item.id}">
									<img src="${item.image}" alt="${item.name}">
								</a>
							</td>
							<td class="cart__area-table-item-name">
								<span class="title">Product Name</span>
								<a href="product-details.php?id=${item.id}">${item.name}</a>
							</td>
							<td class="cart__area-table-item-price">
								<span class="title">Price</span>
								<span>$${item.price.toFixed(2)}</span>
							</td>
							<td><span class="title">Quantity</span>
								<div class="cart__area-table-item-product-qty-select">
									<div class="cart__area-table-item-product-qty-select-cart-plus-minus">
										<input type="text" value="${item.quantity}" data-index="${index}" class="qty-input">
										<div class="dec qtybutton" data-index="${index}">-</div>
										<div class="inc qtybutton" data-index="${index}">+</div>
									</div>
								</div>
							</td>
							<td class="cart__area-table-item-total">
								<span class="title">Total</span>
								<span>$${total.toFixed(2)}</span>
							</td>
							<td class="cart__area-table-item-remove">
								<span class="title">Remove</span>
								<a href="#" class="remove-item" data-index="${index}">
									<i class="fal fa-trash-alt"></i>
								</a>
							</td>
						</tr>
					`;
				});

				console.log('Generated HTML for', this.cart.length, 'items');

				tbody.innerHTML = html;
				console.log('Set tbody innerHTML');

				// Verify the HTML was applied
				const rows = tbody.querySelectorAll('tr');
				console.log('Number of table rows after render:', rows.length);

				// Check if cart items are visible
				const cartItems = tbody.querySelectorAll('.cart__area-table-item');
				console.log('Number of cart item rows:', cartItems.length);

				// Check computed styles
				if (cartItems.length > 0) {
					const firstItem = cartItems[0];
					const computedStyle = window.getComputedStyle(firstItem);
					console.log('First cart item display:', computedStyle.display);
					console.log('First cart item visibility:', computedStyle.visibility);
					console.log('First cart item opacity:', computedStyle.opacity);

					// Check if the table itself is visible
					const table = document.querySelector('.cart__area-table');
					if (table) {
						const tableStyle = window.getComputedStyle(table);
						console.log('Table display:', tableStyle.display);
						console.log('Table visibility:', tableStyle.visibility);
					}

					// Check parent containers
					let parent = firstItem.parentElement;
					let count = 0;
					while (parent && parent !== document.body && count < 5) {
						const parentStyle = window.getComputedStyle(parent);
						console.log(`Parent ${parent.tagName}.${parent.className || ''} display:`, parentStyle.display);
						console.log(`Parent ${parent.tagName}.${parent.className || ''} visibility:`, parentStyle.visibility);
						parent = parent.parentElement;
						count++;
					}
				}

				this.updateTotals(subtotal, subtotal); // No taxes/shipping for now
			}

			updateTotals(subtotal, total) {
				const subtotalEl = document.querySelector('.cart-subtotal');
				const totalEl = document.querySelector('.cart-total');

				if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
				if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
			}

			updateCartCount() {
				const count = this.cart.reduce((total, item) => total + item.quantity, 0);
				const elements = document.querySelectorAll('.cart-count');

				elements.forEach((el) => {
					el.textContent = count;
					if (count > 0) {
						el.classList.add('cart-count-visible');
					} else {
						el.classList.remove('cart-count-visible');
					}
				});
			}

			bindEvents() {
				// Quantity buttons
				document.addEventListener('click', (e) => {
					const target = e.target;

					if (target.classList.contains('inc')) {
						e.preventDefault();
						const index = parseInt(target.getAttribute('data-index'));
						console.log('Incrementing quantity for item', index);
						this.updateQuantity(index, this.cart[index].quantity + 1);
					} else if (target.classList.contains('dec')) {
						e.preventDefault();
						const index = parseInt(target.getAttribute('data-index'));
						console.log('Decrementing quantity for item', index);
						if (this.cart[index].quantity > 1) {
							this.updateQuantity(index, this.cart[index].quantity - 1);
						}
					} else if (target.classList.contains('remove-item') || target.closest('.remove-item')) {
						e.preventDefault();
						const removeBtn = target.closest('.remove-item');
						const index = parseInt(removeBtn.getAttribute('data-index'));
						console.log('Removing item', index);
						this.removeItem(index);
					}
				});

				// Quantity input changes
				document.addEventListener('change', (e) => {
					if (e.target.classList.contains('qty-input')) {
						const index = parseInt(e.target.getAttribute('data-index'));
						const newQty = parseInt(e.target.value) || 1;
						this.updateQuantity(index, Math.max(1, newQty));
					}
				});

				// Checkout button
				const checkoutBtn = document.getElementById('checkout-btn');
				if (checkoutBtn) {
					checkoutBtn.addEventListener('click', (e) => {
						e.preventDefault();
						if (this.cart.length === 0) {
							alert('Your cart is empty. Add some products first!');
							return;
						}
						window.location.href = 'checkout.php';
					});
				}
			}

			updateQuantity(index, newQuantity) {
				if (index >= 0 && index < this.cart.length) {
					console.log('Updating quantity for item', index, 'from', this.cart[index].quantity, 'to', newQuantity);
					this.cart[index].quantity = Math.max(1, newQuantity);
					this.saveCart();
					this.renderCart();
					this.updateCartCount();
					console.log('Quantity updated successfully');
				} else {
					console.log('Invalid index for quantity update:', index);
				}
			}

			removeItem(index) {
				if (index >= 0 && index < this.cart.length) {
					console.log('Removing item at index', index, ':', this.cart[index].name);
					this.cart.splice(index, 1);
					this.saveCart();
					this.renderCart();
					this.updateCartCount();
					console.log('Item removed successfully');
				} else {
					console.log('Invalid index for item removal:', index);
				}
			}
		}

		// Initialize when DOM loads
		document.addEventListener('DOMContentLoaded', function() {
			console.log('DOM loaded, creating cart page...');
			window.cartPage = new CartPage();
			console.log('Cart page created successfully!');
		});
	</script>

</body>

</html>