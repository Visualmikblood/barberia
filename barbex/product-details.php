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

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: product-page.php');
    exit;
}

// Get product details
$query = "SELECT * FROM products WHERE id = :id AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    header('Location: product-page.php');
    exit;
}

// Get related products (same category, excluding current product)
$related_query = "SELECT * FROM products WHERE category = :category AND id != :id AND status = 'active' ORDER BY created_at DESC LIMIT 4";
$related_stmt = $db->prepare($related_query);
$related_stmt->bindParam(':category', $product['category']);
$related_stmt->bindParam(':id', $product_id);
$related_stmt->execute();
$related_products = $related_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Start Meta -->
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="description" content="<?php echo htmlspecialchars($product['short_description'] ?? $product['name']); ?>"/>
	<meta name="keywords" content="Creative, Digital, multipage, landing, freelancer template"/>
	<meta name="author" content="ThemeOri">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Title of Site -->
	<title><?php echo htmlspecialchars($product['name']); ?> - BarbeX</title>
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
                        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="page__banner-title-menu">
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><span>_</span><?php echo htmlspecialchars($product['name']); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Banner End -->
	<!-- Product Details Start -->
    <div class="product__details section-padding">
        <div class="container">
            <div class="row">
				<div class="col-xl-2 col-lg-2 col-sm-3">
					<div class="nav">
					  <span class="product__details-image active" data-bs-toggle="tab" data-bs-target="#one"><img src="<?php echo htmlspecialchars($product['image']); ?>" alt=""></span>
					  <?php
					  $gallery = json_decode($product['gallery_images'] ?? '[]', true);
					  if (is_array($gallery) && !empty($gallery)) {
					      foreach ($gallery as $index => $image) {
					          $target = '#gallery-' . $index;
					          echo '<span class="product__details-image" data-bs-toggle="tab" data-bs-target="' . $target . '"><img src="' . htmlspecialchars($image) . '" alt=""></span>';
					      }
					  }
					  ?>
					</div>
				</div>
				<div class="col-xl-5 col-lg-5 col-sm-9 order-first order-sm-1">
					<div class="tab-content">
					  <div class="tab-pane fade show active" id="one">
						  <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="">
					  </div>
					  <?php
					  if (is_array($gallery) && !empty($gallery)) {
					      foreach ($gallery as $index => $image) {
					          $target = 'gallery-' . $index;
					          echo '<div class="tab-pane fade" id="' . $target . '">';
					          echo '<img src="' . htmlspecialchars($image) . '" alt="">';
					          echo '</div>';
					      }
					  }
					  ?>
				  </div>
				</div>
				<div class="col-xl-5 col-lg-5 lg-mt-30 order-last">
					<div class="product__details-title ml-30 xl-ml-0">
						<h3><?php echo htmlspecialchars($product['name']); ?></h3>
						<h4>$<?php echo number_format($product['price'], 2); ?>
						<?php if ($product['sale_price']): ?>
						    <span>$<?php echo number_format($product['sale_price'], 2); ?></span>
						<?php endif; ?>
						</h4>
						<div class="product__details-title-info">
							<div class="product__details-title-info-rating">
								<ul>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
								</ul>
							</div>
							<span>(<?php echo rand(10, 50); ?> customer review)</span>
						</div>
						<p><?php echo htmlspecialchars($product['description']); ?></p>
						<div class="product__details-product-qty">
							<form action="#">
							   <div class="product__details-product-qty-select">
									<div class="product__details-product-qty-select-cart-plus-minus">
									    <input type="text" value="1" class="quantity-input">
									    <div class="dec qtybutton">-</div>
									    <div class="inc qtybutton">+</div>
									</div>
								  <div class="pro-cart-btn">
										<button class="theme-btn add-to-cart-btn" type="button"
										        data-product-id="<?php echo $product['id']; ?>"
										        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
										        data-product-price="<?php echo $product['price']; ?>"
										        data-product-image="<?php echo htmlspecialchars($product['image']); ?>">
										    Add to cart
										</button>
								  </div>
							   </div>
							</form>
						</div>
						<div class="product__details-list">
							<h6>SKU :<a href="#"><?php echo htmlspecialchars($product['sku']); ?></a></h6>
							<h6>Category :<a href="#"><?php echo htmlspecialchars($product['category']); ?></a></h6>
							<h6>Tags :<a href="#"><?php echo htmlspecialchars($product['tags']); ?></a></h6>
						</div>
					</div>
				</div>
            </div>
			<div class="row mt-65">
				<div class="col-xl-12">
					<div class="product__details-description">
						<div class="product__details-information nav">
							<h6 class="nav-button active" data-bs-toggle="tab" data-bs-target="#description">Description</h6>
							<h6 class="nav-button" data-bs-toggle="tab" data-bs-target="#information">Product Information</h6>
							<h6 class="nav-button" data-bs-toggle="tab" data-bs-target="#reviews">Reviews</h6>
						</div>
						<div class="tab-content">
							<div class="tab-pane fade show active" id="description">
								<p class="mb-20"><?php echo htmlspecialchars($product['description']); ?></p>
							</div>
							<div class="tab-pane fade" id="information">
								<div class="product__details-product-information">
									<ul>
										<li><span>Product Dimensions :</span><?php echo htmlspecialchars($product['dimensions'] ?? 'N/A'); ?></li>
										<li><span>Item model number :</span><?php echo htmlspecialchars($product['sku']); ?></li>
										<li><span> Manufacturer :</span><?php echo htmlspecialchars($product['brand'] ?? 'BarbeX'); ?></li>
										<li><span> Country of Origin :</span>Spain</li>
										<li><span>Brand Name :</span><?php echo htmlspecialchars($product['brand'] ?? 'BarbeX'); ?></li>
									</ul>
								</div>
							</div>
							<div class="tab-pane fade" id="reviews">
								<div class="product__details-reviews">
									<div class="row">
										<div class="product__details-reviews">
											<div class="product__details-reviews-title">
												<h6>Add a review</h6>
												<p>Your email address will not be published. Required fields are marked *</p>
											</div>
											<div class="product__details-reviews-rating">
												<span>Your rating *</span>
												<ul>
													<li><a href="#"><i class="fas fa-star"></i></a></li>
													<li><a href="#"><i class="fas fa-star"></i></a></li>
													<li><a href="#"><i class="fas fa-star"></i></a></li>
													<li><a href="#"><i class="fas fa-star"></i></a></li>
													<li><a href="#"><i class="fas fa-star"></i></a></li>
												</ul>
											</div>
											<div class="product__details-reviews-form mt-45">
												<form action="#">
													<div class="row">
														<div class="col-sm-6 mb-30">
															<div class="blog__details-left-contact-form-item">
																<i class="fal fa-user"></i>
																<input type="text" name="name" placeholder="Name *" required="required">
															</div>
														</div>
														<div class="col-sm-6 sm-mb-30">
															<div class="blog__details-left-contact-form-item">
																<i class="fal fa-envelope"></i>
																<input type="text" name="email" placeholder="Email *" required="required">
															</div>
														</div>
														<div class="col-sm-12 mb-20">
															<div class="blog__details-left-contact-form-item">
																<i class="fal fa-pen"></i>
																<textarea name="message" placeholder="Your review *"></textarea>
															</div>
														</div>
														<div class="col-sm-12 mb-30">
															<label><input class="mr-10" type="checkbox">Save my name, email, and website in this browser for the next time I comment.</label>
														</div>
														<div class="col-lg-12">
															<div class="blog__details-left-contact-form-item">
																<button class="theme-btn" type="submit">Submit Now<i class="far fa-angle-double-right"></i></button>
															</div>
														</div>
													</div>
												</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($related_products)): ?>
			<div class="row mb-65">
				<h3>Related Product</h3>
			</div>
			<div class="row">
				<?php foreach ($related_products as $related): ?>
				<div class="col-xl-3 col-lg-4 col-md-6 xl-mb-30">
					<div class="products__area-item">
						<div class="products__area-item-image">
							<img src="<?php echo htmlspecialchars($related['image']); ?>" alt="">
							<div class="products__area-item-image-social">
								<ul>
									<li><a href="#" class="add-to-cart-btn" data-product-id="<?php echo $related['id']; ?>" data-product-name="<?php echo htmlspecialchars($related['name']); ?>" data-product-price="<?php echo $related['price']; ?>" data-product-image="<?php echo htmlspecialchars($related['image']); ?>"><i class="far fa-shopping-basket"></i></a></li>
									<li><a href="#"><i class="far fa-heart"></i></a></li>
									<li><a class="img-popup" href="<?php echo htmlspecialchars($related['image']); ?>"><i class="far fa-compress"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="products__area-item-content">
							<div class="products__area-item-content-social">
								<ul>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
									<li><i class="fas fa-star"></i></li>
								</ul>
							</div>
							<h5><a href="product-details.php?id=<?php echo $related['id']; ?>"><?php echo htmlspecialchars($related['name']); ?></a></h5>
							<span>$<?php echo number_format($related['price'], 2); ?></span>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
        </div>
    </div>
	<!-- Product Details End -->
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
</body>

</html>