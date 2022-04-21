<?php

namespace WPML\TM\Jobs;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Str;
use function WPML\FP\curryN;
use function WPML\FP\pipe;

/**
 * Class FieldId
 *
 * @package WPML\TM\Jobs
 * @method static callable|int get_term_id( ...$field ) - Curried :: string → int
 * @method static callable|int is_a_term( ...$field ) - Curried :: string → bool
 * @method static callable|int is_a_term_description( ...$field ) - Curried :: string → bool
 * @method static callable|int is_a_term_meta( ...$field ) - Curried :: string → bool
 * @method static callable|int is_a_custom_field( ...$field ) - Curried :: string → bool
 * @method static callable|int is_any_term_field( ...$field ) - Curried :: string → bool
 * @method static callable|string forTerm( ...$termId ) - Curried :: int → string
 * @method static callable|string forTermDescription( ...$termId ) - Curried :: int → string
 * @method static callable|string forTermMeta( ...$termId, $key ) - Curried :: int → string → string
 * @method static callable|string getTermMetaKey( ...$field ) - Curried :: string → string
 */
class FieldId {

	use Macroable;

	const TERM_PREFIX             = 't_';
	const TERM_DESCRIPTION_PREFIX = 'tdesc_';
	const TERM_META_FIELD_PREFIX  = 'tfield-';
	const CUSTOM_FIELD_PREFIX     = 'field-';

	public static function init() {

		self::macro( 'is_a_term', Str::startsWith( self::TERM_PREFIX ) );

		self::macro( 'is_a_term_description', Str::startsWith( self::TERM_DESCRIPTION_PREFIX ) );

		self::macro( 'is_a_term_meta', Str::startsWith( self::TERM_META_FIELD_PREFIX ) );

		self::macro( 'is_a_custom_field', Str::startsWith( self::CUSTOM_FIELD_PREFIX ) );

		self::macro(
			'is_any_term_field',
			Logic::anyPass( [ self::is_a_term(), self::is_a_term_description(), self::is_a_term_meta() ] )
		);

		self::macro(
			'get_term_id',
			curryN(
				1,
				Logic::cond(
					[
						[ self::is_a_term(), Str::sub( Str::len( self::TERM_PREFIX ) ) ],
						[ self::is_a_term_description(), Str::sub( Str::len( self::TERM_DESCRIPTION_PREFIX ) ) ],
						[ Fns::always( true ), pipe( Str::split( '-' ), Lst::last() ) ],
					]
				)
			)
		);

		self::macro( 'forTerm', Str::concat( self::TERM_PREFIX ) );

		self::macro( 'forTermDescription', Str::concat( self::TERM_DESCRIPTION_PREFIX ) );

		self::macro(
			'forTermMeta',
			curryN(
				2,
				function ( $termId, $key ) {
					return 'tfield-' . $key . '-' . $termId;
				}
			)
		);

		// getTermMetaKey :: string tfield-K-E-Y-ID → string K-E-Y ( KEY can have hyphens )
		self::macro(
			'getTermMetaKey',
			curryN(
				1,
				pipe(
					Str::sub( Str::len( self::TERM_META_FIELD_PREFIX ) ), // K-E-Y-ID
					Str::split( '-' ), // [ K, E, Y, ID ]
					Lst::dropLast( 1 ), // [ K, E, Y ]
					Lst::join( '-' ) // K-E-Y
				)
			)
		);
	}

}

FieldId::init();
