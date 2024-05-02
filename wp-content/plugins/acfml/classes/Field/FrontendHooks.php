<?php

namespace ACFML\Field;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class FrontendHooks implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'acf/load_value', 10, 3 )
			->then( spreadArgs( [ self::class, 'convertTargetLinks' ] ) );
	}

	/**
	 * @param mixed  $value
	 * @param string $postId
	 * @param array  $field
	 *
	 * @return mixed
	 */
	public static function convertTargetLinks( $value, $postId, $field ) {
		$isWysiwygField = Obj::prop( 'type', $field ) === 'wysiwyg';

		if ( $isWysiwygField && is_string( $value ) ) {
			return apply_filters( 'wpml_translate_link_targets', $value );
		}

		return $value;
	}
}
