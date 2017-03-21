/**
 * Work around for Require, Radio, and WordPress.
 * 
 * Returns the WordPress-loaded version of Underscore for use with things that need it and use Require.
 * 
 * @since  3.0
 * @return Object	Underscore Object
 */
define( function() {
	return _;
} );