<?php
if(!defined('IN_CRONLITE'))exit();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=yes">
<link rel="shortcut icon" href="/favicon.ico" />
<title><?php echo $conf['title']?></title>
<meta name="keywords" content="<?php echo $conf['keywords']?>">
<meta name="description" content="<?php echo $conf['description']?>">
  <meta name="renderer" content="webkit">
	
	<link rel="stylesheet" href="/css/bootstrap.css" />
	<link rel="stylesheet" href="/css/font-awesome.min.css" />
	<link rel="stylesheet" href="/css/linea-icon.css" />
	<link rel="stylesheet" href="/css/fancy-buttons.css" />
	
	<!--=== Google Fonts ===-
	<link href='http://fonts.googleapis.com/css?family=Bangers' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Roboto+Slab:300,700,400' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Raleway:600,400,300' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
	<!--=== Other CSS files ===-->
	<link rel="stylesheet" href="/css/animate.css" />
	<link rel="stylesheet" href="/css/jquery.vegas.css" />
	<link rel="stylesheet" href="/css/baraja.css" />
	<link rel="stylesheet" href="/css/jquery.bxslider.css" />
	
	<!--=== Main Stylesheets ===-->
	<link rel="stylesheet" href="/css/style.css" />
	<link rel="stylesheet" href="/css/responsive.css" />
	
	<!--=== Color Scheme, three colors are available red.css, orange.css and gray.css ===-->
	<link rel="stylesheet" id="scheme-source" href="/css/schemes/gray.css" />
	
	<!--=== Internet explorer fix ===-->
	<!-- [if lt IE 9]>
		<script src="http://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="http://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	<![endif] -->
	
	
 
</head>
<body>
	<!--=== Preloader section starts ===-->
	<section id="preloader">
		<div class="loading-circle fa-spin"></div>
	</section>
	<!--=== Preloader section Ends ===-->
	
	<!--=== Header section Starts ===-->
	<div id="header" class="header-section">
		<!-- sticky-bar Starts-->
		<div class="sticky-bar-wrap">
			<div class="sticky-section">
				<div id="topbar-hold" class="nav-hold container">
					<nav class="navbar" role="navigation">
						<div class="navbar-header">
							<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-responsive-collapse">
									<span class="sr-only">Toggle navigation</span>
									<span class="icon-bar"></span>
									<span class="icon-bar"></span>
									<span class="icon-bar"></span>
							</button>
							<!--=== Site Name ===-->
							<a class="site-name navbar-brand" href="#"><span></span><?php echo $conf['sitename']?></a>
						</div>
						
						<!-- Main Navigation menu Starts -->
						<div class="collapse navbar-collapse navbar-responsive-collapse">
							<ul class="nav navbar-nav navbar-right">
								<li class="current"><a href="#header">首页</a></li>
								<li><a href="#section-feature">产品</a></li>
								<li><a href="#section-services">特点</a></li>
								<li><a href="/user/test.php">在线测试</a></li>
								<li><a href="/doc.html">开发文档</a></li>

							</ul>
						</div>
						<!-- Main Navigation menu ends-->
					</nav>
				</div>
			</div>
		</div>
		<!-- sticky-bar Ends-->
		<!--=== Header section Ends ===-->
		
		<!--=== Home Section Starts ===-->
		<div id="section-home" class="home-section-wrap center">
			<div class="section-overlay"></div>
			<div class="container home">
				<div class="row">
					<h1 class="well-come"><?php echo $conf['sitename']?></h1>
					
					<div class="col-md-8 col-md-offset-2">
						<p class="intro-message">帮助开发者快速将支付（支付宝，钱包，微信）集成到自己相应产品，效率高，见效快，费率低</p>
						<p class="intro-message">帮助个体，企业，申请微信支付，高审核率，更多联系QQ客服</p>						
						<div class="home-buttons">
							<a href="user/login.php" class="fancy-button button-line button-white vertical">
								登录商户
								<span class="icon">
									<i class="fa fa-gear"></i>
								</span>
							</a>
							<a href="/user/reg.php" class="fancy-button button-line button-white zoom">
								注册商户
								<span class="icon">
									<i class="fa fa-leaf"></i>
								</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!--=== Home Section Ends ===-->
	</div>
	
	
	<!--=== Features section Starts ===-->
	<section id="section-feature" class="feature-wrap">
		<div class="container features center">
			<div class="row">
				<div class="col-lg-12">
						<!--Features container Starts -->
						<ul id="card-ul" class="features-hold baraja-container">
						
							<!-- Single Feature Starts -->
							<li class="single-feature" title="Card style">
								<img src="images/5.jpg" alt="风讯云支付" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">个人自动发卡网</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								
									<a href="#" class="fancy-button button-line btn-col small vertical">
										细节
										<span class="icon">
											<i class="fa fa-leaf"></i>
										</span>
									</a>
								
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="50+ SVG Icon included">
								<img src="images/2.jpg" alt="风讯云支付" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">摄影网站</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="MailChimp Ready">
								<img src="images/3.jpg" alt="QQ代刷网" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">QQDaiShua网自助平台</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="4 home style">
								<img src="images/4.jpg" alt="QQ卡盟" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">QQ钻KaMeng程序</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="Parallax Backgrounds">
								<img src="images/5.jpg" alt="娱乐网" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">在线娱乐网</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="Ajax contact form">
								<img src="images/6.jpg" alt="个人博客" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">个人博客</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="unlimited Google fonts">
								<img src="images/7.jpg" alt="高防idc" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">idc互联网站</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
							
							<!-- Single Feature Starts -->
							<li class="single-feature" title="Feature heading">
								<img src="images/8.jpg" alt="风讯云支付" class="feature-image" /><!-- Feature Icon -->
								<h4 class="feature-title color-scheme">更多</h4>
								<p class="feature-text">
									提供免签约支付宝、QQ钱包、微信、财付通支付.
								</p>
								<a href="#" class="fancy-button button-line btn-col small zoom">
									细节
									<span class="icon">
										<i class="fa fa-leaf"></i>
									</span>
								</a>
							</li>
							<!-- Single Feature Ends -->
						</ul>
						<!--Features container Ends -->
						
						<!-- Features Controls Starts -->
						<div class="features-control relative">
							<button class="control-icon fancy-button button-line no-text btn-col bell" id="feature-prev" title="Previous" >
								<span class="icon">
									<i class="fa fa-arrow-left"></i>
								</span>
							</button>
							<button class="control-icon fancy-button button-line no-text btn-col zoom" id="feature-expand" title="Expand" >
								<span class="icon">
									<i class="fa fa-expand"></i>
								</span>
							</button>
							<button class="control-icon fancy-button button-line no-text btn-col zoom" id="feature-close" title="Collapse" >
								<span class="icon">
									<i class="fa fa-compress"></i>
								</span>
							</button>
							<button class="control-icon fancy-button button-line no-text btn-col bell" id="feature-next" title="Next" >
								<span class="icon">
									<i class="fa fa-arrow-right"></i>
								</span>
							</button>
						</div>
						<!-- Features Controls Ends -->
				</div>
			</div>
		</div>
	</section>
	<!--=== Features section Ends ===-->
	
	<!--=== Services section Starts ===-->
	<section id="section-services" class="services-wrap">
		<div class="container services">
			<div class="row">
			
				<div class="col-md-10 col-md-offset-1 center section-title">
					<h3>我们的优势</h3>
				</div>
			
				<!-- Single Service Starts -->
				<div class="col-md-6 col-sm-6 service animated" data-animation="fadeInLeft" data-animation-delay="700">
					<span class="service-icon center"><i class="icon icon-basic-book-pencil fa-3x"></i></span>
					<div class="service-desc">
						<h4 class="service-title color-scheme">多种方式结算</h4>
						<p class="service-description justify">
							资金安全有保障-实时查询，安全可靠，多重账号保护措施安全可靠：维护商户利益，7*24小时全天候服务
						</p>
					</div>
				</div>
				<!-- Single Service Ends -->
				
				<!-- Single Service Starts -->
				<div class="col-md-6 col-sm-6 service animated" data-animation="fadeInUp" data-animation-delay="700">
					<span class="service-icon center"><i class="icon icon-basic-paperplane fa-3x"></i></span>
					<div class="service-desc">
						<h4 class="service-title color-scheme">资金回笼</h4>
						<p class="service-description justify">
							一次轻松接入所有支付（钱包，支付宝，微信），省时省心省力， 结算费率低，利润高！
						</p>
					</div>
				</div>
				<!-- Single Service ends -->
				
				<!-- Single Service Starts -->
				<div class="col-md-6 col-sm-6 service animated" data-animation="fadeInRight" data-animation-delay="700">
					<span class="service-icon center"><i class="icon icon-basic-accelerator fa-3x"></i></span>
					<div class="service-desc">
						<h4 class="service-title color-scheme">支付流程</h4>
						<p class="service-description justify">
							方便，快捷。让支付和交易 变得更简单、方便
							
						</p>
					</div>
				</div>
				<!-- Single Service Ends -->
				
				<!-- Single Service Starts -->
				<div class="col-md-6 col-sm-6 service animated" data-animation="fadeInUp" data-animation-delay="700">
					<span class="service-icon center"><i class="icon icon-basic-lightbulb fa-3x"></i></span>
					<div class="service-desc">
						<h4 class="service-title color-scheme">高效服务</h4>
						<p class="service-description justify">
							我们提供7X24小时在线服务，对日交易高额用户可提供一对一服务！
						</p>
					</div>
				</div>
				<!-- Single Service Ends -->
			</div>
		</div>
	</section>

			<div id="portfolio-loader" class="center">
				<div class="loading-circle fa-spin"></div>
			</div> <!--=== Portfolio loader ===-->
			
			<div id="portfolio-load"></div><!--=== ajax content will be loaded here ===-->
			
			<div class="col-md-12 center back-button">
				<a class="backToProject fancy-button button-line bell btn-col" href="#">
					Back
					<span class="icon">
						<i class="fa fa-arrow-left"></i>
					</span>
				</a>
			</div><!--=== Single portfolio back button ===-->
		</div>
	</section>
	<!--=== ScreenShots section Ends ===-->

	<div class="copyrights">Collect from <a href="/" >风讯易支付官网</a></div>

	

	
	<!--=== Footer section Starts ===-->
	<div id="section-footer" class="footer-wrap">
		<div class="container footer center">
			<div class="row">
				<div class="col-lg-12">
					<h4 class="footer-title"><!-- Footer Title -->
						<a class="site-name" href="#"><span></span><?php echo $conf['sitename']?></a>
					</h4>
					
				
					 <ul class="breadcrumb">



						   <li><a class="btn-link" href="#"><?php echo $conf['sitename']?></a></li>

						   	<li><a class="btn-link" href="https://www.baidu.com/">百度搜索 </a></li>

						   <li><a class="btn-link" href="https://www.sogou.com/">搜狗搜索</a></li>


                        </ul>

<p class="lianxifangshi">客服QQ：<?php echo $conf['kfqq']?> &nbsp;&nbsp;  邮箱：<?php echo $conf['email']?> &nbsp;&nbsp; 公司名称：<?php echo $conf['orgname']?></a></p>
<p class="copyright">All rights reserved &copy;  Copyright 2017-2020.More <a href="" target="_blank" title="风讯云支付"><?php echo $conf['sitename']?>官网</a> - 备案号： <a href="https://beian.miit.gov.cn/" title="备案号：<?php echo $conf['footer']?>" target="_blank"><?php echo $conf['footer']?></a></p>
					

				</div>
			</div>
		</div>
	</div>



	<!--=== Footer section Ends ===-->
	
<!--==== Js files ====-->
<!--==== Essential files ====-->
<script type="text/javascript" src="/js/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/js/bootstrapValidator.min.js"></script>
<script type="text/javascript" src="/js/modernizr.js"></script>
<script type="text/javascript" src="/js/jquery.easing.1.3.js"></script>

<!--==== Slider and Card style plugin ====-->
<script type="text/javascript" src="/js/jquery.baraja.js"></script>
<script type="text/javascript" src="/js/jquery.vegas.min.js"></script>
<script type="text/javascript" src="/js/jquery.bxslider.min.js"></script>

<!--==== MailChimp Widget plugin ====-->
<script type="text/javascript" src="/js/jquery.ajaxchimp.min.js"></script>

<!--==== Scroll and navigation plugins ====-->
<script type="text/javascript" src="/js/jquery.nicescroll.min.js"></script>
<script type="text/javascript" src="/js/jquery.nav.js"></script>
<script type="text/javascript" src="/js/jquery.appear.js"></script>
<script type="text/javascript" src="/js/jquery.fitvids.js"></script>

<!--==== Custom Script files ====-->
<script type="text/javascript" src="/js/custom.js"></script>

</body>
</html>