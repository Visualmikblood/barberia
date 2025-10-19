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

// Pagination settings
$products_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $products_per_page;

// Get total products count
$total_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$total_stmt = $db->query($total_query);
$total_products = $total_stmt->fetch()['total'];
$total_pages = ceil($total_products / $products_per_page);

// Get products for current page
$query = "SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $products_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories for sidebar
$category_query = "SELECT category, COUNT(*) as count FROM products WHERE status = 'active' GROUP BY category ORDER BY category";
$category_stmt = $db->query($category_query);
$categories = $category_stmt->fetchAll();
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
	<title>BarbeX - Hair Salon HTML5 Template</title>
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
                        <h1>Product Page</h1>
                        <div class="page__banner-title-menu">
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><span>_</span>Product Page</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Banner End -->
	<!-- Product Page Start -->
    <div class="product__page section-padding">
        <div class="container">
            <div class="row">
                <div class="col-xl-4">
					<div class="all__sidebar-item-search mr-25 xl-mr-0 mb-40">
						<form action="#">
							<input type="text" placeholder="Search.....">
							<button type="submit"><i class="fal fa-search"></i></button>
						</form>
					</div>
                    <div class="all__sidebar mr-25 xl-mr-0">
                        <div class="all__sidebar-item">
                            <h5>Product Category</h5>
                            <div class="all__sidebar-item-category">
                                <ul>
                                    <?php foreach ($categories as $category): ?>
                                    <li><a href="product-page.php?category=<?php echo urlencode($category['category']); ?>"><i class="far fa-angle-double-right"></i><?php echo htmlspecialchars($category['category']); ?><span>(<?php echo $category['count']; ?>)</span></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="all__sidebar-item mt-40">
                            <h5>Popular Product</h5>
                            <div class="all__sidebar-item-product">
                                <?php
                                // Get popular products (featured or recently added)
                                $popular_query = "SELECT * FROM products WHERE status = 'active' AND featured = 1 ORDER BY created_at DESC LIMIT 3";
                                $popular_stmt = $db->query($popular_query);
                                $popular_products = $popular_stmt->fetchAll();

                                foreach ($popular_products as $product):
                                ?>
                                <div class="all__sidebar-item-product-item">
                                    <div class="all__sidebar-item-product-item-image">
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>"><img src="<?php echo htmlspecialchars($product['image']); ?>" alt=""></a>
                                    </div>
                                    <div class="all__sidebar-item-product-item-content">
                                        <div class="all__sidebar-item-product-item-content-review">
                                            <ul>
                                                <li><i class="fas fa-star"></i></li>
                                                <li><i class="fas fa-star"></i></li>
                                                <li><i class="fas fa-star"></i></li>
                                                <li><i class="fas fa-star"></i></li>
                                                <li><i class="fas fa-star"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h6>
                                        <span>$<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="all__sidebar-item mt-40">
                            <h5>Tags</h5>
                            <div class="all__sidebar-item-tag">
                                <ul>
                                    <li><a href="blog-standard.php">Design</a></li>
                                    <li><a href="blog-standard.php">Brochure</a></li>
                                    <li><a href="blog-standard.php">Product</a></li>
                                    <li><a href="blog-standard.php">Business</a></li>
                                    <li><a href="blog-standard.php">Development</a></li>
                                    <li><a href="blog-standard.php">Marketing</a></li>
                                    <li><a href="blog-standard.php">Branding</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8 order-first order-xl-1 xl-mb-30">
                    <div class="row align-items-center mb-30">
                        <div class="col-md-7 md-mb-30">
                            <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $products_per_page, $total_products); ?> of <?php echo $total_products; ?> Result</span>
                        </div>
                        <div class="col-md-5">
                            <div class="product__page-filter">
                                <select name="select" data-background="assets/img/icon/down-arrow.png">
                                    <option value="1">Default Sorting</option>
                                    <option value="2">Sort By Most Popular</option>
                                    <option value="3">Sort By High To Low</option>
                                    <option value="4">Sort By Low To High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-xl-4 col-lg-4 col-md-6 mb-30">
                            <div class="products__area-item">
                                <div class="products__area-item-image">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="">
                                    <div class="products__area-item-image-social">
                                        <ul>
                                            <li><a href="#" class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" data-product-price="<?php echo $product['price']; ?>" data-product-image="<?php echo htmlspecialchars($product['image']); ?>"><i class="far fa-shopping-basket"></i></a></li>
                                            <li><a href="#"><i class="far fa-heart"></i></a></li>
                                            <li><a class="img-popup" href="<?php echo htmlspecialchars($product['image']); ?>"><i class="far fa-compress"></i></a></li>
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
                                    <h5><a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h5>
                                    <span>$<?php echo number_format($product['price'], 2); ?>
                                    <?php if ($product['sale_price']): ?>
                                        <del>$<?php echo number_format($product['sale_price'], 2); ?></del>
                                    <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
						<div class="col-xl-12">
							<div class="theme__pagination product__page-border">
								<ul>
									<?php if ($page > 1): ?>
									<li><a href="?page=<?php echo $page - 1; ?>"><i class="far fa-angle-left"></i></a></li>
									<?php endif; ?>

									<?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
									<li><a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a></li>
									<?php endfor; ?>

									<?php if ($page < $total_pages): ?>
									<li><a href="?page=<?php echo $page + 1; ?>"><i class="far fa-angle-right"></i></a></li>
									<?php endif; ?>
								</ul>
							</div>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<!-- Product Page End -->
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