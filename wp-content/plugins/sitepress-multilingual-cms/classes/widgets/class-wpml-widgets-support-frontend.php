<?php

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks;

/**
 * This code is inspired by WPML Widgets (https://wordpress.org/plugins/wpml-widgets/),
 * created by Jeroen Sormani
 *
 * @author OnTheGo Systems
 */
class WPML_Widgets_Support_Frontend implements IWPML_Action {

	/**
	 * @see \WPML\PB\Gutenberg\Widgets\Block\DisplayTranslation::PRIORITY_BEFORE_REMOVE_BLOCK_MARKUP
	 */
	const PRIORITY_AFTER_TRANSLATION_APPLIED = 0;

	/** @var array $displayFor */
	private $displayFor;

	/**
	 * WPML_Widgets constructor.
	 *
	 * @param string $current_language
	 */
	public function __construct( $current_language ) {
		$this->displayFor = [ null, $current_language, 'all' ];
	}

	public function add_hooks() {
		add_filter( 'widget_block_content', [ $this, 'filterByLanguage' ], self::PRIORITY_AFTER_TRANSLATION_APPLIED );
		add_filter( 'widget_display_callback', [ $this, 'display' ], - PHP_INT_MAX );
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function filterByLanguage( $content ) {
		$render = function () use ( $content ) {
			return wpml_collect( parse_blocks( $content ) )
				->map( Fns::unary( 'render_block' ) )
				->reduce( Str::concat(), '' );
		};

		return Hooks::callWithFilter( $render, 'pre_render_block', [ $this, 'shouldRender' ], 10, 2 );
	}

	/**
	 * Determine if a block should be rendered depending on its language
	 * Returning an empty string will stop the block from being rendered.
	 *
	 * @param string|null $pre_render The pre-rendered content. Default null.
	 * @param array $block The block being rendered.
	 *
	 * @return string|null
	 */
	public function shouldRender( $pre_render, $block ) {
		return Lst::includes( Obj::path( [ 'attrs', 'wpml_language' ], $block ), $this->displayFor ) ? $pre_render : '';
	}

	/**
	 * Get display status of the widget.
	 *
	 * @param array|bool $instance
	 *
	 * @return array|bool
	 */
	public function display( $instance ) {
		if (
			! $instance ||
			( is_array( $instance ) && $this->it_must_display( $instance ) )
		) {
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
		return Lst::includes( Obj::propOr( null, 'wpml_language', $instance ), $this->displayFor );
	}
}
