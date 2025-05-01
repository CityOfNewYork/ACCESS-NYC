<?php
/**
 * /premium/network-options.php
 *
 * Relevanssi Premium network options menu.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Adds the network level menu for Relevanssi Premium.
 *
 * @global array $relevanssi_variables The Relevanssi variables array, used for the plugin file name.
 */
function relevanssi_network_menu() {
	global $relevanssi_variables;
	RELEVANSSI_PREMIUM ? $name = 'Relevanssi Premium' : $name = 'Relevanssi';
	add_menu_page(
		$name,
		$name,
		/**
		 * Capability required to see the Relevanssi network options.
		 *
		 * The capability level required to see the Relevanssi Premium network options.
		 *
		 * @since Unknown
		 *
		 * @param string $capability The capability required. Default 'manage_options'.
		 */
		apply_filters( 'relevanssi_options_capability', 'manage_options' ),
		$relevanssi_variables['file'],
		'relevanssi_network_options'
	);
}

/**
 * Prints out the Relevanssi Premium network options.
 *
 * @global array $relevanssi_variables The Relevanssi variables array, used for the plugin file name.
 */
function relevanssi_network_options() {
	global $relevanssi_variables;

	printf( '<div class="wrap"><h2>%s</h2>', esc_html__( 'Relevanssi network options', 'relevanssi' ) );

	if ( ! empty( $_POST ) ) { // WPCS: Input var okay.
		if ( isset( $_REQUEST['submit'] ) ) { // WPCS: Input var okay.
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_network_options' );
			relevanssi_update_network_options();
		}
		if ( isset( $_REQUEST['copytoall'] ) ) { // WPCS: Input var okay.
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_network_options' );
			relevanssi_copy_options_to_subsites( $_REQUEST ); // WPCS: Input var okay.
		}
	}

	$this_page = '?page=relevanssi/relevanssi.php';
	if ( RELEVANSSI_PREMIUM ) {
		$this_page = '?page=relevanssi-premium/relevanssi.php';
	}

	printf( "<form method='post' action='admin.php%s'>", esc_attr( $this_page ) );

	wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_network_options' );

	?>
	<table class="form-table">
	<?php
	relevanssi_form_api_key( 'network' );
	?>
	</table>
	<input type='submit' name='submit' value='<?php esc_attr_e( 'Save the options', 'relevanssi' ); ?>' class='button button-primary' />
</form>

<h2><?php esc_html_e( 'Copy options from one site to other sites', 'relevanssi' ); ?></h2>
<p><?php esc_html_e( "Choose a blog and copy all the options from that blog to all other blogs that have active Relevanssi Premium. Be careful! There's no way to undo the procedure!", 'relevanssi' ); ?></p>

<form id='copy_config' method='post' action='admin.php?page=relevanssi-premium/relevanssi.php'>
	<?php wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_network_options' ); ?>

<table class="form-table">
<tr>
	<th scope="row"><?php esc_html_e( 'Copy options', 'relevanssi' ); ?></th>
	<td>
	<?php

	$raw_blog_list = get_sites( array( 'number' => 50 ) );
	$blog_list     = array();
	foreach ( $raw_blog_list as $blog ) {
		$details                         = get_blog_details( $blog->blog_id );
		$blog_list[ $details->blogname ] = $blog->blog_id;
	}
	ksort( $blog_list );
	echo "<select id='sourceblog' name='sourceblog'>";
	foreach ( $blog_list as $name => $id ) {
		echo "<option value='" . esc_attr( $id ) . "'>" . esc_html( $name ) . '</option>';
	}
	echo '</select>';

	?>
	<input type='submit' name='copytoall' value='<?php esc_attr_e( 'Copy options to all other subsites', 'relevanssi' ); ?>' class='button button-primary' />
	</td>
</tr>
</table>
</form>
</div>
	<?php
}

/**
 * Saves the network options.
 *
 * @global array $relevanssi_variables Relevanssi global variables, used to check the plugin file name.
 *
 * Saves the Relevanssi Premium network options.
 */
function relevanssi_update_network_options() {
	global $relevanssi_variables;

	if ( empty( $_REQUEST['relevanssi_api_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		unset( $_REQUEST['relevanssi_api_key'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	if ( isset( $_REQUEST['relevanssi_remove_api_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		update_site_option( 'relevanssi_api_key', '' );
	}
	if ( isset( $_REQUEST['relevanssi_api_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$value = sanitize_text_field( wp_unslash( $_REQUEST['relevanssi_api_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		update_site_option( 'relevanssi_api_key', $value );
	}
}

/**
 * Copies options from one subsite to other subsites.
 *
 * @global $wpdb The WordPress database interface.
 *
 * @param array $data Copy parameters.
 */
function relevanssi_copy_options_to_subsites( $data ) {
	if ( ! isset( $data['sourceblog'] ) ) {
		return;
	}
	$sourceblog = $data['sourceblog'];
	if ( ! is_numeric( $sourceblog ) ) {
		return;
	}
	$sourceblog = esc_sql( $sourceblog );

	/* translators: %s has the source blog ID */
	printf( '<h2>' . esc_html__( 'Copying options from blog %s', 'relevanssi' ) . '</h2>', esc_html( $sourceblog ) );
	global $wpdb;
	switch_to_blog( $sourceblog );
	$q = "SELECT * FROM $wpdb->options WHERE option_name LIKE 'relevanssi%'";
	restore_current_blog();

	$results = $wpdb->get_results( $q ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	$blog_list = get_sites( array( 'number' => 50 ) );
	foreach ( $blog_list as $blog ) {
		if ( $blog->blog_id === $sourceblog ) {
			continue;
		}
		switch_to_blog( $blog->blog_id );

		/* translators: %s is the blog ID */
		printf( '<p>' . esc_html__( 'Processing blog %s:', 'relevanssi' ) . '<br />', esc_html( $blog->blog_id ) );
		if ( ! is_plugin_active( 'relevanssi-premium/relevanssi.php' ) ) {
			echo esc_html__( 'Relevanssi is not active in this blog.', 'relevanssi' ) . '</p>';
			continue;
		}
		foreach ( $results as $option ) {
			if ( is_serialized( $option->option_value ) ) {
				$value = unserialize( $option->option_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			} else {
				$value = $option->option_value;
			}
			update_option( $option->option_name, $value );
		}
		echo esc_html__( 'Options updated.', 'relevanssi' ) . '</p>';
		restore_current_blog();
	}
}
