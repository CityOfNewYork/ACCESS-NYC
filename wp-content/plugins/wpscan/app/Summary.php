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
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'admin_init', array( $this, 'add_meta_box_summary' ) );
		add_action( 'wp_ajax_wpscan_check_now', array( $this, 'ajax_check_now' ) );
		add_action( 'wp_ajax_' . $this->parent->WPSCAN_TRANSIENT_CRON, array( $this, 'ajax_doing_cron' ) );
	}

	/**
	 * Add meta box
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
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
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function do_meta_box_summary() {
		$report = $this->parent->get_report();
		$errors = get_option($this->parent->OPT_ERRORS);
		$total  = $this->parent->get_total_not_ignored();
		?>

		<?php
			// Check if we have run a scan yet.
			if ( ! empty( $this->parent->get_report() ) ) {
		?>

		<p>
			<?php _e( 'The last full scan was run on: ', 'wpscan' ) ?>
		</p>
		<p>
			<span class="dashicons dashicons-calendar-alt"></span>

			<strong>
				<?php
					if ( array_key_exists('cache', $report ) ) {
						echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $report['cache'] );
					} else {
						echo _e( 'No full scan yet', 'wpscan' );
					}
				?>
			</strong>
		</p>

			<?php
				// Only show next scan time if there's a scheduled cron job.
				if ( wp_next_scheduled( $this->parent->WPSCAN_SCHEDULE ) ) {
			?>
				<p>
					<?php _e( 'The next scan will automatically be run on ', 'wpscan' ) ?>
					<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), wp_next_scheduled( $this->parent->WPSCAN_SCHEDULE ) ); ?>
				</p>
			<?php } ?>

		<?php } ?>

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
			} elseif( ! get_option($this->parent->OPT_API_TOKEN) ) {
				echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'You need to add a WPScan API Token to the settings page', 'wpscan' ) . '</strong></p>';
			} else {
				echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'Some vulnerabilities were found', 'wpscan' ) . '</strong></p>';
			}
		?>

		<p class="description">
			<?php _e( 'Click the Run All button to run a full vulnerability scan against your WordPress website.', 'wpscan' ) ?>
		</p>

		<?php if ( get_option( $this->parent->OPT_API_TOKEN ) ) : ?>
			<p class="check-now">
				<span class="spinner"></span>
				<button type="button" class="button button-primary"><?php _e( 'Run All', 'wpscan' ) ?></button>
			</p>
		<?php endif ?>

		<?php
	}

	/**
	 * Ajax check now
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function ajax_check_now() {
		check_ajax_referer( 'wpscan' );

		if ( ! current_user_can( $this->parent->WPSCAN_ROLE ) ) {
			wp_redirect( home_url() );
			wp_die();
		}

		$this->parent->check_now();

		wp_die();
	}

	/**
	 * Ajax to check when the cron task has finished
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function ajax_doing_cron() {
		check_ajax_referer( 'wpscan' );

		if ( ! current_user_can( $this->parent->WPSCAN_ROLE ) ) {
			wp_redirect( home_url() );
			wp_die();
		}

		echo get_transient( $this->parent->WPSCAN_TRANSIENT_CRON ) ? 'YES' : 'NO';

		wp_die();
	}
}
