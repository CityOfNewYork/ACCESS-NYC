<?php

namespace WPML\Compatibility\Divi;

use WPML\Convert\Ids;
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
			$conditions = json_decode( base64_decode( $displayConditions ), true );

			foreach ( $conditions as &$condition ) {
				$condition = $this->translateConditionIds( $condition, 'categories' );
				$condition = $this->translateConditionIds( $condition, 'tags' );
				$condition = $this->translateConditionIds( $condition, 'dynamicPosts' );
			}

			/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode */
			$atts['display_conditions'] = base64_encode( wp_json_encode( $conditions ) );
		}

		return $atts;
	}

	/**
	 * @param array  $condition
	 * @param string $type
	 *
	 * @return array
	 */
	private function translateConditionIds( $condition, $type ) {
		if ( isset( $condition['conditionSettings'][ $type ] ) ) {
			$elementType = 'dynamicPosts' === $type ? 'any_post' : 'any_term';
			foreach ( $condition['conditionSettings'][ $type ] as &$category ) {
				$category['value'] = Ids::convert( $category['value'], $elementType );
			}
		}

		return $condition;
	}
}
