<?php
/**
 * @author OnTheGo Systems
 */
class WPML_TM_XLIFF_Shortcodes extends WPML_TM_XLIFF_Phase {
	const SHORTCODE_STORE_OPTION_KEY = 'wpml_xliff_shortcodes';

	/**
	 * @return string
	 */
	protected function get_data() {
		return implode( ',', $this->get_shortcodes() );
	}

	/**
	 * @return array
	 */
	private function get_shortcodes() {
		global $shortcode_tags;

		$registered_shortcodes = array();

		if ( $shortcode_tags ) {
			$registered_shortcodes = array_keys( $shortcode_tags );
		}

		$stored_shortcodes = get_option( self::SHORTCODE_STORE_OPTION_KEY, array() );

		return $this->get_sanitized_shortcodes( $registered_shortcodes, $stored_shortcodes );
	}

	private function get_sanitized_shortcodes( array $shortcodes1, array $shortcodes2 ) {
		return array_unique( array_filter( apply_filters( 'wpml_shortcode_list', array_merge( $shortcodes1, $shortcodes2 ) ) ) );
	}

	protected function get_phase_name() {
		return 'shortcodes';
	}

	protected function get_process_name() {
		return 'Shortcodes identification';
	}
}
