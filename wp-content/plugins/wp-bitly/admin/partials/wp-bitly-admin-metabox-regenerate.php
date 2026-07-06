<?php
/**
 * Display the WP Bitly Metabox on enabled posts
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Wp_bitly
 * @subpackage Wp_bitly/admin/partials
 */

?>

<?php
if ( $shortlink ) {
	$text = 'Regenerate';
} else {
	$text = 'Generate new Shortlink';
}
?>

<div class="wpbitly-spacer"></div>


<div id="wpbitly-actions">
	<div id="regenerate-action">
		<?php
		$regenerate_url = add_query_arg(
			array(
				'wpbr'     => 'true',
				'_wpnonce' => wp_create_nonce( 'wpbitly_regenerate' ),
			),
			$request_uri
		);
		?>
		<a href="<?php echo esc_url( $regenerate_url ); ?>" class="regeneratelink"><?php echo esc_attr( $text ); ?></a>
	</div>
	<div class="clear"></div>

	
	<div class="clear"></div>
</div>