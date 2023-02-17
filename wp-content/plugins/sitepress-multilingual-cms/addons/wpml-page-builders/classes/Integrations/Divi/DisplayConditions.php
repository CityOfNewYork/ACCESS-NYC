<?php

namespace WPML\Compatibility\Divi;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use WPML\FP\Obj;

class DisplayConditions implements \IWPML_Frontend_Action {

	const BASE64_EMPTY_ARRAY = 'W10=';

	public function add_hooks() {
		Hooks::onFilter( 'et_pb_module_shortcode_attributes' )
			->then( spreadArgs( [ $this, 'translateAttributes' ] ) );
	}

	/**
	 * @param array $atts
	 * @return array
	 */
	public function translateAttributes( $atts ) {
		$displayConditions = Obj::prop( 'display_conditions', $atts );
		
		if ( $displayConditions && self::BASE64_EMPTY_ARRAY !== $displayConditions ) {
			/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode */
			$conditions = json_decode( base64_decode( $atts['display_conditions'] ), true );

			foreach ( $conditions as &$condition ) {
				if ( 'categoryPage' === $condition['condition'] ) {
					foreach ( $condition['conditionSettings']['categories'] as &$category ) {
						$category['value'] = (string) apply_filters( 'wpml_object_id', $category['value'], $category['groupSlug'] );
					}
				}
			}

			/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode */
			$atts['display_conditions'] = base64_encode( wp_json_encode( $conditions ) );
		}

		return $atts;
	}

}
