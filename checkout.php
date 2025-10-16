<?php
session_start();
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

// Get cart items
$cart_items = $cart->getCartItems();
$cart_total = $cart->getTotal();

// Allow checkout even if cart is empty for testing
// if (empty($cart_items)) {
//     header('Location: product-page.php');
//     exit;
// }

// Handle checkout form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address)) {
        $message = '<div class="alert alert-danger">Please fill in all required fields</div>';
    } else {
        // Create order
        $order_data = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'postcode' => $postcode,
            'notes' => $notes,
            'total' => $cart_total,
            'status' => 'pending',
            'items' => json_encode($cart_items)
        ];

        $query = "INSERT INTO orders (user_id, first_name, last_name, email, phone, address, city, postcode, notes, total, status, items, created_at) VALUES (:user_id, :first_name, :last_name, :email, :phone, :address, :city, :postcode, :notes, :total, :status, :items, NOW())";
        $stmt = $db->prepare($query);

        foreach ($order_data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if ($stmt->execute()) {
            $order_id = $db->lastInsertId();

            // Clear cart
            $cart->clearCart();

            // Redirect to success page
            header("Location: checkout-success.php?order_id=$order_id");
            exit;
        } else {
            $message = '<div class="alert alert-danger">Error processing order. Please try again.</div>';
        }
    }
}
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
	<title>Checkout - BarbeX Hair Salon</title>
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
								<span class="cart-count"><?php echo count($cart_items); ?></span>
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
                        <h1>Checkout</h1>
                        <div class="page__banner-title-menu">
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><span>_</span>Checkout</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Banner End -->
    <!-- Checkout Area Start -->
    <div class="checkout__area section-padding">
        <div class="container">
			<form action="checkout.php" method="POST">
            <div class="row">
				<div class="col-xl-8 col-lg-8 lg-mb-30">
					<div class="checkout__area-left">
						<div class="checkout__area-left-top">
							<span>Have a Coupon<a href="#">Check here to enter your code</a></span>
						</div>
					</div>
					<h4 class="pt-60 pb-60">Billing Details</h4>
					<?php echo $message; ?>
					<div class="checkout__area-left-form">
						<div class="row">
							<div class="col-md-6 mb-30">
								<div class="checkout__area-left-form-list">
									<label>First Name<span> *</span></label>
									<input type="text" name="first_name" placeholder="First Name" required>
								</div>
							</div>
							<div class="col-md-6 md-mb-30">
								<div class="checkout__area-left-form-list">
									<label>Last Name<span> *</span></label>
									<input type="text" name="last_name" placeholder="Last Name" required>
								</div>
							</div>
							<div class="col-md-12 mb-30">
								<div class="checkout__area-left-form-list">
									<label>Email Address<span> *</span></label>
									<input type="email" name="email" placeholder="Email address" required>
								</div>
							</div>
							<div class="col-md-12 mb-30">
								<div class="checkout__area-left-form-list">
									<label>Phone Number<span> *</span></label>
									<input type="text" name="phone" placeholder="Phone" required>
								</div>
							</div>
							<div class="col-md-12 mb-30">
								<div class="checkout__area-left-form-list">
									<label>Street Address<span> *</span></label>
									<input type="text" name="address" placeholder="House number and Street name" required>
								</div>
							</div>
							<div class="col-md-12 mb-30">
								<div class="checkout__area-left-form-list">
									<label>Town City<span> *</span></label>
									<input type="text" name="city" placeholder="Town City" required>
								</div>
							</div>
							<div class="col-md-12 mb-30">
								<div class="checkout__area-left-form-list">
									<label>Postcode / Zip<span> *</span></label>
									<input type="text" name="postcode" placeholder="Postcode / Zip" required>
								</div>
							</div>
							<div class="col-md-12 pt-60 pb-60">
								<h3>Additional Information</h3>
							</div>
							<div class="col-md-12">
								<div class="checkout__area-left-form-list">
									<textarea name="notes" placeholder="Notes about Your Order"></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-4 col-lg-4">
                    <div class="all__sidebar">
                        <div class="all__sidebar-item">
                            <h5>Your Order</h5>
                            <div class="all__sidebar-item-cart">
                                <ul>
									<?php foreach ($cart_items as $item): ?>
									<li><?php echo htmlspecialchars($item['name']); ?> X <?php echo $item['quantity']; ?><span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span></li>
									<?php endforeach; ?>
									<li>Total<span>$<?php echo number_format($cart_total, 2); ?></span></li>
                                </ul>
                            </div>
							<button class="theme-btn" type="submit">Place Order<i class="far fa-angle-double-right"></i></button>
                        </div>
                    </div>
				</div>
            </div>
			</form>
        </div>
    </div>
    <!-- Checkout Area End -->
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
</body>

</html>
