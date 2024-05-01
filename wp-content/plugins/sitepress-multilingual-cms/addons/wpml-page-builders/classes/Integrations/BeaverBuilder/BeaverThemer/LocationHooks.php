<?php

namespace WPML\PB\BeaverBuilder\BeaverThemer;

use WPML\Convert\Ids;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class LocationHooks implements \IWPML_Backend_Action {

	const LAYOUT_CPT = 'fl-theme-layout';

	const LOCATIONS_RULES_KEY = '_fl_theme_builder_locations';

	const EXCLUSIONS_RULES_KEY = '_fl_theme_builder_exclusions';

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_copy_meta_field', 10, 4 )
			->then( spreadArgs( [ $this, 'translateLocationRulesMeta' ] ) );
	}

	/**
	 * @param mixed  $copiedValue
	 * @param int    $translatedPostId
	 * @param int    $originalPostId
	 * @param string $metaKey
	 *
	 * @return mixed
	 */
	public function translateLocationRulesMeta( $copiedValue, $translatedPostId, $originalPostId, $metaKey ) {
		if ( in_array( $metaKey, [ self::LOCATIONS_RULES_KEY, self::EXCLUSIONS_RULES_KEY ], true ) ) {
			$targetLang = self::getLayoutLanguage( $translatedPostId );

			foreach ( $copiedValue as &$rule ) {
				$rule = $this->translateRule( $rule, $targetLang );
			}
		}

		return $copiedValue;
	}

	/**
	 * Translate IDs in locations rules.
	 *
	 * Location rules are an array of rules. Each rule is separated by (:).
	 * General rules can be like:
	 *   'general:site'
	 *   'general:archive'
	 *   'general:single'
	 *   'general:404'
	 *   'post:post'
	 *   'post:page'
	 *
	 * This translates the cases for posts and taxonomies. Their rules can be like:
	 *   'post:page:12'
	 *   'post:post:taxonomy:category:45'
	 *
	 * @param string $rule
	 * @param string $targetLangCode
	 *
	 * @return string
	 */
	private function translateRule( $rule, $targetLangCode ) {
		$parts = explode( ':', $rule );

		if ( 3 === count( $parts ) ) {
			$rule = implode( ':', [ $parts[0], $parts[1], self::translateElement( $parts[2], $parts[1], $targetLangCode ) ] );
		} elseif ( 5 === count( $parts ) ) {
			$rule = implode( ':', [ $parts[0], $parts[1], $parts[2], $parts[3], self::translateElement( $parts[4], $parts[3], $targetLangCode ) ] );
		}

		return $rule;
	}

	/**
	 * @param int $translatedPostId
	 *
	 * @return string|null
	 */
	private static function getLayoutLanguage( $translatedPostId ) {
		return apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $translatedPostId,
			'element_type' => self::LAYOUT_CPT,
		] );
	}

	/**
	 * @param string $elementId
	 * @param string $elementType
	 * @param string $targetLangCode
	 *
	 * @return string
	 */
	private static function translateElement( $elementId, $elementType, $targetLangCode ) {
		return Ids::convert( $elementId, $elementType, true, $targetLangCode );
	}
}
