<?php
if ( ! class_exists( 'WSQ_FORM' ) ) {

	class WSQ_FORM extends FlipperCode_HTML_Markup{


		function __construct($options = array()) {


		$premium_features = "<ul class='fc-pro-features'>
				                        <li>Ask multiple security questions to users to protect it in more advance way (Multiple Security Layers).</li>
				                        <li>Display / Hide edit security answer from user profile page. Useful feature if admin don’t want to let users update security answers on their own.</li>
				                        <li>You can choose if you want to ask users security questions always, randomly, on one time login failed or two time login failed. Most flexibile plugin with this main requirement.</li>
				                        <li>Display Answer Hints to users if someone completely forgot the security answer. Though user can send security answer reset request to admin.</li>
				                        <li>You can display security answer field as password type field so that user can safely type security answer.</li>
				                        <li>You can choose if multiple security answers is required or any one answer is required on registration page.</li>
				                        </ul>";

			$productInfo = array('productName' => __('WP Security Questions',WSQ_TEXT_DOMAIN),
													'productSlug' => 'wp-security-questions',
													'productTagLine' => 'WP Security Questions - A product that protect your wordpress account with help of security questions your answered during registration process.',
													'productTextDomain' => WSQ_TEXT_DOMAIN,
													'productIconImage' => WSQ_URL.'core/core-assets/images/wp-poet.png',
													'videoURL' => 'https://www.youtube.com/watch?v=6hNa1UDfYo8&list=PLlCp-8jiD3p27paLQNZn4AtdqW0Bi4ZSE',
													'productVersion' => WSQ_VERSION,
													'docURL' => 'http://guide.flippercode.com/store-locator/',
													'demoURL' => 'http://www.flippercode.com/product/wp-overlays-pro/',
													'productImagePath' => WSQ_URL.'core/core-assets/product-images/',
													'productSaleURL' => 'https://codecanyon.net/item/wordpress-security-questions/5894819',
													'multisiteLicence' => 'https://codecanyon.net/item/wordpress-security-questions/5894819?license=extended&open_purchase_for_item_id=5894819&purchasable=source',
													'is_premium' => 'false',
													'docURL' => 'http://guide.flippercode.com/securityquestions/ ',
													'demoURL' => 'http://www.flippercode.com/product/wp-security-questions/',
													'have_premium' => 'true',
													'productBanner' => 'https://image-cc.s3.envato.com/files/170364381/wp-security-quesitons.png',
													'premium_features' => $premium_features,
			);

			$productInfo = array_merge($productInfo, $options);
			parent::__construct($productInfo);
		}

	}

}
