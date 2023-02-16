<?php

use WPML\FP\Lst;
use WPML\FP\Obj;

/**
 * This code is inspired by WPML Widgets (https://wordpress.org/plugins/wpml-widgets/),
 * created by Jeroen Sormani
 *
 * @author OnTheGo Systems
 */
class WPML_Widgets_Support_Frontend implements IWPML_Action {
	private $current_language;

	/**
	 * WPML_Widgets constructor.
	 *
	 * @param string $current_language
	 */
	public function __construct( $current_language ) {
		$this->current_language = $current_language;
	}

	public function add_hooks() {
		add_filter( 'widget_display_callback', array( $this, 'display' ), - PHP_INT_MAX, 1 );
	}

	/**
	 * Get display status of the widget.
	 *
	 * @param array|bool $instance
	 *
	 * @return array|bool
	 */
	public function display( $instance ) {
		if ( ! $instance || $this->it_must_display( $instance ) ) {
			return $instance;
		}

		return false;
	}

	/**
	 * Returns display status of the widget as boolean.
	 *
	 * @param array $instance
	 *
	 * @return bool
	 */
	private function it_must_display( $instance ) {
		if ( !isset ( $instance['wpml_language'] ) && isset ( $instance['content'] ) ) {
			$blocks = parse_blocks( $instance['content'] );
			$instance = $this->find_wpml_language_element_in_multidimensional_array( $blocks );
		}

		return Lst::includes( Obj::propOr( null, 'wpml_language', $instance ), [ null, $this->current_language, 'all' ] );
	}

	private function find_wpml_language_element_in_multidimensional_array( $arr ) {
		$wpml_lang_arr = [];
		array_walk_recursive( $arr, function( $value, $key ) use ( &$wpml_lang_arr ) {
			if ( $key === 'wpml_language' ) {
				$wpml_lang_arr[ $key ] = $value;
			}
		});
		return $wpml_lang_arr;
	}

}
