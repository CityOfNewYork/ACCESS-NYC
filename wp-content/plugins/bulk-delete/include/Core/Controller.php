<?php

namespace BulkWP\BulkDelete\Core;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Controller.
 *
 * Handle all requests and automatically perform nonce checks.
 *
 * @since 5.5.4
 * @since 6.0.0 Added namespace.
 */
class Controller {
	/**
	 * Load the controller and setup hooks and actions.
	 *
	 * @since 6.0.0
	 */
	public function load() {
		add_action( 'admin_init', array( $this, 'request_handler' ) );

		add_action( 'bd_pre_bulk_action', array( $this, 'increase_timeout' ), 9 );
		add_action( 'bd_before_scheduler', array( $this, 'increase_timeout' ), 9 );

		add_filter( 'bd_get_action_nonce_check', array( $this, 'verify_get_request_nonce' ), 10, 2 );

		add_action( 'wp_ajax_bd_load_taxonomy_term', array( $this, 'load_taxonomy_term' ) );

		add_filter( 'bd_help_tooltip', 'bd_generate_help_tooltip', 10, 2 );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 2 );

		$this->load_old_hooks();

		if ( defined( 'BD_DEBUG' ) && BD_DEBUG ) {
			add_action( 'bd_after_query', array( $this, 'log_sql_query' ) );
		}
	}

	/**
	 * Handle both POST and GET requests.
	 * This method automatically triggers all the actions after checking the nonce.
	 */
	public function request_handler() {
		if ( isset( $_POST['bd_action'] ) ) {
			$bd_action   = sanitize_text_field( $_POST['bd_action'] );
			$nonce_valid = false;

			if ( 'delete_jetpack_messages' === $bd_action && wp_verify_nonce( $_POST['sm-bulk-delete-misc-nonce'], 'sm-bulk-delete-misc' ) ) {
				$nonce_valid = true;
			}

			/**
			 * Perform nonce check.
			 *
			 * @since 5.5
			 */
			if ( ! apply_filters( 'bd_action_nonce_check', $nonce_valid, $bd_action ) ) {
				return;
			}

			/**
			 * Before performing a bulk action.
			 * This hook is for doing actions just before performing any bulk operation.
			 *
			 * @since 5.4
			 */
			do_action( 'bd_pre_bulk_action', $bd_action );

			/**
			 * Perform the bulk operation.
			 * This hook is for doing the bulk operation. Nonce check has already happened by this point.
			 *
			 * @since 5.4
			 */
			do_action( 'bd_' . $bd_action, $_POST );
		}

		if ( isset( $_GET['bd_action'] ) ) {
			$bd_action   = sanitize_text_field( $_GET['bd_action'] );
			$nonce_valid = false;

			/**
			 * Perform nonce check.
			 *
			 * @since 5.5.4
			 */
			if ( ! apply_filters( 'bd_get_action_nonce_check', $nonce_valid, $bd_action ) ) {
				return;
			}

			/**
			 * Perform the bulk operation.
			 * This hook is for doing the bulk operation. Nonce check has already happened by this point.
			 *
			 * @since 5.5.4
			 */
			do_action( 'bd_' . $bd_action, $_GET );
		}
	}

	/**
	 * Increase PHP timeout.
	 *
	 * This is to prevent bulk operations from timing out
	 *
	 * @since 5.5.4
	 */
	public function increase_timeout() {
		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		@set_time_limit( 0 );
	}

	/**
	 * Verify if GET request has a valid nonce.
	 *
	 * @since  5.5.4
	 *
	 * @param bool   $result Whether nonce is valid.
	 * @param string $action Action name.
	 *
	 * @return bool True if nonce is valid, otherwise return $result.
	 */
	public function verify_get_request_nonce( $result, $action ) {
		if ( check_admin_referer( "bd-{$action}", "bd-{$action}-nonce" ) ) {
			return true;
		}

		return $result;
	}

	/**
	 * Ajax call back function for getting taxonomies to load select2 options.
	 *
	 * @since 6.0.0
	 */
	public function load_taxonomy_term() {
		$response = array();

		$taxonomy = sanitize_text_field( $_GET['taxonomy'] );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'search'     => sanitize_text_field( $_GET['q'] ),
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$response[] = array(
					absint( $term->term_id ),
					$term->name . ' (' . $term->count . __( ' Posts', 'bulk-delete' ) . ')',
				);
			}
		}

		echo wp_json_encode( $response );
		die;
	}

	/**
	 * Adds the settings link in the Plugin page.
	 *
	 * Based on http://striderweb.com/nerdaphernalia/2008/06/wp-use-action-links/.
	 *
	 * @since 6.0.0 Moved into Controller class.
	 *
	 * @staticvar string $this_plugin
	 *
	 * @param array  $action_links Action Links.
	 * @param string $file         Plugin file name.
	 *
	 * @return array Modified links.
	 */
	public function filter_plugin_action_links( $action_links, $file ) {
		static $this_plugin;

		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( $this->get_plugin_file() );
		}

		if ( $file === $this_plugin ) {
			/**
			 * Filter plugin action links added by Bulk Move.
			 *
			 * @since 6.0.0
			 *
			 * @param array Plugin Links.
			 */
			$bm_action_links = apply_filters( 'bd_plugin_action_links', array() );

			if ( ! empty( $bm_action_links ) ) {
				$action_links = array_merge( $bm_action_links, $action_links );
			}
		}

		return $action_links;
	}

	/**
	 * Log SQL query used by Bulk Delete.
	 *
	 * Query is logged only when `BD_DEBUG` is set.
	 *
	 * @since 5.6
	 * @since 6.0.0 Moved into Controller class.
	 *
	 * @param \WP_Query|\WP_Term_Query|\WP_User_Query $wp_query Query object.
	 */
	public function log_sql_query( $wp_query ) {
		if ( ! property_exists( $wp_query, 'request' ) ) {
			return;
		}

		$query = $wp_query->request;

		/**
		 * Bulk Delete query is getting logged.
		 *
		 * @since 5.6
		 *
		 * @param string $query Bulk Delete SQL Query.
		 */
		do_action( 'bd_log_sql_query', $query );

		error_log( 'Bulk Delete Query: ' . $query );
	}

	/**
	 * Temporary fix to get plugin file.
	 *
	 * TODO: Remove this method from this class.
	 *
	 * @since 6.0.0
	 *
	 * @return string
	 */
	private function get_plugin_file() {
		$bd = BULK_DELETE();

		return $bd->get_plugin_file();
	}

	/**
	 * Load Old hooks.
	 *
	 * TODO: Refactor these hooks into seperate classes.
	 *
	 * @since 6.0.0
	 */
	protected function load_old_hooks() {
		// license related.
		add_action( 'bd_license_form', array( 'BD_License', 'display_activate_license_form' ), 100 );
		add_action( 'bd_deactivate_license', array( 'BD_License', 'deactivate_license' ) );
		add_action( 'bd_delete_license', array( 'BD_License', 'delete_license' ) );
		add_action( 'bd_validate_license', array( 'BD_License', 'validate_license' ), 10, 2 );

		// Settings related.
		add_action( 'bd_before_secondary_menus', array( 'BD_Settings_Page', 'add_menu' ) );
		add_action( 'bd_admin_footer_settings_page', 'bd_modify_admin_footer' );
		add_action( 'admin_init', array( 'BD_Settings', 'create_settings' ), 100 );

		// Help tab related.
		add_action( 'bd_add_contextual_help', array( 'Bulk_Delete_Help_Screen', 'add_contextual_help' ) );

		// Misc page related.
		add_action( 'bd_admin_footer_misc_page', 'bd_modify_admin_footer' );
	}
}
