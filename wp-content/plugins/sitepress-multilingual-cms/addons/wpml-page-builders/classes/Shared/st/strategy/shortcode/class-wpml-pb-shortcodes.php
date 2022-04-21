<?php

class WPML_PB_Shortcodes {

	/** @var  WPML_PB_Shortcode_Strategy $shortcode_strategy */
	private $shortcode_strategy;

	/** @var bool $is_wrapping_regular_text */
	private $is_wrapping_regular_text = false;

	public function __construct( WPML_PB_Shortcode_Strategy $shortcode_strategy ) {
		$this->shortcode_strategy = $shortcode_strategy;
	}

	public function get_shortcodes( $content ) {

		$shortcodes = array();
		$pattern    = get_shortcode_regex( $this->shortcode_strategy->get_shortcodes() );

		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) && isset( $matches[5] ) && ! empty( $matches[5] ) ) {
			for ( $index = 0; $index < sizeof( $matches[0] ); $index ++ ) {
				$shortcode = array(
					'block'      => $matches[0][ $index ],
					'tag'        => $matches[2][ $index ],
					'attributes' => $matches[3][ $index ],
					'content'    => $matches[5][ $index ],
				);

				$nested_shortcodes = array();
				if ( $shortcode['content'] ) {
					if ( $this->needs_wrapping_regular_text( $shortcode['content'] ) ) {
						$this->is_wrapping_regular_text = true;
						$shortcode['content']           = $this->wrap_regular_text( $shortcode['content'] );
					}

					$nested_shortcodes              = $this->get_shortcodes( $shortcode['content'] );
					$this->is_wrapping_regular_text = false;
					if ( count( $nested_shortcodes ) ) {
						$shortcode['content'] = '';
					}
				}

				if ( count( $nested_shortcodes ) ) {
					$shortcodes = array_merge( $shortcodes, $nested_shortcodes );
				}
				$shortcodes[] = $shortcode;
			}
		}

		return $shortcodes;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	private function wrap_regular_text( $content ) {
		$wrapper = new WPML_PB_Shortcode_Content_Wrapper( $content, $this->shortcode_strategy->get_shortcodes() );
		return $wrapper->get_wrapped_content();
	}

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	private function needs_wrapping_regular_text( $content ) {
		if ( $this->is_wrapping_regular_text ) {
			return false;
		}

		return WPML_PB_Shortcode_Content_Wrapper::isStrippedContentDifferent( $content );
	}
}
