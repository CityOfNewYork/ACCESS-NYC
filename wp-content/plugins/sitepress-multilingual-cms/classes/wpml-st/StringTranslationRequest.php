<?php

namespace WPML\TM\StringTranslation;

class StringTranslationRequest {

	/**
	 * @param array    $post clone of $_POST
	 * @param callable $addStringsToBasket :: array $stringIds -> string $fromLang -> array $toLangs -> void
	 */
	public static function sendToTranslation( $post, callable $addStringsToBasket ) {
		$post         = stripslashes_deep( $post );
		$string_ids   = explode( ',', $post['strings'] );
		$translate_to = array();
		foreach ( $post['translate_to'] as $lang_to => $one ) {
			$translate_to[ $lang_to ] = $lang_to;
		}
		if ( ! empty( $translate_to ) ) {
			$addStringsToBasket( $string_ids, $post['icl-tr-from'], $translate_to );
		}
	}
}
