<?php
/**
 * Manages admin connections pages.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes\Metabox_Field_Mapping;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes\Metabox_Import_Infos;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes\Metabox_Importer_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes\Metabox_Sync_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;

/**
 * Admin Connection
 */
class Admin_Connection {
	/**
	 * Plugin’s context.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Plugin's specific strings.
	 *
	 * @var Plugin_Strings
	 */
	private $plugin_strings;

	/**
	 * Constructor
	 *
	 * @param Plugin_Context $plugin_context Plugin’s context.
	 * @param Plugin_Strings $plugin_strings Plugin's specific strings.
	 */
	public function __construct( $plugin_context, $plugin_strings ) {
		$this->plugin_context = $plugin_context;
		$this->plugin_strings = $plugin_strings;
		$this->add_meta_boxes();

		add_action( 'edit_form_top', array( $this, 'add_header' ) );
		add_action( 'dbx_post_sidebar', array( $this, 'add_footer' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_alpine_defer_attribute' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		add_action( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );
	}

	/**
	 * Output wrapper opening for alpinejs x-data
	 */
	public function add_header() {
		if ( 'airwpsync-connection' === get_post_type() ) {
			echo '<div id="airwpsync-alpine-container" x-data="airWpSyncSettingsHandler" @focusout="change" @input="change" @validate="submit">';
			echo '<div id="airwpsync-validation-notice" class="notice notice-error" style="display:none"><p><strong>' . esc_html__( 'Error:', 'air-wp-sync-pro-plus' ) . '</strong> ' . esc_html__( 'One or more fields have an error. Please check and try again.', 'air-wp-sync-pro-plus' ) . '</p></div>';
		}
	}

	/**
	 * Output wrapper closing for alpinejs x-data
	 */
	public function add_footer() {
		if ( 'airwpsync-connection' === get_post_type() ) {
			echo '</div>';
		}
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'airwpsync-connection' === $screen->id ) {
			wp_enqueue_script( 'air-wp-sync-pro-plus-alpine', plugins_url( 'assets/js/alpinejs@3.10.2.min.js', $this->plugin_context->get_plugin_file() ), false, $this->plugin_context->get_plugin_version(), false );
			wp_enqueue_script( 'air-wp-sync-pro-plus-admin-connection', plugins_url( 'assets/js/admin-page.js', $this->plugin_context->get_plugin_file() ), array( 'air-wp-sync-pro-plus-alpine', 'jquery-ui-tooltip', 'wp-hooks' ), $this->plugin_context->get_plugin_version(), false );
			wp_add_inline_script( 'air-wp-sync-pro-plus-admin-connection', 'var airWpSyncImporterData = ' . $this->get_config(), 'before' );
			wp_add_inline_script( 'air-wp-sync-pro-plus-admin-connection', 'var airWpSync = ' . $this->get_modules_config(), 'before' );
			wp_localize_script( 'air-wp-sync-pro-plus-admin-connection', 'airWpSyncL10n', $this->get_l10n_strings() );
			wp_enqueue_script( 'air-wp-sync-pro-plus-admin-metabox-mapping', plugins_url( 'assets/js/metabox-mapping/main.js', $this->plugin_context->get_plugin_file() ), array( 'air-wp-sync-pro-plus-admin-connection' ), $this->plugin_context->get_plugin_version(), false );
			wp_set_script_translations( 'air-wp-sync-pro-plus-admin-metabox-mapping', 'air-wp-sync-pro-plus', $this->plugin_context->get_plugin_dir() . 'languages' );

			do_action( 'airwpsync/admin/admin-connection/register-styles-scripts' );
		}
	}

	/**
	 * Add defer attribute to AlpineJS script
	 *
	 * @param string $tag The `<script>` tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 */
	public function add_alpine_defer_attribute( $tag, $handle ) {
		if ( 'air-wp-sync-pro-plus-alpine' === $handle ) {
			$tag = str_replace( ' src', ' defer="defer" src', $tag );
		}
		return $tag;
	}

	/**
	 * Add metaboxes for importer post type
	 */
	public function add_meta_boxes() {
		new Metabox_Importer_Settings();
		new Metabox_Field_Mapping( $this->plugin_strings );
		new Metabox_Sync_Settings( $this->plugin_strings );
		new Metabox_Import_Infos( $this->plugin_context, $this->plugin_strings );

		return do_action( 'airwpsync/admin/connection-metaboxes' );
	}

	/**
	 * After importer is saved
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @param bool     $update Whether this is an existing post being updated.
	 */
	public function save_post( $post_id, $post, $update ) {
		if ( ! $update || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'airwpsync-connection' !== $post->post_type ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['content'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$config_json_string = wp_unslash( $_POST['content'] );
			$json               = json_decode( $config_json_string );
			if ( $json ) {
				update_post_meta( $post_id, 'airwpsync_config', $config_json_string );
			}
		}

		// FLush rewrite rules.
		delete_option( 'rewrite_rules' );

		// Clear schedule.
		wp_clear_scheduled_hook( 'air_wp_sync_importer_' . $post_id );
	}

	/**
	 * Before importer is saved
	 *
	 * @param array $data An array of slashed, sanitized, and processed post data.
	 * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
	 */
	public function wp_insert_post_data( $data, $postarr ) {
		if ( 'airwpsync-connection' !== $postarr['post_type'] ) {
			return $data;
		}

		// Update slug.
		if ( array_key_exists( 'post_title', $data ) ) {
			$data['post_name'] = sanitize_title( $data['post_title'] );
		}

		return $data;
	}

	/**
	 * Customize connections update messages
	 *
	 * @param array[] $messages Post updated messages. For defaults see `$messages` declarations above.
	 */
	public function post_updated_messages( $messages ) {
		$back_link_html = sprintf(
			' <a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=airwpsync-connection' ) ),
			__( 'Back to list', 'air-wp-sync-pro-plus' )
		);

		$messages['airwpsync-connection'] = array_replace(
			$messages['post'],
			array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Connection updated.', 'air-wp-sync-pro-plus' ) . $back_link_html,
				4  => __( 'Connection updated.', 'air-wp-sync-pro-plus' ),
				6  => __( 'Connection published.', 'air-wp-sync-pro-plus' ),
				7  => __( 'Connection saved.', 'air-wp-sync-pro-plus' ),
				10 => __( 'Connection draft updated.', 'air-wp-sync-pro-plus' ),
			)
		);

		return $messages;
	}

	/**
	 * Customize connections bulk update messages
	 *
	 * @param array[] $messages Arrays of messages, each keyed by the corresponding post type. Messages are
	 *                               keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
	 * @param int[]   $bulk_counts Array of item counts for each message, used to build internationalized strings.
	 */
	public function bulk_post_updated_messages( $messages, $bulk_counts ) {
		$messages['airwpsync-connection'] = array_replace(
			$messages['post'],
			array(
				/* translators: %s: Number of connections. */
				'updated'   => _n( '%s connection updated.', '%s connections updated.', $bulk_counts['updated'], 'air-wp-sync-pro-plus' ),
				'locked'    => ( 1 === $bulk_counts['locked'] ) ? __( '1 connection not updated, somebody is editing it.', 'air-wp-sync-pro-plus' ) :
					/* translators: %s: Number of connections. */
					_n( '%s connection not updated, somebody is editing it.', '%s connections not updated, somebody is editing them.', $bulk_counts['locked'], 'air-wp-sync-pro-plus' ),
				/* translators: %s: Number of connections. */
				'deleted'   => _n( '%s connection permanently deleted.', '%s connections permanently deleted.', $bulk_counts['deleted'], 'air-wp-sync-pro-plus' ),
				/* translators: %s: Number of connections. */
				'trashed'   => _n( '%s connection moved to the Trash.', '%s connections moved to the Trash.', $bulk_counts['trashed'], 'air-wp-sync-pro-plus' ),
				/* translators: %s: Number of connections. */
				'untrashed' => _n( '%s connection restored from the Trash.', '%s connections restored from the Trash.', $bulk_counts['untrashed'], 'air-wp-sync-pro-plus' ),
			)
		);

		return $messages;
	}

	/**
	 * Get connection config
	 *
	 * @return string|false
	 */
	protected function get_config() {
		global $post;
		$importer = Helper::get_importer_by_id( $post->ID );
		return $importer ? wp_json_encode( $importer->config() ) : '{}';
	}

	/**
	 * Get modules config
	 *
	 * @return string|false
	 */
	protected function get_modules_config() {
		$config = array_map(
			function ( $module ) {
				return array(
					'mappingOptions' => $module->get_mapping_options(),
					'extraConfig'    => $module->get_extra_config(),
				);
			},
			Helper::get_modules()
		);
		return wp_json_encode( $config );
	}

	/**
	 * Get localization strings
	 *
	 * @return array
	 */
	protected function get_l10n_strings() {
		$l10n_strings = array(
			'startingUpdate'       => __( 'In progress...', 'air-wp-sync-pro-plus' ),
			'canceling'            => __( 'Canceling...', 'air-wp-sync-pro-plus' ),
			'unsavedChanges'       => __( 'You have unsaved changes.', 'air-wp-sync-pro-plus' ),
			'requiredField'        => __( 'This fields is required', 'air-wp-sync-pro-plus' ),
			'emptyField'           => __( 'Please select an option in the "Import As" column for all mappings', 'air-wp-sync-pro-plus' ),
			'emptyCustomField'     => __( '"Custom Field" fields can\'t be empty.', 'air-wp-sync-pro-plus' ),
			'databaseNotAvailable' => $this->plugin_strings->get_string( 'admin.connection.database_not_available' ),
		);

		/**
		 * Get localization strings
		 *
		 * @param array $l10n_strings Localization strings.
		 */
		return apply_filters( 'airwpsync/get_l10n_strings', $l10n_strings );
	}
}
