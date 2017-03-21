/**
 * Work around for Require, Radio, and WordPress.
 * 
 * Returns the WordPress-loaded version of Backbone for use with things that need it and use Require.
 * 
 * @since  3.0
 * @return Object	Backbone Object
 */
define( function() {
	return Backbone;
} );