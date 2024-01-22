<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\PB\Elementor\Config\DynamicElements\Provider;
use function WPML\FP\curryN;
use function WPML\FP\spreadArgs;

class DynamicElements implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	public function add_hooks() {
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'convert' ] );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function convert( array $data ) {
		// $convertTag :: (curried) string -> string -> string -> string
		$convertTag = curryN( 3, [ __CLASS__, 'convertTag' ] );

		// $assignConvertCallable :: (callable, callable, string|null, string|null) -> array
		$assignConvertCallable = function( $shouldConvert, $lens, $allowedTag = null, $idKey = null ) use ( $convertTag ) {
			if ( $allowedTag && $idKey ) {
				$convert = $convertTag( $allowedTag, $idKey );
			} else {
				$convert = [ __CLASS__, 'convertId' ];
			}

			return [ $shouldConvert, Obj::over( $lens, $convert ) ];
		};
		
		/**
		 * Filter widget dynamic id conversion configuration.
		 *
		 * Gather conversion configuration for Elementor widget dynamic ids.
		 * The id can be stored in a widget key, or in a shortcode string.
		 *
		 * @since 2.0.4
		 *
		 * @param array $args {
		 *     @type array $configuration {
		 *         Conversion configuration.
		 *
		 *         @type callable $shouldConvert Check if the widget should be converted.
		 *         @type callable $keyLens       Lens to the key that holds the dynamic id.
		 *         @type string   $tagName       Optional. Shortcode name attribute.
		 *         @type string   $idKey         Optional. Id key in the shortcode's settings attribute.
		 *     }
		 * }
		 */
		$converters = apply_filters( 'wpml_pb_elementor_widget_dynamic_id_converters', Provider::get() );

		$converters = wpml_collect( $converters )
			->map( spreadArgs( $assignConvertCallable ) )
			->toArray();

		return $this->applyConverters( $data, $converters );
	}

	/**
	 * @param array $data
	 * @param array $converters
	 *
	 * @return array
	 */
	private function applyConverters( $data, $converters ) {
		foreach ( $data as &$item ) {
			foreach ( $converters as $converter ) {
				list( $shouldConvert, $convert ) = $converter;

				if ( $shouldConvert( $item ) ) {
					$item = $convert( $item );
				}
			}

			$item['elements'] = $this->applyConverters( $item['elements'], $converters );
		}

		return $data;
	}

	/**
	 * @param string      $allowedTag
	 * @param string      $idKey
	 * @param string|null $tagString
	 *
	 * @return string|null
	 */
	public static function convertTag( $allowedTag, $idKey, $tagString ) {
		if ( ! $tagString ) {
			return $tagString;
		}

		preg_match( '/name="(.*?(?="))"/', $tagString, $tagNameMatch );

		if ( ! $tagNameMatch || $tagNameMatch[1] !== $allowedTag ) {
			return $tagString;
		}

		return preg_replace_callback( '/settings="(.*?(?="]))/', function( array $matches ) use ( $idKey ) {
			$settings = json_decode( urldecode( $matches[1] ), true );

			if ( ! isset( $settings[ $idKey ] ) ) {
				return $matches[0];
			}

			$settings[ $idKey ] = self::convertId( $settings[ $idKey ] );
			$replace            = urlencode( json_encode( $settings ) );

			return str_replace( $matches[1], $replace, $matches[0] );

		}, $tagString );
	}

	/**
	 * @param int $elementId
	 *
	 * @return int
	 */
	public static function convertId( $elementId ) {
		return apply_filters( 'wpml_object_id', $elementId, get_post_type( $elementId ), true );
	}
}
