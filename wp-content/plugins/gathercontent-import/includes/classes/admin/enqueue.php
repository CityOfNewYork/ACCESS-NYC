<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Utils;

/**
 * A base class for enqueueing the GC resources and localizing the script data.
 *
 * @since  3.0.0
 */
abstract class Enqueue extends Plugin_Base {

	/**
	 * Enqueues the GC stylesheets.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_style() {
		\GatherContent\Importer\enqueue_style( 'gc-select2', 'vendor/select2-4.0.3/select2', array(), '4.0.3' );
		\GatherContent\Importer\enqueue_style( 'gathercontent', 'gathercontent-importer' );

		do_action( 'gc_admin_enqueue_style' );
	}

	/**
	 * Enqueues the GC scripts, and hooks the localization to the footer.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_script() {

		// BadgeOS is a bad citizen as it is enqueueing its (old) version of select2 in the entire admin.
		// It is incompatible with the new version, so we need to remove it on our pages.
		if ( wp_script_is( 'badgeos-select2', 'enqueued' ) ) {
			wp_dequeue_script( 'badgeos-select2' );
			wp_deregister_script( 'badgeos-select2' );
			wp_dequeue_style( 'badgeos-select2-css' );
			wp_deregister_style( 'badgeos-select2-css' );
		}

		\GatherContent\Importer\enqueue_script( 'gc-select2', 'vendor/select2-4.0.3/select2', array( 'jquery' ), '4.0.3' );

		// If < WP 4.5, we need the newer version of underscore.js
		if ( ! Utils::enqueued_at_least( 'underscore', '1.8.3' ) ) {

			// Cannot use wp_deregister_script as WP will not allow it.
			wp_scripts()->remove( 'underscore' );

			\GatherContent\Importer\enqueue_script( 'underscore', 'vendor/underscore-1.8.3/underscore', array(), '1.8.3' );

			// Need to output underscore script early since WP may be using load-scripts.php which enqueues early.
			wp_scripts()->print_scripts( 'underscore' );
		}

		\GatherContent\Importer\enqueue_script( 'gathercontent', 'gathercontent', array( 'gc-select2', 'wp-backbone' ) );

		do_action( 'gc_admin_enqueue_script' );

		// Localize in footer so that 'gathercontent_localized_data' filter is more useful.
		add_action( 'admin_footer', array( $this, 'script_localize' ), 1 );
	}

	/**
	 * Localizes the data for the GC scripts. Hooked to admin_footer in order to be run late,
	 * and for the gathercontent_localized_data filter to be easily hooked to.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function script_localize() {
		wp_localize_script( 'gathercontent', 'GatherContent', apply_filters( 'gathercontent_localized_data', array(
			'debug'       => Utils::script_debug(),
			// @codingStandardsIgnoreStart
			'queryargs'   => $_GET,
			// @codingStandardsIgnoreEnd
			'_type_names' => Utils::gc_field_type_name( 'all' ),
		) ) );
	}
}
