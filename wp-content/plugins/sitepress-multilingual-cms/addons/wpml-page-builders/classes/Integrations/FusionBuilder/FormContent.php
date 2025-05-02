<?php

namespace WPML\Compatibility\FusionBuilder;

use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class FormContent implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const CPT_FORM = 'fusion_form';

	const OPTIONS_ENCODING = 'fusion_options';
	const LOGICS_ENCODING  = 'fusion_logics';

	/** @var null|array */
	private $inProcess;

	public function add_hooks() {
		Hooks::onAction( 'init' )->then( [ $this, 'disableAvadaBuiltinShortcodeHooks' ] );
		Hooks::onFilter( 'wpml_pb_shortcode_decode', 10, 2 )->then( spreadArgs( [ $this, 'decode' ] ) );
		Hooks::onFilter( 'wpml_pb_shortcode_encode', 10, 2 )->then( spreadArgs( [ $this, 'encode' ] ) );
		Hooks::onFilter( 'fusion_pre_shortcode_atts' )->then( spreadArgs( [ $this, 'convertForm' ] ) );
	}

	/**
	 * Avada's team tried to add WPML support for forms
	 * but it's not working at all. We'll just make sure
	 * to detach their filters.
	 */
	public function disableAvadaBuiltinShortcodeHooks() {
		if ( function_exists( 'fusion_library' ) && property_exists( fusion_library(), 'multilingual' ) ) {
			remove_filter( 'wpml_pb_shortcode_decode', [ fusion_library()->multilingual, 'wpml_pb_shortcode_decode_forms' ] );
			remove_filter( 'wpml_pb_shortcode_encode', [ fusion_library()->multilingual, 'wpml_pb_shortcode_encode_forms' ] );
		}
	}

	/**
	 * @param string|array $string
	 * @param string       $encoding
	 *
	 * @return array|string
	 */
	public function decode( $string, $encoding ) {
		if ( ! $string || is_array( $string ) ) {
			return $string;
		}

		if ( self::OPTIONS_ENCODING === $encoding || ! $encoding ) {
			$getValue = Obj::prop( 1 );
		} elseif ( self::LOGICS_ENCODING === $encoding ) {
			$getValue = Obj::prop( 'value' );
		} else {
			return $string;
		}

		$decoded = $this->decodeString( $string );
		if ( is_array( $decoded ) ) {
			$this->inProcess = $decoded;

			$parsed_strings = [];
			foreach ( $decoded as $item ) {
				$value = $getValue( $item );

				$parsed_strings[] = [
					'value'     => $value,
					'translate' => $value && ! is_numeric( $value ),
				];
			}

			return $parsed_strings;
		}

		return $string;
	}

	/**
	 * @param string $string
	 *
	 * @return array|mixed
	 */
	private function decodeString( $string ) {
		/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode */
		return json_decode( base64_decode( $string ) );
	}

	/**
	 * @param string|array $string
	 * @param string       $encoding
	 *
	 * @return string|array
	 */
	public function encode( $string, $encoding ) {
		if ( ! is_array( $string ) || ! is_array( $this->inProcess ) ) {
			return $string;
		}

		if ( self::OPTIONS_ENCODING === $encoding || ! $encoding ) {
			$setValue = Obj::assoc( 1 );
		} elseif ( self::LOGICS_ENCODING === $encoding ) {
			$setValue = Obj::assoc( 'value' );
		} else {
			return $string;
		}

		$options         = $this->inProcess;
		$this->inProcess = null;
		foreach ( $options as $key => $option ) {
			$options[ $key ] = $setValue( $string[ $key ], $option );
		}

		return $this->encodeArray( $options );
	}

	/**
	 * @param array $array
	 *
	 * @return string
	 */
	private function encodeArray( $array ) {
		/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode */
		return base64_encode( wp_json_encode( $array ) );
	}

	/**
	 * @param array $atts
	 *
	 * @return array
	 */
	public function convertForm( $atts ) {
		// $convertId :: string|int -> int
		$convertId = function( $id ) {
			return apply_filters( 'wpml_object_id', $id, self::CPT_FORM, true );
		};

		// $convertForm :: array -> array
		$convertForm = Obj::over(
			Obj::lensProp( 'form_post_id' ),
			$convertId
		);

		return Maybe::just( $atts )
			->filter( Obj::prop( 'form_post_id' ) )
			->map( $convertForm )
			->getOrElse( $atts );
	}

}
