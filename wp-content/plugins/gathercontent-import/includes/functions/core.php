<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;

/**
 * Will look for Some_Class\Name in /includes/classes/some-class/name.php
 *
 * @since  3.0.0
 *
 * @param  string $class_name Class name.
 * @return void
 */
function autoload( $class_name ) {

	// Project-specific namespace prefix.
	$prefix = __NAMESPACE__ . '\\';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( 0 !== strncmp( $prefix, $class_name, $len ) ) {
	    // No, move to the next registered autoloader.
	    return;
	}

	// base directory for the namespace prefix.
	$base_dir = GATHERCONTENT_INC . 'classes/';

	// get the relative class name.
	$relative_class = substr( $class_name, $len );

	/*
	 * replace the namespace prefix with the base directory, replace namespace
	 * separators with directory separators in the relative class name, replace
	 * underscores with dashes, and append with .php
	 */
	$path = strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), $relative_class ) );
	$file = $base_dir . $path . '.php';

	// if the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
}

/**
 * Default setup routine
 *
 * @since  3.0.0
 *
 * @uses add_action()
 * @uses do_action()
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	spl_autoload_register( $n( 'autoload' ), false );

	if ( is_admin() ) {
		// We only need to do our work in the admin.
		add_action( 'init', $n( 'init' ) );
	}

	do_action( 'gathercontent_loaded' );

	add_action( 'plugins_loaded', $n( 'General::init_plugins_loaded_hooks' ) );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @since  3.0.0
 *
 * @uses do_action()
 *
 * @return void
 */
function init() {

	$general = General::get_instance();
	$general->init_hooks();

	do_action( 'gathercontent_init', $general );
}

/**
 * Activate the plugin
 *
 * @since  3.0.0
 *
 * @uses init()
 * @uses flush_rewrite_rules()
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded.
	init();
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @since  3.0.0
 *
 * @return void
 */
function deactivate() {
}

// Activation/Deactivation.
register_activation_hook( GATHERCONTENT_PLUGIN, '\GatherContent\Importer\activate' );
register_deactivation_hook( GATHERCONTENT_PLUGIN, '\GatherContent\Importer\deactivate' );

// Bootstrap.
setup();
