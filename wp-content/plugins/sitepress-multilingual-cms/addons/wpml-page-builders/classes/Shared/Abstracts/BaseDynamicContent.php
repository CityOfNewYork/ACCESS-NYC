<?php

namespace WPML\Compatibility;

use SitePress;

abstract class BaseDynamicContent implements \IWPML_DIC_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action {

	/** @var SitePress */
	private $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Add filters and actions.
	 */
	public function add_hooks() {
		if ( $this->sitepress->is_setup_complete() ) {
			add_filter( 'wpml_pb_shortcode_decode', [ $this, 'decode_dynamic_content' ], 10, 2 );
			add_filter( 'wpml_pb_shortcode_encode', [ $this, 'encode_dynamic_content' ], 10, 2 );
		}
	}

	/**
	 * Sets dynamic content to be translatable.
	 *
	 * @param string $string   The decoded string so far.
	 * @param string $encoding The encoding used.
	 *
	 * @return string|array
	 */
	abstract public function decode_dynamic_content( $string, $encoding );

	/**
	 * Rebuilds dynamic content with translated strings.
	 *
	 * @param string|array $string   The field array or string.
	 * @param string       $encoding The encoding used.
	 *
	 * @return string
	 */
	abstract public function encode_dynamic_content( $string, $encoding );

	/**
	 * Check if a certain field contains dynamic content.
	 *
	 * @param string $string The string to check.
	 *
	 * @return bool
	 */
	abstract protected function is_dynamic_content( $string );

	/**
	 * Decode a dynamic-content field.
	 *
	 * @param string $string The string to decode.
	 *
	 * @return array
	 */
	abstract protected function decode_field( $string );

	/**
	 * Encode a dynamic-content field.
	 *
	 * @param array $field The field to encode.
	 *
	 * @return string
	 */
	abstract protected function encode_field( $field );
}
