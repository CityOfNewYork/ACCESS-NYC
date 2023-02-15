<?php

namespace WPML\Compatibility\FusionBuilder;

use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class FormContent implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const CPT_FORM = 'fusion_form';

	/** @var null|array $formOptionsInProcess */
	private $formOptionsInProcess;

	public function add_hooks() {
		Hooks::onAction( 'init' )->then( [ $this, 'disableAvadaBuiltinShortcodeHooks' ] );
		Hooks::onFilter( 'wpml_pb_shortcode_decode' )->then( spreadArgs( [ $this, 'decode' ] ) );
		Hooks::onFilter( 'wpml_pb_shortcode_encode' )->then( spreadArgs( [ $this, 'encode' ] ) );
		Hooks::onFilter( 'fusion_pre_shortcode_atts' )->then( spreadArgs( [ $this, 'convertForm' ] ) );
	}

	/**
	 * Avada's team tried to add WPML support for forms
	 * but it's not working at all. We'll just make sure
	 * to detach theirs filters.
	 */
	public function disableAvadaBuiltinShortcodeHooks() {
		if ( function_exists( 'fusion_library' ) && property_exists( fusion_library(), 'multilingual' ) ) { // @phpstan-ignore-line
			remove_filter( 'wpml_pb_shortcode_decode', [ fusion_library()->multilingual, 'wpml_pb_shortcode_decode_forms' ] ); // @phpstan-ignore-line
			remove_filter( 'wpml_pb_shortcode_encode', [ fusion_library()->multilingual, 'wpml_pb_shortcode_encode_forms' ] ); // @phpstan-ignore-line
		}
	}

	/**
	 * Decodes form shortcodes.
	 *
	 * @param string|array $string
	 *
	 * @return array|string
	 */
	public function decode( $string ) {
		if ( ! $string || is_array( $string ) ) {
			return $string;
		}

		$decoded = json_decode( base64_decode( $string ) );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			$this->formOptionsInProcess = $decoded;
			$parsed_strings             = [];

			foreach ( $decoded as $item ) {
				$parsed_strings[] = [
					'value'     => $item[1],
					'translate' => ! ( empty( $item[1] ) || is_numeric( $item[1] ) ),
				];
			}

			return $parsed_strings;
		}

		return $string;
	}

	/**
	 * Encodes form shortcodes.
	 *
	 * @param string|array $string
	 *
	 * @return string
	 */
	public function encode( $string ) {
		if ( is_array( $string ) && is_array( $this->formOptionsInProcess ) ) {
			$options                    = $this->formOptionsInProcess;
			$this->formOptionsInProcess = null;

			foreach ( $options as $key => $option ) {
				$options[ $key ][1] = $string[ $key ];
			}

			return base64_encode( json_encode( $options ) );
		}

		return $string;
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
