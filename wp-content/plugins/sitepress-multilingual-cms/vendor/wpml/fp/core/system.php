<?php

namespace WPML\FP\System;

/**
 * Returns a filter function to filter a collection by the given key
 * Use like:
 * $theCollection->map( getFilterFor( 'my-key' )->using( santatizeString() )->defaultTo( '' ) )
 * This will filter the collection item with a key of 'my-key' using the 'FILTER_SANITIZE_STRING'.
 * If the key doesn't exist it defaults to an empty string.
 *
 * defaultTo can be a value or a callable that returns a value
 *
 * @param string $key
 *
 * @return _Filter
 */
function getFilterFor( $key ) {
	return new _Filter( $key );
}

/**
 * Returns a function of the defined type that can then be used to map
 * over a variable.
 *
 * @param int $filter - Filter type same as for php filter_var function
 *
 * @return \Closure
 */
function filterVar( $filter ) {
	return function ( $var ) use ( $filter ) {
		return filter_var( $var, $filter );
	};
}

/**
 * returns a function that will sanitize using the FILTER_SANITIZE_STRING type.
 * @return \Closure
 */
function sanitizeString() {
	return filterVar( FILTER_SANITIZE_STRING );
}

/**
 * Returns a validator function to filter a collection by the given key
 * Use like:
 * map( getValidatorFor( 'my-key' )->using( Logic::isNotNull() )->error( 'It was false' ) ), $myCollection)
 * This will run the validator on the collection item with a key of 'my-key' and return Either::Right
 * containing the given collection or Either::Left containing the error depending if the supplied
 * using function returns true or false
 *
 * error can be a value or a callable that returns a value
 *
 * @param string $key
 *
 * @return _Validator
 */
function getValidatorFor( $key ) {
	return new _Validator( $key );
}

