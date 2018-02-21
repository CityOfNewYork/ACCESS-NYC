<?php
/*
 * Load All Core Initialisation classes
 * @author Flipper Code <hello@flippercode.com>
 * @package Core
 * Author URL : http://www.flippercode.com/
 * Version 1.0.0
****/ 

if ( ! class_exists( 'FlipperCode_Initialise_Core_Lite' ) ) {


	 class FlipperCode_Initialise_Core_Lite {
	
			public function __construct() {
				$this->_load_core_files();
				$this->_register_flippercode_globals();
			}
		
			public function _register_flippercode_globals() {

				if(is_admin()) {	
					add_action('admin_head',array( $this, 'hook_in_admin_header' ));
					add_action( 'wp_ajax_submit_user_suggestion',array( $this, 'submit_user_suggestion' ) );
				}
							
			}
		
		function hook_in_admin_header() { ?>
			
			    <style>
				.fc-pro-features li {background: #f9f9f9;padding: 5px 10px;font-size: .9em!important;}
				.fc-pro-features li > *{display: inline-block;vertical-align: middle;}
				.fc-pro-features a,.fc-pro-features a:hover{color: #eca204!important;font-weight: bold;}
				#changelog a.fc-buy-btn{background: #f1c40f!important;}
				.ptheading{color: #B7950B;font-weight: bold!important;font-size: 16px!important;}
				</style>
				<script>var fcajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";</script>
		<?php }
		
		function submit_user_suggestion() {

				$current_user = wp_get_current_user();
				if (isset( $_POST['action'] )
				&& $_POST['action'] == 'submit_user_suggestion'
				&& isset( $_POST['uss'] )
				&& wp_verify_nonce($_POST['uss'],'user-suggestion-submitted')
				)
				{
					$data = $_POST;
					$current_user = wp_get_current_user();
					$sitename = get_bloginfo('name');
					$username = $current_user->user_nicename;
					$siteURL = get_bloginfo('url');
					$siteadminemail = get_bloginfo('admin_email');
					$suggestion = sanitize_text_field($data['suggestion']);
					$suggestionfor = sanitize_text_field($data['suggestionfor']);
					$url = 'http://plugins.flippercode.com/wunpupdates/';
					$bodyargs = array( 'wunpu_action' => 'submit-suggestion',
									   'username' =>   $username,
									   'sitename' =>   $sitename,
									   'siteurl' =>    urlencode($siteURL),
									   'useremail' =>  $siteadminemail,
									   'suggestion' => $suggestion,
									   'suggestion_for' => $suggestionfor);
					$args = array('method' => 'POST', 'timeout' => 45, 'body' => $bodyargs );
					$response = wp_remote_post($url,$args);
					if ( is_wp_error( $response ) ) {
					$result = array('status' => '0','error' => $response->get_error_message()) ;
					} else {
					$result = array('status' => '1','submission_saved' => $response['body']);
					echo $response['body'];

					}
				 }else {
					echo 'failed';
				}

				exit;

			}
		
		 	
		public function _load_core_files() {
			
			$corePath  = plugin_dir_path( __FILE__ );
			$backendCoreFiles = array(
				'class.tabular.php',
				'class.template.php',
				'class.controller-factory.php',
				'class.model-factory.php',
				'class.controller.php',
				'class.model.php',
				'class.validation.php',
				'class.database.php',
				'class.importer.php',
				'class.plugin-overview.php',
				'class.emails.php',
				'class.widget-builder.php'
			);
			
			$frontendCoreFiles = array(
				'class.controller-factory.php',
				'class.model-factory.php',
				'class.emails.php',
				'class.model.php',
				'class.database.php',
				'class.widget-builder.php',
				'class.template.php'
			);

			foreach ( $backendCoreFiles as $file ) {

				if ( file_exists( $corePath.$file ) and is_admin() ) {
					require_once( $corePath.$file );
				}
			}
			
			foreach ( $frontendCoreFiles as $file ) {

				if ( file_exists( $corePath.$file )  ) {
					require_once( $corePath.$file );
				}
			}

		}

	 }
	 return new FlipperCode_Initialise_Core_Lite();

}
