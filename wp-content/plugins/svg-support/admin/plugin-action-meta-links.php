<?php
/**
 * PLUGIN ACTION & ROW META LINKS
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// add plugin_action_links
add_filter( 'plugin_action_links_' . $plugin_file, 'bodhi_svgs_plugin_action_links' );

function bodhi_svgs_plugin_action_links( $links ) {

   $links[] = '<a href="'. get_admin_url(null, 'options-general.php?page=svg-support') .'">Settings</a>';
   //$links[] = '<a href="http://gowebben.com" target="_blank">More plugins by GoWebben</a>';
   return $links;

}

// add plugin_row_meta links
add_filter( 'plugin_row_meta', 'bodhi_svgs_plugin_meta_links', 10, 2 );

function bodhi_svgs_plugin_meta_links( $links, $file ) {

	$plugin_file = 'svg-support/svg-support.php';
	if ( $file == $plugin_file ) {
		return array_merge(
			$links,
			array(
				'<a target="_blank" href="http://wordpress.org/support/plugin/svg-support">' . __( 'Get Support', 'svg-support') . '</a>',
				'<a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F7W2NUFAVQGW2">' . __( 'Donate to author', 'svg-support') . '</a>',
				'<a target="_blank" href="https://secure.gowebben.com/cart.php?promocode=SVGSUPPORT">' . __( '$25 Free Credit from GoWebben', 'svg-support') . '</a>'
			)
		);
	}

	return $links;

}

?>