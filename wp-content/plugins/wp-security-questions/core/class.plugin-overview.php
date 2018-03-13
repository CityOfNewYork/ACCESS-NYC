<?php
/**
 * Flippercode Product Overview Setup Class
 * @author Flipper Code<hello@flippercode.com>
 * @version 1.0.0
 * @package Core
 */

if ( ! class_exists( 'Flippercode_Product_Overview' ) ) {

	/**
	 * FlipperCode Overview Setup Class.
	 * @author Flipper Code<hello@flippercode.com>
	 * @version 1.0.0
	 * @package Core
	 */
	class Flippercode_Product_Overview {

		/**
		 * Store object type
		 * @var  String
		 */
		public $productName;
		/**
		 * Store object type
		 * @var  String
		 */
		public $productSlug;
		/**
		 * Store object type
		 * @var  String
		 */
		public $productTagLine;
		/**
		 * Store object type
		 * @var  String
		 */
		public $productTextDomain;
		/**
		 * Store object type
		 * @var  String
		 */
		public $productIconImage;

		/**
		 * Store product current running version number
		 * @var  String
		 */
		public $productVersion;

		/**
		 * Store product new version
		 * @var  String
		 */
		public $newVersion;

		/**
		 * Store object type
		 * @var  String
		 */
		private $commonBlocks;

		/**
		 * Store object type
		 * @var  String
		 */
		private $productSpecificBlocks;

		/**
		 * Store object type
		 * @var  String
		 */
		private $is_common_block;

		/**
		 * Store Product Overview Markup
		 * @var  String
		 */
		private $productBlocksRendered = 0;

		/**
		 * Store Product Overview Markup
		 * @var  String
		 */
		private $blockHeading;
		/**
		 * Store Product Overview Markup
		 * @var  String
		 */
		private $blockContent;
		/**
		 * Store Current Block Indication Class
		 * @var  String
		 */
		private $blockClass = '';
		/**
		 * Store Product Overview Markup
		 * @var  String
		 */
		private $commonBlockMarkup = '';
		/**
		 * Store Product Overview Markup
		 * @var  String
		 */
		private $pluginSpecificBlockMarkup = '';
		/**
		 * Final Product Overview Markup
		 * @var  String
		 */
		private $finalproductOverviewMarkup = '';
		/**
		 * Assign all products their i-cards :)
		 * @var  Array
		 */
		private $allProductsInfo = array();
		/**
		 * Store current message
		 * @var  Boolean
		 */
		private $message = '';
		/**
		 * Store current error = '';
		 * @var  Boolean
		 */
		private $error;
		/**
		 * Store product online doc url;
		 * @var  Boolean
		 */
		private $docURL;
		/**
		 * Store product demo url;
		 * @var  Boolean
		 */
		private $demoURL;
		/**
		 * Product Image Path;
		 * @var  Boolean
		 */
		private $productImagePath;
	
		/**
		 * Is Update Available ?;
		 * @var  Boolean
		 */
		private $isUpdateAvailable;

		private $multisiteLicence;

		private $productSaleURL;

		function __construct($pluginInfo) {

			$this->commonBlocks = array( 'suggestion-area','socialmedia','product-activation','newsletter' );
			$this->init( $pluginInfo );
			$this->renderOverviewPage();

		}

		function renderOverviewPage() {
			$skin = $_GET['skin'];
			$plugin_updates = unserialize( get_option('fc_'.$this->productSlug ) );
		
			?>
			<div class="<?php echo $skin; ?> flippercode-ui fcdoc-product-info" data-current-product=<?php echo $this->productTextDomain; ?> data-current-product-slug=<?php echo $this->productSlug; ?> data-product-version = <?php echo $this->productVersion; ?> data-product-name = "<?php echo $this->productName; ?>" >
			<div class="fc-main">	
			<div class="fc-container">
		        <div class="fc-divider"><div class="fc-8"><div class="fc-divider">
					 <div class="fcdoc-flexrow">
					 <?php $this->renderBlocks(); ?> 
					 </div>
			    </div></div>
			    <div class="fc-4 message-board"><?php echo $this->renderMessages(); ?></div>
			    </div>
		    </div>    
			</div>
		<?php
		}
		function renderMessages() {
			$plugin_updates = unserialize( get_option('fc_'.$this->productSlug ) );

			$changelog =  $this->premium_features;
			$changelog .= '<a href="'.$this->productSaleURL.'" target="_blank" class="fc-btn fc-btn-default fc-buy-btn">Buy on Codecanyon</a>';
			
			
			$plugins =  $plugin_updates['plugins'];
			if( $plugins == '' ) {
				$plugins = '<p>Awesome wordpress plugins will be listed very soon.</p>';
			}
			
			$html = '<div class="fc-divider">
			 <ul class="fc-tabs fc-tabs-list">
			 
			  <li class=""><a href="#changelog" data-toggle="tab">Additional Useful Features in Pro Version</a></li>
			 </ul>
			 <div class="fc-tabs-container">
			  <div class="fc-tabs-content active" id="changelog">'.$changelog.'</div>
			</div>
			</div>';

			return $html;
		}
		function setup_plugin_info($pluginInfo) {

			foreach ( $pluginInfo as $pluginProperty => $value ) {
				$this->$pluginProperty = $value;
			}

		    $this->newVersion = unserialize(get_option( $this->productSlug.'_latest_version' ));

		}

		function get_mailchimp_integration_form() {

			$form = '';

			$form .= '<!-- Begin MailChimp Signup Form -->
<link href="//cdn-images.mailchimp.com/embedcode/slim-10_7.css" rel="stylesheet" type="text/css">
<style type="text/css">
	#mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
	/* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
	   We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
</style>
<div id="mc_embed_signup">
<form action="//flippercode.us10.list-manage.com/subscribe/post?u=eb646b3b0ffcb4c371ea0de1a&amp;id=3ee1d0075d" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
    <div id="mc_embed_signup_scroll">
	<label for="mce-EMAIL">Subscribe to our mailing list</label>
	<input type="email"  name="EMAIL" value="'.get_bloginfo('admin_email').'" class="email" id="mce-EMAIL" placeholder="email address" required>
    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_eb646b3b0ffcb4c371ea0de1a_3ee1d0075d" tabindex="-1" value=""></div>
    <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="fc-btn fc-btn-default"></div>
    </div>
</form>
</div>

<!--End mc_embed_signup-->';
			 return $form;

		}


		function init($pluginInfo) {

			$this->setup_plugin_info( $pluginInfo );

			foreach ( $this->commonBlocks as $block ) {

				switch ( $block ) {
				    case 'product-activation':
				    	
						$this->blockHeading = '<h1>Upgrade to Pro</h1>';

						$this->blockContent = '
                        <div class="fc-divider fcdoc-brow">
	                       	<div class="fc-2"><i class="fa fa-file-video-o" aria-hidden="true"></i></div>
	                       	<div class="fc-10">We have set up live examples where you can see the pro version in working mode.<br><br><strong><a href="'.$this->demoURL.'" target="_blank" class="fc-btn fc-btn-default">View Demos</a></strong>
	                         </div>
                        </div>';
						
						$is_update = false;
				    	if( is_array($this->newVersion) and isset($this->newVersion['new_version']) ) {
				    		if( version_compare($this->productVersion, $this->newVersion['new_version'])) {
				    			$is_update = true;
				    		}
				    	}

						$this->blockContent .= '<div class="fc-divider fcdoc-brow">
	                       	<div class="fc-2"><i class="fa fa-arrow-right" aria-hidden="true"></i></div>
	                       	<div class="fc-10">Pro Features :<br>Display Posts on Google Maps, Layers, Clustering, Custom Skins, Routes and many more...
							<div class="action">';
						if( $is_update  == true) {
							$plugin_status = 'Latest Version Available : <strong>'.$this->newVersion['new_version'].'</strong>';
							$plugin_action = '<span class="orangebg" name="plugin_update_status" id="plugin_update_status"><a class="codecanyon-link" href="http://www.codecanyon.net/downloads" target="_blank"><i class="fa fa-refresh" aria-hidden="true"></i>&nbsp;&nbsp;Update Available</a></span>';
							$status_class = 'orangebg';
						} else {
							$plugin_status = '';
							$status_class = '';
							$plugin_action = '';
						}
						$this->blockContent .='<br><a href="'.$this->demoURL.'" target="_blank" class="fc-btn fc-btn-default">View Premium Features</a>';
						$this->blockContent .='</div></div>';
						$this->blockContent .= '</div>';

				         break;

				    case 'product-updates':

				    	if ( true ) {
				      	    $this->blockClass = 'green';
				      	    $status = '<i class="fa fa-check" aria-hidden="true"></i>&nbsp;&nbsp;Plugin Up To Date';
					    } else {
					      	 $this->blockClass = 'orange';
					      	 $status = '<i class="fa fa-check" aria-hidden="true"></i>&nbsp;&nbsp;Update Available';
					    }
						// class="'.$this->blockClass.'"
						$this->blockHeading = '<div class="plugin-update-area">
					  <h1 class="full">Plugin Updates</h1>';
						$this->blockHeading .= '<span name="plugin_update_status" id="plugin_update_status"></span></div>';
						$this->blockContent = '
                        <div><br>Installed version :<br><strong>'.$this->productVersion.'</strong><br><div class="action">
                        <input type="button" class="fc-btn fc-btn-default check_for_updates_btn" name=" check_for_updates_btn" id=" check_for_updates_btn" value="Check Updates">
                          <div class="fcdoc-loader updatecheck">
                             <i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
							 <span class="sr-only">Loading...</span>
							</div></div><div class="latest_version_availalbe"></div></div>';
				         break;
				    case 'newsletter':
				         $this->blockHeading = '<h1>Subscribe Now</h1>';
						$this->blockContent = '
				      <div class="fc-divider fcdoc-brow"> 
	                       	<div class="fc-2"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
	                       	<div class="fc-10">Receive updates on our  new product features and new products effortlessly.		
	                         </div>
                        </div>
                        <div class="fc-divider fcdoc-brow"> 
	                       	<div class="fc-2"><i class="fa fa-thumbs-up" aria-hidden="true"></i></div>
	                       	<div class="fc-10">We will not share your email address in any case.		
	                         </div>
                        </div>';

						$this->blockContent .= $this->get_mailchimp_integration_form();

				    	break;

				    case 'product-support':
				         $this->blockHeading = '<h1>Product Support</h1>';
						 $this->blockContent = '
				      <div class="fc-divider fcdoc-brow"> 
	                       	<div class="fc-2"><i class="fa fa-file" aria-hidden="true"></i></div>
	                       	<div class="fc-10">For our each product we have very well explained starting guide to get you started in matter of minutes.<br><strong><a class="blue" href="'.$this->docURL.'" target="_blank"> Click Here</a></strong>
	                        </div>
                        </div>
                        <div class="fc-divider fcdoc-brow"> 
	                       	<div class="fc-2"><i class="fa fa-file-video-o" aria-hidden="true"></i></div>
	                       	<div class="fc-10">For our each product we have set up demo pages where you can see the plugin in working mode. You can see a working demo before making a purchase.<br><strong><a href="'.$this->demoURL.'" target="_blank" class="blue"> Click Here</a></strong>
	                         </div>
                        </div>';
				        break;

			        case 'socialmedia':
			        	 $this->blockHeading = '<h1>Be Our Friend</h1>';
						 $this->blockContent = '
                        <div class="fcdoc-brow">Stay connected and updated with what we are upto.
                        </div><br>
                        <div class="social-media-links">
                           <a href="https://profiles.wordpress.org/flippercode/" target="_blank"><i class="fa fa-wordpress" aria-hidden="true"></i></a>
                           <a href="https://www.facebook.com/flippercodepvtltd/" target="_blank"><i class="fa fa-facebook-official" aria-hidden="true"></i></a>
                           <a href="http://twitter.com/wpflippercode" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                           <a href="https://www.linkedin.com/company/2737561" target="_blank"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a>
                           <a href="https://plus.google.com/+Flippercode" target="_blank"><i class="fa fa-google-plus-official" aria-hidden="true"></i></a>
                         </div>';
			        break;

			        case 'suggestion-area':
				        $this->blockHeading = '<h1>Suggestion Box</h1>';
						$this->blockContent = '';
						$this->blockContent .= $this->get_suggestion_form();
			        break;

			        case 'refund-block':
						$this->blockHeading = '<h1>Get Refund</h1>';
						$this->blockContent = '<div class="fc-divider fcdoc-brow"> 
	                       	<div class="fc-2"><i class="fa fa-smile-o" aria-hidden="true"></i></div>
	                       	<div class="fc-10">Please click on the below button to initiate the refund process.<br><br><a target="_blank" class="fc-btn fc-btn-default refundbtn" href="http://codecanyon.net/refund_requests/new">Request a Refund</a></div></div>';
					break;
					case 'extended-support':
						$this->blockHeading = '<h1>Extended Technical Support</h1>';
						$this->blockContent = '<div class="fc-divider fcdoc-brow"> 
	                       	<div class="fc-2"><i class="fa fa-life-ring" aria-hidden="true"></i><br></div>
	                       	<div class="fc-10">We provide technical support for all of our products. You can opt for 12 months support below.<br><br>
	                         	<div class="support_btns"><a target="_blank" href="'.esc_url( $this->productSaleURL ).'" name="one_year_support" id="one_year_support" value="" class="fc-btn fc-btn-default support">Extend support</a>
	                       	    <a target="_blank" href="'.esc_url( $this->multisiteLicence ).'" name="multi_site_licence" id="multi_site_licence" class="fc-btn fc-btn-default supportbutton">Get Extended Licence</a></div>
	                         </div>

                    </div>';

					break;

				}
				$info = array( $this->blockHeading,$this->blockContent, $block );

				$this->commonBlockMarkup .= $this->get_block_markup( $info );

			}

		}

		function get_suggestion_form() {

			ob_start(); ?>
         
	         <form name="user-suggestion-form" id="user-suggestion-form" action="#" method="post">
	         <div class="fc-form-group"><input type="email" name="user-email" id="user-email" value="<?php echo get_bloginfo('admin_email'); ?>" placeholder="Your Email" />	
	         </div>
	         <textarea rows="5" name="user-suggestion" required id="user-suggestion" placeholder= "Do you have any suggestions to improve this product ?"></textarea>
	          <input type="button" class="fc-btn fc-btn-default submit-suggestion" name="submit-user-suggestion" id="submit-user-suggestion" name="submit-user-suggestion" value="Submit Suggestion">
	          <div class="fcdoc-loader submitsuggestion">
								 <i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
								 <span class="sr-only">Loading...</span>
			  </div>
	          <input type="hidden" name="suggestion-for" value="<?php echo $this->productTextDomain; ?>">
				<?php wp_nonce_field( 'user-suggestion-submitted','uss' ); ?>
	         </form>
	         
			<?php
			$suggestionForm = ob_get_contents();
			ob_clean();

			return $suggestionForm;

		}


		function get_block_markup($blockinfo) {

			$markup = '<div class="fc-6 fcdoc-blocks '.$blockinfo[2].'">
			                <div class="fcdoc-block-content">
			                    <div class="fcdoc-header">'.$blockinfo[0].'</div>
			                    <div class="fcdoc-body">'.$blockinfo[1].'</div>
			                </div>
            		   </div>';

			$this->productBlocksRendered++;
			if ( $this->productBlocksRendered % 2 == 0 ) {
				$markup .= '</div></div><div class="fc-divider"><div class="fcdoc-flexrow">'; }

			return $markup;

		}

		function renderBlocks() {
			/*
			$import_form = new FlipperCode_HTML_Markup(array('no_header' => true ));
				$import_form->set_header( __( 'Download', WPGMP_TEXT_DOMAIN ), $respone_upload_backup );

			$import_form->add_element('hidden', 'operation', array(
				'value' => 'download_plugin',
			));

			$import_form->add_element('hidden', 'product_id', array(
				'value' => 'wp-google-map-gold',
			));

			$import_form->add_element('html','purchase_code_instruction',array(
				'html' => __( 'We recommended to update your plugin as we released a new version for security, fixes and new functionality.',WPGMP_TEXT_DOMAIN ),
				'class' => 'fc-msg',
			));

			$import_form->add_element('text','purchase_code',array(
				'label' => __( 'Enter Purchase Code',WPGMP_TEXT_DOMAIN ),
				'desc' => __( 'Please enter your purchase code here.',WPGMP_TEXT_DOMAIN ),
				'class' => 'form-control',
			));

			$import_form->add_element('submit', 'wpgmp_update_plugin', array(
				'value' => __( 'Download & Install',WPGMP_TEXT_DOMAIN ),
			));
			
			$download_form = $import_form->render(false);
			*/
			$modalArgs = array( 'fc_modal_header' => __('Download WP Google Map Pro 4.0.0',WFIP_TEXT_DOMAIN),
					'fc_modal_content' => $download_form,
					'fc_modal_initiator' => '.fc-open-modal',
					'class' => 'fc-modal' );

 			$modal_window = FlipperCode_HTML_Markup::field_fc_modal('fc_overview_modal', $modalArgs);

			$this->finalproductOverviewMarkup = $this->commonBlockMarkup.$this->pluginSpecificBlockMarkup.$modal_window;
			echo $this->finalproductOverviewMarkup;

		}

	}

}
