<?php

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * System.
 *
 * General system functions.
 *
 * @since 1.0.0
 */
class System {

	public $OPT_FATAL_ERRORS = 'wpscan_fatal_errors';

	// Schedule.
	public $WPSCAN_SECURITY_SCHEDULE = 'wpscan_security_schedule';

	// Events Inline.
	public $OPT_EVENTS_INLINE = 'wpscan_events_inline';

	// Current running events.
	public $current_running = '';

	/**
	 * A list of registered checks.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $checks = array();

	/**
	 * Class constructor.
	 *
	 * @param object $parent parent.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function __construct( $parent ) {
		$this->parent          = $parent;
		$this->current_running = get_option( $this->OPT_EVENTS_INLINE );

		register_shutdown_function( array( $this, 'catch_errors' ) );

		add_action( 'admin_notices', array( $this, 'display_errors' ) );

		add_action( 'plugins_loaded', array( $this, 'load_checks' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		add_action( 'wp_ajax_wpscan_check_action', array( $this, 'handle_actions' ) );

		add_action( $this->WPSCAN_SECURITY_SCHEDULE, array( $this, 'security_check_now' ), 99 );
	}

	/**
	 * Register Admin Scripts
	 *
	 * @param string $hook parent.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function admin_enqueue( $hook ) {
		if ( $hook === $this->parent->page_hook ) {
			wp_enqueue_script(
				'wpscan-security-checks',
				plugins_url( 'assets/js/security-checks.js', WPSCAN_PLUGIN_FILE ),
				array( 'jquery-ui-tooltip' )
			);
		}
	}

	/**
	 * Load checks files.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function load_checks() {
		$dir     = $this->parent->plugin_dir . 'security-checks';
		$folders = array_diff( scandir( $dir ), array( '..', '.' ) );

		foreach ( $folders as $folder ) {
			$file = "$dir/$folder/check.php";

			if ( '.' === $folder[0] ) {
				continue;
			}

			require_once $file;

			$data = get_file_data( $file, array( 'classname' => 'classname' ) );

			$data['instance'] = new $data['classname']( $folder, "$dir/$folder", $this->parent );

			$this->checks[ $folder ] = $data;
		}
	}

	/**
	 * Register a shutdown hook to catch errors
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function catch_errors() {
		$error = error_get_last();

		if ( $error && $error['type'] ) {

			if ( basename( $error['file'] ) == 'check.php' ) {
				$errors = get_option( $this->OPT_FATAL_ERRORS, array() );

				array_push( $errors, $error );

				update_option( $this->OPT_FATAL_ERRORS, array_unique( $errors ) );

				$report = $this->parent->get_report();

				$report['cache'] = strtotime( current_time( 'mysql' ) );

				update_option( $this->parent->OPT_REPORT, $report );

				$this->parent->classes['account']->update_account_status();

				delete_transient( $this->parent->WPSCAN_TRANSIENT_CRON );
			}
		}
	}

	/**
	 * Display fatal errors
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function display_errors() {
		$screen = get_current_screen();
		$errors = get_option( $this->OPT_FATAL_ERRORS, array() );

		if ( strstr( $screen->id, $this->parent->classes['report']->page ) ) {
			foreach ( $errors as $err ) {
				$msg = explode( 'Stack', $err['message'] )[0];
				$msg = trim( $msg );

				echo "<div class='notice notice-error'><p>$msg</p></div>";
			}
		}
	}

	/**
	 * Return vulnerabilities in the report.
	 * 
	 * This is very similar, but subtly different to
	 *  Report->list_security_check_vulnerabilities().
	 * Should see if they could be merged.
	 *
	 * @param object $check - The check instance.
	 *
	 * @access public
	 * @return string
	 * @since 1.14.4
	 *
	 */
	public function get_check_vulnerabilities( $instance ) {
		$vulnerabilities = $instance->get_vulnerabilities();
		$count           = $instance->get_vulnerabilities_count();
		$ignored         = $this->parent->get_ignored_vulnerabilities();

		$not_checked_text = __( 'Not checked yet. Click the Run button to run a scan', 'wpscan' );

		if ( ! isset( $vulnerabilities ) ) {
			return esc_html( $not_checked_text );
		} elseif ( empty( $vulnerabilities ) || 0 === $count ) {
			return esc_html( $instance->success_message() );
		} else {
			$list = array();

			foreach ( $vulnerabilities as $item ) {
				if ( in_array( $item['id'], $ignored, true ) ) {
					continue;
				}

				$html   = "<div class='vulnerability'>";
				$html  .= "<div class='vulnerability-severity'>";
				$html  .= "<span class='wpscan-" . esc_attr( $item['severity'] ) . "'>" . esc_html( $item['severity'] ) . '</span>';
				$html  .= '</div>';
				$html  .= "<div class='vulnerability-title'>" . wp_kses( $item['title'], array( 'a' => array( 'href' => array() ) ) ) . '</div>';
				$html  .= '</div>';
				$list[] = $html;
			}

			return join( '<br>', $list );
		}
	}

	/**
	 * Display actions buttons
	 *
	 * @param object $instance - The check instance.
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function list_actions( $instance ) {
		foreach ( $instance->actions as $action ) {
			$confirm         = isset( $action['confirm'] ) ? $action['confirm'] : false;
			$button_text     = ( $this->current_running && array_key_exists( $instance->id, $this->current_running ) && 'dismiss' !== $action['id'] ) ? esc_html__( 'Running', 'wpscan' ) : esc_html( $action['title'] );
			$button_disabled = ( $this->current_running && array_key_exists( $instance->id, $this->current_running ) && 'dismiss' !== $action['id'] ) ? ' disabled' : '';

			echo sprintf(
				"<button class='button' data-check-id='%s' data-confirm='%s' data-action='%s'%s> %s</button>",
				esc_attr( $instance->id ),
				esc_attr( $confirm ),
				esc_attr( $action['id'] ),
				$button_disabled,
				$button_text
			);
		}
	}

	/**
	 * Get actions buttons
	 *
	 * @param object $instance - The check instance.
	 *
	 * @access public
	 * @return string
	 * @since 1.14.4
	 *
	 */
	public function get_list_actions( $instance ) {
		foreach ( $instance->actions as $action ) {
			$confirm         = isset( $action['confirm'] ) ? $action['confirm'] : false;
			$button_text     = ( $this->current_running && array_key_exists( $instance->id, $this->current_running ) && 'dismiss' !== $action['id'] ) ? esc_html__( 'Running', 'wpscan' ) : esc_html( $action['title'] );
			$button_disabled = ( $this->current_running && array_key_exists( $instance->id, $this->current_running ) && 'dismiss' !== $action['id'] ) ? ' disabled' : '';

			return sprintf(
				"<button class='button' data-check-id='%s' data-confirm='%s' data-action='%s'%s> %s</button>",
				esc_attr( $instance->id ),
				esc_attr( $confirm ),
				esc_attr( $action['id'] ),
				$button_disabled,
				$button_text
			);
		}
	}

	/**
	 * Load checks files.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function handle_actions() {
		check_ajax_referer( 'wpscan' );

		if ( ! current_user_can( $this->parent->WPSCAN_ROLE ) ) {
			wp_die();
		}

		$check  = isset( $_POST['check'] ) ? $_POST['check'] : false;
		$action = isset( $_POST['action_id'] ) ? $_POST['action_id'] : false;

		if ( $action && $check ) {
			$res = 0;
			if ( 'run' === $action ) {
				$event_type[ $check ] = $action;
				$this->add_event_inline( $event_type );

				if ( false === as_next_scheduled_action( $this->WPSCAN_SECURITY_SCHEDULE ) ) {
					as_schedule_single_action( strtotime( 'now' ), $this->WPSCAN_SECURITY_SCHEDULE );
				}
				$res = 1;
			} else {
				$action = array_filter(
					$this->checks[ $check ]['instance']->actions,
					function ( $i ) use ( $action ) {
						return $i['id'] === $action;
					}
				);

				$action = current( $action );

				if ( method_exists( $this->checks[ $check ]['instance'], $action['method'] ) ) {
					$res = call_user_func( array( $this->checks[ $check ]['instance'], $action['method'] ) );
				}
			}

			if ( $res ) {
				wp_send_json_success( $check );
			} else {
				wp_send_json_error();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Run the Security checks
	 *
	 * @since 1.15
	 * @acces public
	 */
	public function security_check_now() {
		if ( ! empty( $this->current_running ) ) {
			foreach ( $this->current_running as $key => $to_check ) {
				$check  = $key;
				$action = $to_check;
				$action = array_filter(
					$this->checks[ $check ]['instance']->actions,
					function ( $i ) use ( $action ) {
						return $i['id'] === $action;
					}
				);

				$action = current( $action );

				if ( method_exists( $this->checks[ $check ]['instance'], $action['method'] ) ) {
					call_user_func( array( $this->checks[ $check ]['instance'], $action['method'] ) );
				}
				$this->remove_event_from_list( $check );
				as_schedule_single_action( strtotime( 'now' ) + 10, $this->WPSCAN_SECURITY_SCHEDULE );

				break;
			}
		} else {
			delete_option( $this->OPT_EVENTS_INLINE );
		}
	}

	/**
	 * Register event to wait inline
	 *
	 * @param $event_type
	 *
	 * @since 1.15
	 * @acces public
	 */
	public function add_event_inline( $event_type ) {
		if ( $this->current_running ) {
			update_option( $this->OPT_EVENTS_INLINE, $this->current_running + $event_type );
		} else {
			update_option( $this->OPT_EVENTS_INLINE, $event_type );
		}
	}

	/**
	 * Remove event from the waiting line
	 *
	 * @param $event
	 *
	 * @since 1.15
	 * @acces public
	 */
	public function remove_event_from_list( $event ) {
		if ( $event ) {
			unset( $this->current_running[ $event ] );
			update_option( $this->OPT_EVENTS_INLINE, $this->current_running );
		}
	}
}
