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

// Debug logging removed - cart is working correctly

// Debug logging removed - cart is working correctly

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
    $payment_method = trim($_POST['payment_method'] ?? 'cash_on_delivery');
    $paypal_email = trim($_POST['paypal_email'] ?? '');
    $card_number = trim($_POST['card_number'] ?? '');
    $card_expiry = trim($_POST['card_expiry'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');
    $card_holder = trim($_POST['card_holder'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validate PayPal fields if PayPal is selected
    if ($payment_method === 'paypal') {
        if (empty($paypal_email) || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="alert alert-danger">Please enter a valid PayPal email address</div>';
        }
    }

    // Validate credit card fields if credit card is selected
    if ($payment_method === 'credit_card') {
        if (empty($card_number) || empty($card_expiry) || empty($card_cvv) || empty($card_holder)) {
            $message = '<div class="alert alert-danger">Please fill in all credit card information</div>';
        } elseif (strlen(preg_replace('/\s+/', '', $card_number)) < 13) {
            $message = '<div class="alert alert-danger">Please enter a valid card number</div>';
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $card_expiry)) {
            $message = '<div class="alert alert-danger">Please enter a valid expiry date (MM/YY)</div>';
        } elseif (strlen($card_cvv) < 3) {
            $message = '<div class="alert alert-danger">Please enter a valid CVV</div>';
        }
    }

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address)) {
        $message = '<div class="alert alert-danger">Please fill in all required fields</div>';
    }

    // Process the order
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $order_data = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'session_id' => $_SESSION['cart_session_id'] ?? session_id(),
        'order_number' => $order_number,
        'customer_name' => $first_name . ' ' . $last_name,
        'customer_email' => $email,
        'customer_phone' => $phone,
        'customer_address' => $address,
        'customer_city' => $city,
        'customer_postcode' => $postcode,
        'customer_country' => 'Spain', // Default country
        'subtotal' => $cart_total,
        'tax' => 0,
        'shipping' => 0,
        'total' => $cart_total,
        'payment_method' => $payment_method,
        'payment_status' => ($payment_method === 'cash_on_delivery') ? 'pending' : 'paid',
        'order_status' => 'pending',
        'notes' => $notes
    ];

    $query = "INSERT INTO orders (user_id, session_id, order_number, customer_name, customer_email, customer_phone, customer_address, customer_city, customer_postcode, customer_country, subtotal, tax, shipping, total, payment_method, payment_status, order_status, notes, created_at) VALUES (:user_id, :session_id, :order_number, :customer_name, :customer_email, :customer_phone, :customer_address, :customer_city, :customer_postcode, :customer_country, :subtotal, :tax, :shipping, :total, :payment_method, :payment_status, :order_status, :notes, NOW())";
    $stmt = $db->prepare($query);

    try {
        foreach ($order_data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if ($stmt->execute()) {
            $order_id = $db->lastInsertId();

            // Insert order items
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total) VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :total)";
                $item_stmt = $db->prepare($item_query);
                $item_stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity']
                ]);
            }

            // Clear cart AFTER processing items
            error_log("Clearing cart after order processing...");
            $clearResult = $cart->clearCart();
            error_log("Cart clear result: " . json_encode($clearResult));

            // Redirect to success page
            header("Location: checkout-success.php?order_id=$order_id");
            exit;
        } else {
            $message = '<div class="alert alert-danger">Error processing order. Please try again.</div>';
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error processing order. Please try again.</div>';
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
	<style>
		.payment-methods {
			display: flex;
			flex-direction: column;
			gap: 15px;
		}
		.payment-method-option {
			border: 2px solid #e9ecef;
			border-radius: 8px;
			padding: 15px;
			transition: all 0.3s ease;
		}
		.payment-method-option:hover {
			border-color: #667eea;
		}
		.payment-method-option input[type="radio"] {
			display: none;
		}
		.payment-method-option label {
			display: flex;
			align-items: center;
			cursor: pointer;
			margin: 0;
		}
		.payment-method-option label i {
			font-size: 24px;
			color: #667eea;
			margin-right: 15px;
			min-width: 30px;
		}
		.payment-method-option label strong {
			display: block;
			color: #333;
			margin-bottom: 5px;
		}
		.payment-method-option label p {
			margin: 0;
			color: #6c757d;
			font-size: 14px;
		}
		.payment-method-option input[type="radio"]:checked + label {
			color: #667eea;
		}
		.payment-method-option input[type="radio"]:checked ~ label i {
			color: #28a745;
		}
		.payment-method-option input[type="radio"]:checked ~ label {
			border-color: #28a745;
			background-color: #f8fff9;
		}
	</style>
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
										<li><a href="blog-grid.php">Blog Grid</a></li>
										<li><a href="blog-standard.php">Blog Standard</a></li>
										<li><a href="blog-details.php">Blog Details</a></li>
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
   <form action="checkout.php" method="POST" id="checkout-form">
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
    				<h3>Payment Method</h3>
    			</div>
    			<div class="col-md-12 mb-30">
    				<div class="payment-methods">
    					<div class="payment-method-option">
    						<input type="radio" id="cash_on_delivery" name="payment_method" value="cash_on_delivery" checked>
    						<label for="cash_on_delivery">
    							<i class="fas fa-money-bill-wave"></i>
    							<strong>Cash on Delivery</strong>
    							<p>Pay when you receive your order</p>
    						</label>
    					</div>
    					<div class="payment-method-option" onclick="showBankTransferInfo()">
    						<input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
    						<label for="bank_transfer">
    							<i class="fas fa-university"></i>
    							<strong>Bank Transfer</strong>
    							<p>Direct bank transfer to our account</p>
    						</label>
    					</div>
    					<div class="payment-method-option" onclick="showPayPalForm()">
    						<input type="radio" id="paypal" name="payment_method" value="paypal">
    						<label for="paypal">
    							<i class="fab fa-paypal"></i>
    							<strong>PayPal</strong>
    							<p>Pay securely with your PayPal account</p>
    						</label>
    					</div>
    					<div class="payment-method-option" onclick="showCreditCardForm()">
    						<input type="radio" id="credit_card" name="payment_method" value="credit_card">
    						<label for="credit_card">
    							<i class="fas fa-credit-card"></i>
    							<strong>Credit Card</strong>
    							<p>Pay securely with your credit card</p>
    						</label>
    					</div>
    				</div>

    				<!-- Bank Transfer Info (hidden by default) -->
    				<div id="bank-transfer-info" class="payment-info-form" style="display: none; margin-top: 20px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; background: #f8f9fa;">
    					<h5><i class="fas fa-university"></i> Bank Transfer Information</h5>
    					<div class="alert alert-info">
    						<i class="fas fa-info-circle"></i>
    						<strong>Bank Transfer Instructions:</strong>
    						<ul class="mb-0 mt-2">
    							<li>Bank Name: Banco Santander</li>
    							<li>Account Holder: BarbeX Hair Salon</li>
    							<li>IBAN: ES21 0049 1500 0512 3456 7890</li>
    							<li>BIC/SWIFT: BSCHESMM</li>
    							<li>Reference: Your Order Number</li>
    						</ul>
    						<small class="mt-2 d-block">Please include your order number in the transfer reference. Your order will be processed once the payment is confirmed (usually 1-2 business days).</small>
    					</div>
    				</div>

    				<!-- PayPal Form (hidden by default) -->
    				<div id="paypal-form" class="payment-info-form" style="display: none; margin-top: 20px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; background: #f8f9fa;">
    					<h5><i class="fab fa-paypal"></i> PayPal Payment</h5>
    					<div class="row">
    						<div class="col-md-12 mb-20">
    							<label>PayPal Email *</label>
    							<input type="email" name="paypal_email" class="form-control" placeholder="your-email@example.com">
    						</div>
    					</div>
    					<div class="alert alert-info">
    						<i class="fas fa-info-circle"></i>
    						<small>You will be redirected to PayPal to complete your payment securely. PayPal accepts Visa, MasterCard, American Express, and PayPal balance.</small>
    					</div>
    					<div class="text-center">
    						<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_100x26.png" alt="PayPal" style="height: 26px;">
    					</div>
    				</div>

    				<!-- Credit Card Form (hidden by default) -->
    				<div id="credit-card-form" class="credit-card-form" style="display: none; margin-top: 20px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; background: #f8f9fa;">
    					<h5><i class="fas fa-credit-card"></i> Credit Card Information</h5>
    					<div class="row">
    						<div class="col-md-12 mb-20">
    							<label>Card Number *</label>
    							<input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
    						</div>
    						<div class="col-md-6 mb-20">
    							<label>Expiry Date *</label>
    							<input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5">
    						</div>
    						<div class="col-md-6 mb-20">
    							<label>CVV *</label>
    							<input type="text" name="card_cvv" class="form-control" placeholder="123" maxlength="4">
    						</div>
    						<div class="col-md-12 mb-20">
    							<label>Cardholder Name *</label>
    							<input type="text" name="card_holder" class="form-control" placeholder="John Doe">
    						</div>
    					</div>
    					<div class="alert alert-info">
    						<i class="fas fa-info-circle"></i>
    						<small>Your payment information is encrypted and secure. We accept Visa, MasterCard, and American Express.</small>
    					</div>
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
                            <div class="all__sidebar-item-cart" id="checkout-cart-items">
                                <ul id="checkout-items-list">
    					<?php if (!empty($cart_items)): ?>
    						<?php foreach ($cart_items as $item): ?>
    						<li>
    							<div class="cart-item-info">
    								<?php if (!empty($item['image'])): ?>
    									<img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
    								<?php endif; ?>
    								<span><?php echo htmlspecialchars($item['name']); ?> X <?php echo $item['quantity']; ?></span>
    							</div>
    							<span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
    						</li>
    						<?php endforeach; ?>
    					<?php else: ?>
    						<li id="empty-cart-message">No items in cart</li>
    					<?php endif; ?>
    					<li id="checkout-total">Total<span>$<?php echo number_format($cart_total, 2); ?></span></li>
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
	<script src="assets/js/cart_final.js"></script>

	<script>
		function showBankTransferInfo() {
			document.getElementById('bank_transfer').checked = true;
			document.getElementById('bank-transfer-info').style.display = 'block';
			document.getElementById('paypal-form').style.display = 'none';
			document.getElementById('credit-card-form').style.display = 'none';
		}

		function showPayPalForm() {
			document.getElementById('paypal').checked = true;
			document.getElementById('paypal-form').style.display = 'block';
			document.getElementById('bank-transfer-info').style.display = 'none';
			document.getElementById('credit-card-form').style.display = 'none';
		}

		function showCreditCardForm() {
			document.getElementById('credit_card').checked = true;
			document.getElementById('credit-card-form').style.display = 'block';
			document.getElementById('bank-transfer-info').style.display = 'none';
			document.getElementById('paypal-form').style.display = 'none';
		}

		// Hide all forms when other payment methods are selected
		document.addEventListener('DOMContentLoaded', function() {
			document.getElementById('cash_on_delivery').addEventListener('change', function() {
				document.getElementById('bank-transfer-info').style.display = 'none';
				document.getElementById('paypal-form').style.display = 'none';
				document.getElementById('credit-card-form').style.display = 'none';
			});
			document.getElementById('bank_transfer').addEventListener('change', function() {
				document.getElementById('bank-transfer-info').style.display = 'block';
				document.getElementById('paypal-form').style.display = 'none';
				document.getElementById('credit-card-form').style.display = 'none';
			});
			document.getElementById('paypal').addEventListener('change', function() {
				document.getElementById('paypal-form').style.display = 'block';
				document.getElementById('bank-transfer-info').style.display = 'none';
				document.getElementById('credit-card-form').style.display = 'none';
			});
			document.getElementById('credit_card').addEventListener('change', function() {
				document.getElementById('credit-card-form').style.display = 'block';
				document.getElementById('bank-transfer-info').style.display = 'none';
				document.getElementById('paypal-form').style.display = 'none';
			});
		});

		// Format card number input
		document.addEventListener('DOMContentLoaded', function() {
			const cardNumberInput = document.querySelector('input[name="card_number"]');
			const cardExpiryInput = document.querySelector('input[name="card_expiry"]');

			if (cardNumberInput) {
				cardNumberInput.addEventListener('input', function(e) {
					let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
					let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
					e.target.value = formattedValue;
				});
			}

			if (cardExpiryInput) {
				cardExpiryInput.addEventListener('input', function(e) {
					let value = e.target.value.replace(/\D/g, '');
					if (value.length >= 2) {
						value = value.substring(0, 2) + '/' + value.substring(2, 4);
					}
					e.target.value = value;
				});
			}
		});
	</script>

</body>

</html>
