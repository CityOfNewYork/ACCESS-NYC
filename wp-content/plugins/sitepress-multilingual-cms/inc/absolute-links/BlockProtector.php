<?php

namespace WPML\AbsoluteLinks;

class BlockProtector {

	private $protectedBlocks = [];

	public function protect( $text ) {
		if ( ! function_exists( 'has_blocks' ) || ! has_blocks( $text ) ) {
			return $text;
		}

		$integrationClass = \WPML_Gutenberg_Integration::class;

		$decodeForwardSlashes = function ( $str ) {
			return str_replace( '\\/', '/', $str );
		};

		$replaceBlockWithPlaceholder = function ( $text, $block ) {
			$key                           = md5( $block );
			$this->protectedBlocks[ $key ] = $block;

			return str_replace( $block, $key, $text );
		};

		return wpml_collect( \WPML_Gutenberg_Integration::parse_blocks( $text ) )
			->map( [ $integrationClass, 'sanitize_block' ] )
			->filter( [ $integrationClass, 'has_non_empty_attributes' ] )
			->map( [ $integrationClass, 'render_block' ] )
			->map( $decodeForwardSlashes )
			->reduce( $replaceBlockWithPlaceholder, $text );
	}

	public function unProtect( $text ) {
		foreach ( $this->protectedBlocks as $key => $value ) {
			$text = str_replace( $key, $value, $text );
		}

		return $text;
	}
}
