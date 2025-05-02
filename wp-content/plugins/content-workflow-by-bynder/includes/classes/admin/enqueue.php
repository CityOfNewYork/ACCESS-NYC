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
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function admin_enqueue_style() {
		\GatherContent\Importer\enqueue_style( 'cwby-select2', 'vendor/select2-4.0.13/select2', array(), '4.0.13' );
		\GatherContent\Importer\enqueue_style( 'content-workflow-by-bynder', 'gathercontent-importer' );

		do_action( 'cwby_admin_enqueue_style' );
	}

	/**
	 * Enqueues the GC scripts, and hooks the localization to the footer.
	 *
	 * @return void
	 * @since  3.0.0
	 *
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

		\GatherContent\Importer\enqueue_script( 'gc-select2', 'vendor/select2-4.0.13/select2', array( 'jquery' ), '4.0.13' );
		\GatherContent\Importer\enqueue_script( 'gathercontent-database', 'gathercontent-database', '1.0.0' );

		// If < WP 4.5, we need the newer version of underscore.js
		if ( ! Utils::enqueued_at_least( 'underscore', '1.8.3' ) ) {

			// Cannot use wp_deregister_script as WP will not allow it.
			wp_scripts()->remove( 'underscore' );

			\GatherContent\Importer\enqueue_script( 'underscore', 'vendor/underscore-1.8.3/underscore', array(), '1.8.3' );

			// Need to output underscore script early since WP may be using load-scripts.php which enqueues early.
			wp_scripts()->print_scripts( 'underscore' );
		}

		\GatherContent\Importer\enqueue_script( 'gathercontent', 'gathercontent', array(
			'gc-select2',
			'wp-backbone'
		) );

		do_action( 'cwby_admin_enqueue_script' );

		// Localize in footer so that 'cwby_localized_data' filter is more useful.
		add_action( 'admin_footer', array( $this, 'script_localize' ), 1 );
	}

	/**
	 * Localizes the data for the GC scripts. Hooked to admin_footer in order to be run late,
	 * and for the gathercontent_localized_data filter to be easily hooked to.
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function script_localize() {
		/**
		 * Previously we were pulling the entire $_GET array to localise
		 * the queryargs used on the front-end. These are the only queryargs
		 * referenced on the front-end.
		 */
		$queryArgs = $this->_get_vals( [ 'flush_cache', 'mapping' ] );

		wp_localize_script( 'gathercontent', 'GatherContent', apply_filters( 'cwby_localized_data', array(
			'debug'       => Utils::script_debug(),
			// @codingStandardsIgnoreStart
			'queryargs'   => $queryArgs,
			// @codingStandardsIgnoreEnd
			'_type_names' => Utils::cwby_field_type_name( 'all' ),
		) ) );
	}
}
