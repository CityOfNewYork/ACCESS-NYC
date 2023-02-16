<?php

namespace WPML\LIB\WP;

use WPML\FP\Curryable;
use WPML\FP\Logic;
use WPML\FP\Str;

/**
 * @method static callable|bool hasBlock( ...$string ) - Curried :: string → bool
 * @method static callable|bool doesNotHaveBlock( ...$string ) - Curried :: string → bool
 * @method static callable|bool stripBlockData( ...$string ) - Curried :: string → string
 */
class Gutenberg {
	use Curryable;

	const GUTENBERG_OPENING_START = '<!-- wp:';

	/**
	 * @return void
	 */
	public static function init() {
		self::curryN( 'hasBlock', 1, Str::includes( self::GUTENBERG_OPENING_START ) );
		self::curryN( 'doesNotHaveBlock', 1, Logic::complement( self::hasBlock() ) );
		self::curryN( 'stripBlockData', 1, Str::pregReplace( '(<!--\s*/?wp:[^<]*-->)', '' ) );
	}

}

Gutenberg::init();
