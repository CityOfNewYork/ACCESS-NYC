<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_Shortcodes_Catcher implements IWPML_Action {

	public function add_hooks() {
		add_filter( 'pre_do_shortcode_tag', array( $this, 'register_shortcode' ), 10, 2 );
	}

	public function register_shortcode( $return, $tag ) {
		if ( $tag ) {
			$registered_shortcodes = get_option( WPML_TM_XLIFF_Shortcodes::SHORTCODE_STORE_OPTION_KEY, array() );
			if ( ! in_array( $tag, $registered_shortcodes, true ) ) {
				$registered_shortcodes[] = $tag;
				update_option( WPML_TM_XLIFF_Shortcodes::SHORTCODE_STORE_OPTION_KEY, $registered_shortcodes, false );
			}
		}

		return $return;
	}
}
