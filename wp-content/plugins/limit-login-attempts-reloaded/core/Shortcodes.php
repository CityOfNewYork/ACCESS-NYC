<?php

if( !defined( 'ABSPATH' ) ) exit();

/**
 * Class LLA_Shortcodes
 */
class LLA_Shortcodes {

	/**
	 * Register all shortcodes
	 */
	public function register() {

		add_shortcode( 'llar-link', array( $this, 'llar_link_callback' ) );
	}

	/**
	 * [llar-link url="" text=""] callback
	 *
	 * @param $atts
	 * @return string
	 */
	public function llar_link_callback( $atts ) {

		$atts = shortcode_atts( array(
			'url' 	=> '#',
			'text' 	=> 'Link'
		), $atts );

		return '<a href="' . esc_attr( $atts['url'] ) . '" target="_blank">' . esc_html( $atts['text'] ) . '</a>';
	}

}
(new LLA_Shortcodes())->register();