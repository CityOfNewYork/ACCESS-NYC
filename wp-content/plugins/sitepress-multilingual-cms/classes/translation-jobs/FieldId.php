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
 * @method static callable|int is_a_term_meta( ...$field ) - Curried :: string → bool
 * @method static callable|int is_a_custom_field( ...$field ) - Curried :: string → bool
 * @method static callable|int is_any_term_field( ...$field ) - Curried :: string → bool
 * @method static callable|string forTerm( ...$termId ) - Curried :: int → string
 * @method static callable|string forTermDescription( ...$termId ) - Curried :: int → string
 * @method static callable|string forTermMeta( ...$termId, $key ) - Curried :: int → string → string
 */
class FieldId {

	use Macroable;

	const TERM_PREFIX             = 't_';
	const TERM_DESCRIPTION_PREFIX = 'tdesc_';
	const TERM_META_FIELD_PREFIX  = 'tfield-';
	const CUSTOM_FIELD_PREFIX     = 'field-';

	public static function init() {



		self::macro(
			'is_any_term_field',
			/** @phpstan-ignore-next-line */
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

		/** @phpstan-ignore-next-line */
		self::macro( 'forTerm', Str::concat( self::TERM_PREFIX ) );

		/** @phpstan-ignore-next-line */
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

	}

	/**
	 * @param string $maybe_term
	 *
	 * @return callable|bool
	 *
	 * @phpstan-template A1 of string|curried
	 * @phpstan-template P1 of string
	 * @phpstan-template R of bool
	 *
	 * @phpstan-param ?A1 $maybe_term
	 *
	 * @phpstan-return ($maybe_term is P1 ? R : callable(P1=):R)
	 */
	public static function is_a_term( $maybe_term = null ) {
		return call_user_func_array(
			curryN(
				1,
				function( $maybe_term ) {
					return Str::startsWith( self::TERM_PREFIX, $maybe_term );
				}
			),
			func_get_args()
		);
	}

	/**
	 * @param string $maybe_term_description
	 *
	 * @return callable|bool
	 *
	 * @phpstan-template A1 of string|curried
	 * @phpstan-template P1 of string
	 * @phpstan-template R of bool
	 *
	 * @phpstan-param ?A1 $maybe_term_description
	 *
	 * @phpstan-return ($maybe_term_description is P1 ? R : callable(P1=):R)
	 */
	public static function is_a_term_description( $maybe_term_description = null ) {
		return call_user_func_array(
			curryN(
				1,
				function( $maybe_term_description ) {
					return Str::startsWith( self::TERM_DESCRIPTION_PREFIX, $maybe_term_description );
				}
			),
			func_get_args()
		);
	}

	/**
	 * @param string $maybe_term_meta
	 *
	 * @return callable|bool
	 *
	 * @phpstan-template A1 of string|curried
	 * @phpstan-template P1 of string
	 * @phpstan-template R of bool
	 *
	 * @phpstan-param ?A1 $maybe_term_meta
	 *
	 * @phpstan-return ($maybe_term_meta is P1 ? R : callable(P1=):R)
	 */
	public static function is_a_term_meta( $maybe_term_meta = null ) {
		return call_user_func_array(
			curryN(
				1,
				function( $maybe_term_meta ) {
					return Str::startsWith( self::TERM_META_FIELD_PREFIX, $maybe_term_meta );
				}
			),
			func_get_args()
		);
	}

	/**
	 * @param string $maybe_custom_field
	 *
	 * @return callable|bool
	 *
	 * @phpstan-template A1 of string|curried
	 * @phpstan-template P1 of string
	 * @phpstan-template R of bool
	 *
	 * @phpstan-param ?A1 $maybe_custom_field
	 *
	 * @phpstan-return ($maybe_custom_field is P1 ? R : callable(P1=):R)
	 */
	public static function is_a_custom_field( $maybe_custom_field = null ) {
		return call_user_func_array(
			curryN(
				1,
				function( $maybe_custom_field ) {
					return Str::startsWith( self::CUSTOM_FIELD_PREFIX, $maybe_custom_field );
				}
			),
			func_get_args()
		);
	}

	/**
	 * @param string $termMeta
	 *
	 * @return callable|string
	 *
	 * @phpstan-template A1 of string|curried
	 * @phpstan-template P1 of string
	 * @phpstan-template R of string
	 *
	 * @phpstan-param ?A1 $termMeta
	 *
	 * @phpstan-return ($termMeta is null ? callable(P1=):R : R)
	 */
	public static function getTermMetaKey( $termMeta = null ) {
		return call_user_func_array(
			curryN(
				1,
				function( $termMeta ) {
					$getKey = pipe(
						Str::sub( Str::len( self::TERM_META_FIELD_PREFIX ) ), // K-E-Y-ID
						Str::split( '-' ), // [ K, E, Y, ID ]
						Lst::dropLast( 1 ), // [ K, E, Y ]
						Lst::join( '-' ) // K-E-Y
					);

					return $getKey( $termMeta );
				}
			),
			func_get_args()
		);
	}
}

FieldId::init();
