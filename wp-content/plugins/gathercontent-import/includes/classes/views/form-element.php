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
	 * @since  3.0.0
	 *
	 * @param  boolean $echo Whether to output or return the template
	 *
	 * @return string        Rendered template
	 */
	public function load( $echo = true ) {

		$content = $this->element();

		if ( $desc = $this->get( 'desc' ) ) {
			$content .= '<p class="description">'. $desc .'</p>';
		}

		if ( $echo ) {
			echo $content;
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
