<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;

use GatherContent\Importer\Settings\Form_Section;
use GatherContent\Importer\Admin\Admin;

/**
 * GatherContent Plugin Debug
 *
 * Docs: https://github.com/gathercontent/wordpress-plugin/wiki/Developer-Debug-Mode
 *
 * @since 3.0.1
 */
class Debug extends Base {

	/**
	 * A flag to check if we are in debug mode.
	 *
	 * @var boolean
	 */
	protected static $debug_mode = false;

	/**
	 * GatherContent\Importer\Admin\Admin instance.
	 *
	 * @var GatherContent\Importer\Admin\Admin
	 */
	protected $admin;

	/**
	 * The GC log file name.
	 *
	 * @var string
	 */
	protected static $log_file = 'gathercontent-debug.log';

	/**
	 * The path to the GC log file.
	 *
	 * @var string
	 */
	protected static $log_path = '';

	/**
	 * The query string used to trigger debug mode.
	 * The query string value must be in the current date in the 'm-d-Y' format.
	 * e.g. ?gathercontent_debug_mode=11-17-2017
	 *
	 * @since 3.1.7
	 *
	 * @var string
	 */
	protected static $query_string = 'gathercontent_debug_mode';

	/**
	 * Constructor. Sets the asset_suffix var.
	 *
	 * @since 3.0.1
	 */
	public function __construct( Admin $admin ) {
		$this->admin = $admin;

		self::$log_path = WP_CONTENT_DIR . '/' . self::$log_file;

		if ( $debug_mode_enabled = get_option( self::$query_string ) ) {
			if ( time() > $debug_mode_enabled ) {
				delete_option( self::$query_string );
			} else {
				self::$debug_mode = true;
			}
		} else {
			// Check if constant is set.
			self::$debug_mode = self::has_debug_constant();
		}
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function init_hooks() {
		if ( is_admin() && isset( $_GET[ self::$query_string ] ) ) {
			$enabled = self::toggle_debug_mode( $_GET[ self::$query_string ] );
			unset( $_GET[ self::$query_string ] );
			add_action( 'all_admin_notices', array( $this, $enabled ? 'debug_enabled_notice' : 'debug_disabled_notice' ) );
		}

		if ( ! self::$debug_mode ) {
			return;
		}

		add_filter( "sanitize_option_{$this->admin->option_name}", array( $this, 'do_debug_options_actions' ), 5 );

		add_action( 'admin_init', array( $this, 'add_debug_fields' ), 50 );
		add_action( 'gc_sync_items_result', array( $this, 'log_sync_results' ), 10, 2 );
	}

	/**
	 * Hooked to `gc_sync_items_result`, logs results to the debug mode log.
	 *
	 * @since  3.0.1
	 *
	 * @param  mixed  $maybe_error Result of sync.
	 * @param  object $sync        The GatherContent\Importer\Sync\Pull or GatherContent\Importer\Sync\Push object.
	 *
	 * @return void
	 */
	public function log_sync_results( $maybe_error, $sync ) {
		self::debug_log( $maybe_error, $sync->direction . ' items result' );
	}

	/**
	 * Outputs admin notice that the debug mode has been disabled.
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function debug_disabled_notice() {
		$msg = esc_html__( 'GatherContent Debug Mode: Disabled', 'gathercontent-import' );
		echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
	}

	/**
	 * Outputs admin notice that the debug mode has been enabled.
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function debug_enabled_notice() {
		$msg = esc_html__( 'GatherContent Debug Mode: Enabled', 'gathercontent-import' );
		echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
	}

	/**
	 * Adds debug fields to the GatherConent API connection settings page.
	 *
	 * @since 3.0.1
	 */
	public function add_debug_fields() {
		$section = new Form_Section(
			'debug',
			__( 'Debug Mode', 'gathercontent-import' ),
			sprintf( __( 'Debug file location: %s', 'gathercontent-import' ), '<code>wp-content/'. self::$log_file .'</code>' ),
			Admin::SLUG
		);

		$section->add_field(
			'log_importer_requests',
			__( 'Log Importer Requests?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'review_stuck_status',
			__( 'Review stuck sync statuses?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'delete_stuck_status',
			__( 'Delete stuck sync statuses?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'view_gc_log_file',
			__( 'View contents of the GatherContent debug log file?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'delete_gc_log_file',
			__( 'Delete GatherContent debug log file?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'disable_debug_mode',
			__( 'Disable Debug Mode?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

	}

	/**
	 * The Debug mode checkbox toggle field callback.
	 *
	 * @since  3.0.1
	 *
	 * @param  GatherContent\Importer\Settings\Form_Section $field Form_Section object.
	 *
	 * @return void
	 */
	public function debug_checkbox_field_cb( $field ) {
		$id = $field->param( 'id' );

		$args = array(
			'id' => $id,
			'name' => $this->admin->option_name .'[debug]['. $id .']',
			'type' => 'checkbox',
			'class' => '',
			'value' => 1,
		);

		if ( $this->admin->get_setting( $id ) ) {
			$args['checked'] = 'checked';
		}

		$this->view( 'input', $args );
	}

	/**
	 * Handles the actions associated with the debug checkbox toggle fields.
	 *
	 * @since  3.0.1
	 *
	 * @param  array  $settings Array of settings.
	 *
	 * @return void
	 */
	public function do_debug_options_actions( $settings ) {
		if ( empty( $settings['debug']['log_importer_requests'] ) ) {
			$settings['log_importer_requests'] = false;
			unset( $settings['debug'] );
			return $settings;
		}

		if ( empty( $settings['debug'] ) ) {
			return $settings;
		}

		$orig_settings = $settings;
		$settings = wp_parse_args( $settings['debug'], array(
			'log_importer_requests' => false,
			'review_stuck_status'   => false,
			'delete_stuck_status'   => false,
			'view_gc_log_file'      => false,
			'delete_gc_log_file'    => false,
			'disable_debug_mode'    => false,
		) );

		$back_url = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
		$back_button = $back_url ? '<p><a href="'. $back_url . '">'. __( 'Go Back', 'gathercontent-import' ) .'</a></p>' : '';

		if ( $settings['review_stuck_status'] || $settings['delete_stuck_status']  ) {

			return $this->handle_stuck_statuses( $settings, $back_button );

		} elseif ( $settings['delete_gc_log_file'] ) {

			return $this->delete_gc_log_file( $back_button );

		} elseif ( $settings['view_gc_log_file'] ) {

			return $this->view_gc_log_file( $back_button );

		} elseif ( $settings['disable_debug_mode'] ) {

			wp_safe_redirect( add_query_arg( self::$query_string, 0, $back_url ) );
			exit;

		} elseif ( $settings['log_importer_requests'] ) {
			$orig_settings['log_importer_requests'] = true;
			unset( $orig_settings['debug'] );
			return $orig_settings;
		}

		wp_die( '<xmp>'. __LINE__ .') $settings: '. print_r( $settings, true ) .'</xmp>' . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
	}

	/**
	 * Handles the actions associated with the stuck statuses checkboxes.
	 *
	 * @since  3.0.1
	 *
	 * @param  array  $settings    Array of settings
	 * @param  string $back_button The back button markup.
	 *
	 * @return void
	 */
	public function handle_stuck_statuses( $settings, $back_button ) {
		global $wpdb;

		$sql = "SELECT `option_name` FROM `$wpdb->options` WHERE `option_name` LIKE ('gc_pull_item_%') OR `option_name` LIKE ('gc_push_item_%');";
		$options = $wpdb->get_results( $sql );

		if ( ! empty( $options ) ) {
			foreach ( $options as $key => $option ) {
				$options[ $key ] = array(
					'name' => $option->option_name,
					'value' => get_option( $option->option_name ),
				);
			}
		} else {
			wp_die( __( 'There are no stuck statuses.', 'gathercontent-import' ) . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
		}

		if ( $settings['delete_stuck_status'] ) {
			foreach ( $options as $key => $option ) {
				$options[ $key ]['deleted'] = delete_option( $option['name'] );
			}
		}

		wp_die( '<xmp>'. __LINE__ .') $options: '. print_r( $options, true ) .'</xmp>' . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
	}

	/**
	 * Handles the deleting the debug mode log.
	 *
	 * @since  3.0.1
	 *
	 * @param  string $back_button The back button markup.
	 *
	 * @return void
	 */
	public function delete_gc_log_file( $back_button ) {
		if ( unlink( self::$log_path ) ) {
			wp_die( __( 'GatherContent log file deleted.', 'gathercontent-import' ) . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
		}

		wp_die( __( 'Failed to delete GatherContent log file.' . $back_button, 'gathercontent-import' ), __( 'Debug Mode', 'gathercontent-import' ) );
	}

	/**
	 * Handles the view for the debug mode log.
	 *
	 * @since  3.0.1
	 *
	 * @param  string $back_button The back button markup.
	 *
	 * @return void
	 */
	public function view_gc_log_file( $back_button ) {
		$log_contents = file_exists( self::$log_path ) ? file_get_contents( self::$log_path ) : '';

		if ( ! $log_contents ) {
			wp_die( __( 'GatherContent log file is empty.', 'gathercontent-import' ) . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
		}

		die( '<html><body>'. $back_button .'<pre><textarea style="width:100%;height:100%;min-height:1000px;font-size:14px;font-family:monospace;padding:.5em;">'. print_r( $log_contents, true ) .'</textarea></pre></body></html>' );
	}

	/**
	 * Check if GATHERCONTENT_DEBUG_MODE constant is set.
	 *
	 * @since  3.0.2
	 *
	 * @return string
	 */
	public static function has_debug_constant() {
		return defined( 'GATHERCONTENT_DEBUG_MODE' ) && GATHERCONTENT_DEBUG_MODE;
	}

	/**
	 * Check if SCRIPT_DEBUG is enabled.
	 *
	 * @since  3.0.1
	 *
	 * @return string
	 */
	public static function debug_mode() {
		return self::$debug_mode;
	}

	/**
	 * Enable/disable the Debug Mode.
	 *
	 * @since  3.0.1
	 *
	 * @param  bool  $debug_enabled Enable/Disable
	 *
	 * @return bool                 Whether it has been enabled.
	 */
	public static function toggle_debug_mode( $debug_enabled ) {
		$changed = false;
		if ( ! $debug_enabled ) {
			delete_option( self::$query_string );
			$changed = ! empty( self::$debug_mode );
		} elseif ( date( 'm-d-Y' ) === $debug_enabled ) {
			update_option( self::$query_string, time() + DAY_IN_SECONDS );
			$changed = empty( self::$debug_mode );
		} else {
			$debug_enabled = self::$debug_mode;
		}

		self::$debug_mode = $debug_enabled || self::has_debug_constant();

		if ( $changed ) {
			$status = self::$debug_mode
				? esc_html__( 'Enabled', 'gathercontent-import' )
				: esc_html__( 'Disabled', 'gathercontent-import' );

			self::_debug_log( sprintf( esc_html__( 'GatherContent Debug Mode: %s', 'gathercontent-import' ), $status ) );
		}

		return self::$debug_mode;
	}

	/**
	 * Write a message to the log if debug enabled.
	 *
	 * @since  3.0.1
	 *
	 * @param  string  $message Message to write to log file.
	 * @param  string  $title   Describes what is being logged.
	 *
	 * @return void
	 */
	public static function debug_log( $message = '', $title = '' ) {
		if ( self::$debug_mode ) {
			self::_debug_log( $message, $title );
		}
	}

	/**
	 * Write a message to the log.
	 *
	 * @since  3.0.1
	 *
	 * @param  string  $message Message to write to log file.
	 *
	 * @return void
	 */
	protected static function _debug_log( $message = '', $title = '' ) {
		$message = print_r( $message, 1 );
		if ( $title ) {
			$message = print_r( $title, 1 ) . ': ' . $message;
		}
		error_log( date('Y-m-d H:i:s') .': '. $message ."\r\n", 3, self::$log_path );
	}

}
