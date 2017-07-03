<?php

class WPML_String_Shortcode {
	private $context;
	private $name;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	function init_hooks() {
		add_shortcode( 'wpml-string', array( $this, 'shortcode' ) );
	}

	/**
	 * @param array  $attributes
	 * @param string $value
	 *
	 * @return string
	 */
	function shortcode( $attributes, $value ) {
		$this->parse_attributes( $attributes, $value );
		$this->maybe_register_string( $value );

		return do_shortcode( icl_t( $this->context, $this->name, $value ) );
	}

	/**
	 * @param string $value
	 */
	private function maybe_register_string( $value ) {
		$query  = 'SELECT id, value, status FROM ' . $this->wpdb->prefix . 'icl_strings WHERE context=%s AND name=%s';
		$sql    = $this->wpdb->prepare( $query, $this->context, $this->name );
		$string = $this->wpdb->get_row( $sql );
		if ( ! $string || $string->value !== $value ) {
			icl_register_string( $this->context, $this->name, $value );
		}
	}

	/**
	 * @param array  $attributes
	 * @param string $value
	 */
	private function parse_attributes( $attributes, $value ) {
		$pairs = array(
			'context' => 'wpml-shortcode',
			'name'    => 'wpml-shortcode-' . md5( $value ),
		);

		$attributes = shortcode_atts( $pairs, $attributes );

		$this->context = $attributes['context'];
		$this->name    = $attributes['name'];
	}
}
