<?php

namespace WPML\ST\Batch\Translation;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use function WPML\FP\curryN;

/**
 * Class Strings
 *
 * @package WPML\ST\Batch\Translation
 * @method static callable|object get( ...$getBatchRecord, ...$getString, ...$item, ...$id, ...$type )
 */
class Strings {

	use Macroable;

	public static function init() {

		self::macro(
			'get',
			curryN(
				5,
				function ( callable $getBatchRecord, callable $getString, $item, $id, $type ) {
					if ( $type === 'st-batch' || $type === Module::EXTERNAL_TYPE ) {
						$getBatchString = function ( $strings, $stringId ) use ( $getString ) {
							$strings[ Module::STRING_ID_PREFIX . $stringId ] = $getString( $stringId );

							return $strings;
						};

						return (object) [
							'post_id'       => $id,
							'ID'            => $id,
							'post_type'     => 'strings',
							'kind'          => 'Strings',
							'kind_slug'     => 'Strings',
							'external_type' => true,
							'string_data'   => Fns::reduce( $getBatchString, [], $getBatchRecord( $id ) ),
						];
					}

					return $item;
				}
			)
		);
	}
}

Strings::init();
