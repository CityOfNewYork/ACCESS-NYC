<?php

/*
 * Plugin Name:  Send Me NYC
 * Description:  Email/SMS gateway for saving links for onesself
 * Author:       Blue State Digital
 * Requirements: The plugin doesn't include dependencies. These should be added
 *               to the root Composer file for the site (composer require ...)
 *               twilio/sdk: ^5.32,
 *               aws/aws-sdk-php: ^3.99
*/

namespace SMNYC;

if (!defined('WPINC')) {
	die; //no direct access
}

require plugin_dir_path( __FILE__ ) . 'SMSMe.php';
require plugin_dir_path( __FILE__ ) . 'EmailMe.php';

new SMSMe;
new EmailMe;


/**
 * Public-facing convenience functions
 **/
function get_current_url(){
	global $wp;
	return home_url(esc_url(add_query_arg(NULL, NULL)));
}

function hash( $data ) {
	return wp_create_nonce( 'bsd_smnyc_token_'.$data );
}


add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ), '\SMNYC\settings_link' );
add_action( 'admin_menu', '\SMNYC\add_settings_page' );
function add_settings_page() {
	add_options_page(
		'Send Me NYC Settings',
		'Send Me NYC',
		'manage_options',
		'smnyc_config',
		'\SMNYC\settings_content'
	);
}
function settings_content(){ ?>
<div class="wrap">
	<h1>Send Me NYC Settings</h1>

	<form method="post" action="options.php">
		<?php
			do_settings_sections( 'smnyc_config' );
			settings_fields( 'smnyc_settings' );
			submit_button();
		?>
	</form>
</div>
<?php }
function settings_link( $links ) {
	$settings_link = '<a href="'.esc_url( add_query_arg( 'page','smnyc_config',admin_url('options-general.php'))).'">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
