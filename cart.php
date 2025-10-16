<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection is available
if (!$db) {
    die("Error: Database connection not available. Please check your database configuration.");
}

// Include cart class
require_once 'classes/Cart.php';

// Initialize cart
$cart = new Cart($db);

// Handle POST requests for cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($product_id > 0) {
                $result = $cart->addToCart($product_id, $quantity);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            }
            exit;

        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);

            if ($product_id > 0 && $quantity >= 0) {
                $result = $cart->updateQuantity($product_id, $quantity);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit;

        case 'remove':
            $product_id = (int)($_POST['product_id'] ?? 0);

            if ($product_id > 0) {
                $result = $cart->removeFromCart($product_id);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            }
            exit;

        case 'clear':
            $result = $cart->clearCart();
            echo json_encode($result);
            exit;
    }
}

// Get cart items for display
$cart_items = $cart->getCartItems();
$cart_total = $cart->getTotal();
$cart_count = $cart->getItemCount();
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
	<link rel="stylesheet" href="assets/sass/style.css">
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
								<span class="cart-count"><?php echo $cart_count; ?></span>
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
                            <tbody>
                                <tr class="cart__area-table-item">
                                    <td><span class="title">Image</span>
                                        <a class="cart__area-table-item-product" href="product-details.html"><img src="assets/img/products/products-11.jpg" alt=""></a>
                                    </td>
                                    <td class="cart__area-table-item-name"><span class="title">Product Name</span><a href="product-details.html">Face cream</a></td>
                                    <td class="cart__area-table-item-price"><span class="title">Price</span><span>$18.08</span></td>
                                    <td><span class="title">Quantity</span>
                                        <div class="cart__area-table-item-product-qty-select">
                                            <div class="cart__area-table-item-product-qty-select-cart-plus-minus"><input type="text" value="1">
                                                <div class="dec qtybutton">-</div>
                                                <div class="inc qtybutton">+</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart__area-table-item-total"><span class="title">Total</span><span>$18.08</span></td>
                                    <td class="cart__area-table-item-remove"><span class="title">Remove</span><a href="#"><i class="fal fa-trash-alt"></i></a></td>
                                </tr>
                                <tr class="cart__area-table-item">
                                    <td><span class="title">Image</span>
                                        <a class="cart__area-table-item-product" href="product-details.html"><img src="assets/img/products/products-7.jpg" alt=""></a>
                                    </td>
                                    <td class="cart__area-table-item-name"><span class="title">Product Name</span><a href="product-details.html">Face cream</a></td>
                                    <td class="cart__area-table-item-price"><span class="title">Price</span><span>$37.08</span></td>
                                    <td><span class="title">Quantity</span>
                                        <div class="cart__area-table-item-product-qty-select">
                                            <div class="cart__area-table-item-product-qty-select-cart-plus-minus"><input type="text" value="1">
                                                <div class="dec qtybutton">-</div>
                                                <div class="inc qtybutton">+</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart__area-table-item-total"><span class="title">Total</span><span>$37.08</span></td>
                                    <td class="cart__area-table-item-remove"><span class="title">Remove</span><a href="#"><i class="fal fa-trash-alt"></i></a></td>
                                </tr>
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
			       <li>Subtotal<span class="cart-subtotal">$78</span></li>
			       <li>Total<span class="cart-total">$78</span></li>
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
	<!-- Cart JS -->
	<script src="assets/js/cart.js"></script>
	<script>
		function redirectToCheckout() {
			console.log('redirectToCheckout() function called - redirecting now');
			alert('Redirecting to checkout.php');

			// Force redirect with multiple methods
			console.log('Setting location.href to checkout.php');
			window.location.href = 'checkout.php';

			// Backup redirect after a short delay
			setTimeout(function() {
				console.log('Backup redirect triggered');
				window.location.replace('checkout.php');
			}, 100);

			console.log('Location set, should redirect now');
			return false;
		}

		// Override any other click handlers that might interfere
		document.addEventListener('click', function(e) {
			if (e.target.id === 'checkout-btn' || e.target.closest('#checkout-btn')) {
				console.log('Checkout button clicked via global listener');
				e.preventDefault();
				e.stopImmediatePropagation();

				// Force redirect immediately
				console.log('Forcing redirect to checkout.php');
				window.location.href = 'http://localhost/barbex/checkout.php';
				return false;
			}
		}, true); // Use capture phase
	</script>

</body>

</html>