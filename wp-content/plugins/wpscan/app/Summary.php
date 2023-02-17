<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Summary.
 *
 * Displays the Summary box.
 *
 * @since 1.0.0
 */
class Summary {
	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'admin_init', array( $this, 'add_meta_box_summary' ) );
		add_action( 'wp_ajax_wpscan_check_now', array( $this, 'ajax_check_now' ) );

		if ( get_option( $this->parent->OPT_DISABLE_CHECKS, array() ) !== '1' ) {
		  add_action( 'wp_ajax_wpscan_security_check_now', array( $this, 'ajax_security_check_now' ) );
		}

		add_action( 'wp_ajax_' . $this->parent->WPSCAN_TRANSIENT_CRON, array( $this, 'ajax_doing_cron' ) );
	}

	/**
	 * Add meta box
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function add_meta_box_summary() {
		$report = $this->parent->get_report();

		add_meta_box(
			'wpscan-metabox-summary',
			__( 'Summary', 'wpscan' ),
			array( $this, 'do_meta_box_summary' ),
			'wpscan',
			'side',
			'high'
		);
	}

	/**
	 * Render meta box
	 *
	 * @return string
	 * @since 1.0.0
	 * @access public
	 */
	public function do_meta_box_summary() {
		$report = $this->parent->get_report();
		$errors = get_option( $this->parent->OPT_ERRORS );
		$total  = $this->parent->get_total_not_ignored();
		?>

		<?php
		// Check if we have run a scan yet.
		if ( ! empty( $this->parent->get_report() ) ) {
			?>

			<?php
			if ( ! empty( $errors ) ) {
				foreach ( $errors as $err ) {
					// $err should not contain user input. If you like to add an esc_html() here, be sure to update the error text that use HTML
					echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . $err . '</strong></p>';
				}
			} elseif ( empty( $this->parent->get_report() ) ) { // No scan run yet.
				echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'No scan run yet!', 'wpscan' ) . '</strong></p>';
			} elseif ( empty( $errors ) && 0 === $total ) {
				echo '<p class="wpscan-summary-res is-green"><span class="dashicons dashicons-awards"></span> <strong>' . __( 'No known vulnerabilities found', 'wpscan' ) . '</strong></p>';
			} elseif ( ! get_option( $this->parent->OPT_API_TOKEN ) ) {
				echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'You need to add a WPScan API Token to the settings page', 'wpscan' ) . '</strong></p>';
			} else {
				echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'Some vulnerabilities were found', 'wpscan' ) . '</strong></p>';
			}
			?>

            <p>
				<?php _e( 'The last full scan was run on: ', 'wpscan' ); ?>
            </p>
            <p>
                <span class="dashicons dashicons-calendar-alt"></span>

                <strong>
					<?php
					if ( array_key_exists( 'cache', $report ) ) {
						echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $report['cache'] );
					} else {
						echo _e( 'No full scan yet', 'wpscan' );
					}
					?>
                </strong>
            </p>

			<?php if ( false !== as_next_scheduled_action( $this->parent->WPSCAN_SCHEDULE ) ) { ?>
                <p>
					<?php _e( 'The next scan will automatically be run on ', 'wpscan' ); ?>
					<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), as_next_scheduled_action( $this->parent->WPSCAN_SCHEDULE ) ); ?>
                </p>
			<?php } ?>

		<?php } ?>

        <p class="description">
			<?php
			if ( get_option( $this->parent->OPT_API_TOKEN ) ) {
				_e( 'Click the Run All button to run a full vulnerability scan against your WordPress website.', 'wpscan' );
			} else {
				_e( 'Add your API token to the settings page to be able to run a full scan.', 'wpscan' );
			}
			?>
        </p>

		<?php if ( get_option( $this->parent->OPT_API_TOKEN ) ) : ?>
            <p class="check-now">
				<?php
				$spinner_display = '';
				$button_disabled = '';
				if ( false !== as_next_scheduled_action( $this->parent->WPSCAN_RUN_ALL ) ) {
					$spinner_display = ' style="visibility: visible;"';
					$button_disabled = 'disabled';
				}
				?>
                <span class="spinner"<?php echo $spinner_display; ?>></span>
                <button type="button" class="button button-primary"<?php echo $button_disabled; ?>><?php _e( 'Run All', 'wpscan' ); ?></button>
            </p>
		<?php endif ?>

		<?php
	}

	/**
	 * Ajax check now
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function ajax_check_now() {
		check_ajax_referer( 'wpscan' );

		if ( ! current_user_can( $this->parent->WPSCAN_ROLE ) ) {
			wp_redirect( home_url() );
			wp_die();
		}

		if ( false === as_next_scheduled_action( $this->parent->WPSCAN_RUN_ALL ) ) {
			as_schedule_single_action( strtotime( 'now' ), $this->parent->WPSCAN_RUN_ALL );
		}

		wp_die();
	}

	/**
	 * Ajax security check now
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function ajax_security_check_now() {
		check_ajax_referer( 'wpscan' );

		if ( ! current_user_can( $this->parent->WPSCAN_ROLE ) ) {
			wp_redirect( home_url() );
			wp_die();
		}

		$items_inline = get_option( $this->parent->WPSCAN_RUN_SECURITY );

		$plugins = array();
		foreach ( $this->parent->classes['checks/system']->checks as $id => $data ) {
			$plugins[ $id ] = array(
				'status'                 => $this->parent->classes['report']->get_status( 'security-checks', $id ),
				'vulnerabilities'        => $this->parent->classes['checks/system']->get_check_vulnerabilities( $data['instance'] ),
				'security-check-actions' => $this->parent->classes['checks/system']->get_list_actions( $data['instance'] ),
			);
		}

		$response = array(
			'inline'  => $items_inline,
			'plugins' => $plugins,
		);

		wp_die( wp_json_encode( $response ) );
	}

	/**
	 * Ajax to check when the cron task has finished
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function ajax_doing_cron() {
		check_ajax_referer( 'wpscan' );

		if ( ! current_user_can( $this->parent->WPSCAN_ROLE ) ) {
			wp_redirect( home_url() );
			wp_die();
		}

		//      echo get_transient( $this->parent->WPSCAN_TRANSIENT_CRON ) ? 'YES' : 'NO';
		echo false !== as_next_scheduled_action( $this->parent->WPSCAN_RUN_ALL ) ? 'YES' : 'NO';

		wp_die();
	}
}
