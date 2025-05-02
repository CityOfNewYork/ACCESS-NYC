<?php

namespace GatherContent\Importer\Views;

abstract class Form_Element extends View {

	protected $default_attributes = array(
		'id'    => '',
		'name'  => '',
		'value' => '',
		'desc'  => '',
	);

	public function __construct( $template, array $args = array() ) {
		$this->args = is_array( $template ) ? $template : $args;
	}

	/**
	 * Loads the view and outputs it
	 *
	 * @param boolean $echo Whether to output or return the template
	 *
	 * @return string        Rendered template
	 * @since  3.0.0
	 *
	 */
	public function load( $echo = true ) {

		$content = $this->element();

		if ( $desc = $this->get( 'desc' ) ) {
			$content .= '<p class="description">' . $desc . '</p>';
		}

		/*
		 * It's not reasonable escape the content here, as it contains various different types.
		 * However, it is data from our plugin and our own API, so we can trust it.
		 */
		if ( $echo ) {
			// Affixing _safe as instructed by https://developer.wordpress.org/apis/security/escaping/#toc_4
			$content_safe = $content;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content_safe;
		}

		return $content;
	}

	abstract protected function element();

	protected function attributes() {
		$attributes = wp_parse_args( $this->args, $this->default_attributes );

		unset( $attributes['desc'] );

		return $attributes;
	}

}
