<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\PB\Gutenberg\StringsInBlock\Attributes;

class BlockAttributes extends Base {

	/** @var array $attributesToConvert */
	private $attributesToConvert;

	public function __construct( array $attributesToConvert ) {
		$this->attributesToConvert = $attributesToConvert;
	}

	public function convert( array $block ) {
		if ( ! isset( $block['attrs'] ) ) {
			return $block;
		}

		foreach ( $this->attributesToConvert as $attributeConfig ) {
			$getConfig = Obj::prop( Fns::__, $attributeConfig );
			$name      = $getConfig( 'name' );

			if ( $name ) {
				$block['attrs'] = self::convertByName( $block['attrs'], $name, $getConfig );
			} elseif ( $getConfig( 'path' ) ) {
				$path           = explode( '>', $getConfig( 'path' ) );
				$block['attrs'] = self::convertByPath( $block['attrs'], $path, $getConfig );
			}
		}

		return $block;
	}

	/**
	 * @param array    $attrs
	 * @param string   $name
	 * @param callable $getConfig
	 *
	 * @return array
	 */
	private function convertByName( $attrs, $name, $getConfig ) {
		if ( isset( $attrs[ $name ] ) ) {
			$attrs[ $name ] = self::convertIds(
				$attrs[ $name ],
				$getConfig( 'slug' ),
				$getConfig( 'type' )
			);
		}

		return $attrs;
	}

	/**
	 * @param array|string|int $attrs
	 * @param array            $path
	 * @param callable         $getConfig
	 *
	 * @return mixed
	 */
	private function convertByPath( $attrs, $path, $getConfig ) {
		$currentKey  = reset( $path );
		$nextPath    = Lst::drop( 1, $path );
		$hasWildCard = false !== strpos( $currentKey, '*' );

		if ( $hasWildCard && is_array( $attrs ) ) {
			$regex = Attributes::getWildcardRegex( $currentKey );

			foreach ( $attrs as $key => $attr ) {
				if ( Str::match( $regex, $key ) ) {
					$attrs[ $key ] = self::convertByPath( $attr, $nextPath, $getConfig );
				}
			}
		} elseif ( $currentKey && isset( $attrs[ $currentKey ] ) ) {
			$attrs[ $currentKey ] = self::convertByPath( $attrs[ $currentKey ], $nextPath, $getConfig );
		} elseif ( ! $nextPath && is_scalar( $attrs ) ) {
			$attrs = self::convertIds( $attrs, $getConfig( 'slug' ), $getConfig( 'type' ) );
		}

		return $attrs;
	}
}
