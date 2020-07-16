<?php

namespace WPML\FP\Strings;

use WPML\FP\Maybe;
use function WPML\FP\partial;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;

/**
 * ltrimWith :: string -> ( string -> string )
 * @param string $trim
 *
 * @return callable
 */
function ltrimWith( $trim ) {
	return partialRight( 'ltrim', $trim );
}

/**
 * rtrimWith :: string -> ( string -> string )
 * @param string $trim
 *
 * @return callable
 */
function rtrimWith( $trim ) {
	return partialRight( 'rtrim', $trim );
}

/**
 * explodeToCollection :: string -> ( string -> Collection )
 * @param string $delimiter
 *
 * @return callable
 */
function explodeToCollection( $delimiter ) {
	return pipe( partial( 'explode', $delimiter ), 'wpml_collect' );
}

/**
 * replace :: string -> string -> ( string -> string )
 * @param string $search
 * @param string $replace
 *
 * @return callable
 */
function replace( $search, $replace ) {
	return partial( 'str_replace', $search, $replace );
}

/**
 * remove :: string -> ( string -> string )
 * @param string $remove
 *
 * @return callable
 */
function remove( $remove ) {
	return partial( 'str_replace', $remove, '' );
}

/**
 * @param string $regex
 * @param string $str
 *
 * @return \WPML\FP\Just|\WPML\FP\Nothing
 */
function match( $regex, $str ) {
	$found = preg_match_all( $regex, $str, $matches );
	return $found !== false ? Maybe::of( wpml_collect( $matches[1] ) ) : Maybe::nothing();
}
