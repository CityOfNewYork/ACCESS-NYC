<?php
if ( defined( 'WP_ADMIN' ) ) {
	return;
}

add_action( 'template_redirect', 'icl_language_canonical_redirects', 1 );

function icl_language_canonical_redirects() {
	global $wp_query, $sitepress_settings;
	if ( 3 == $sitepress_settings['language_negotiation_type'] && is_singular() && empty( $wp_query->posts ) ) {
		$pid       = get_query_var( 'p' );
		$permalink = $pid ? get_permalink( $pid ) : false;
		$permalink = $permalink ? html_entity_decode( $permalink ) : false;
		if ( $permalink ) {
			wp_redirect( $permalink, 301 );
			exit;
		}
	}
}
