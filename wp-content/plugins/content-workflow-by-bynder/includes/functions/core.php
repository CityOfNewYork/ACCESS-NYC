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
 * @param string $class_name Class name.
 *
 * @return void
 * @since  3.0.0
 *
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
 * @return void
 * @uses add_action()
 * @uses do_action()
 *
 * @since  3.0.0
 *
 */
function setup() {
	$n = function ( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	spl_autoload_register( $n( 'autoload' ) );

	include_once GATHERCONTENT_PATH . 'vendor/autoload.php';

	if ( is_admin() ) {
		// We only need to do our work in the admin.
		add_action( 'init', $n( 'init' ) );
	}

	do_action( 'cwby_loaded' );

	add_action( 'plugins_loaded', $n( 'General::init_plugins_loaded_hooks' ) );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 * @uses do_action()
 *
 * @since  3.0.0
 *
 */
function init() {

	$general = General::get_instance();
	$general->init_hooks();

	do_action( 'cwby_init', $general );
}

/**
 * Activate the plugin
 *
 * @return void
 * @uses init()
 * @uses flush_rewrite_rules()
 *
 * @since  3.0.0
 *
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
 * @return void
 * @since  3.0.0
 *
 */
function deactivate() {
}

// Activation/Deactivation.
register_activation_hook( GATHERCONTENT_PLUGIN, '\GatherContent\Importer\activate' );
register_deactivation_hook( GATHERCONTENT_PLUGIN, '\GatherContent\Importer\deactivate' );

// Bootstrap.
setup();
