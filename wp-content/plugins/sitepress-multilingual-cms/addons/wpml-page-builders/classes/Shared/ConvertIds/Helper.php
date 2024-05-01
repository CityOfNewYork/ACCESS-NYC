<?php

namespace WPML\PB\ConvertIds;

use WPML\Convert\Ids;

class Helper {

	const TYPE_POST_IDS     = 'post-ids';
	const TYPE_TAXONOMY_IDS = 'taxonomy-ids';

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function isValidType( $type ) {
		return in_array( $type, [ self::TYPE_POST_IDS, self::TYPE_TAXONOMY_IDS ], true );
	}

	/**
	 * @param string|null $subtype
	 * @param string|null $type
	 *
	 * @return string
	 */
	public static function selectElementType( $subtype, $type ) {
		return $subtype ?: wpml_collect( [
			self::TYPE_POST_IDS     => Ids::ANY_POST,
			self::TYPE_TAXONOMY_IDS => Ids::ANY_TERM,
		] )->get( (string) $type, $type );
	}
}
