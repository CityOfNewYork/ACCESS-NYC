<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Account.
 *
 * Deals with user's wpvulndb API user accounts.
 *
 * @since 1.0.0
 */
class Account {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 * @param object $parent parent.
	 * @access public
	 * @return void
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'wpscan/api/get/after', array( $this, 'update_account_status' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'add_account_summary_meta_box' ) );
	}

	/**
	 * Update account status after the api request.
	 *
	 * @since 1.0.0
	 * @param string $endpoint endpoint.
	 * @param string $response response.
	 * @access public
	 * @return void
	 */
	public function update_account_status( $endpoint, $response ) {
		$wp_endpoit = '/wordpresses/' . str_replace( '.', '', get_bloginfo( 'version' ) );

		if ( $endpoint === $wp_endpoit ) {
			$current = get_option( $this->parent->OPT_ACCOUNT_STATUS, array() );
			$updated = $current;

			$updated['limit']     = wp_remote_retrieve_header( $response, 'x-ratelimit-limit' );
			$updated['remaining'] = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
			$updated['reset']     = wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );

			if ( ! isset( $current['plan'] ) || $current['limit'] !== $updated['limit'] ) {
				$req = $this->parent->api_get( '/status' );

				// Plan.
				if ( is_object( $req ) ) {
					$updated['plan'] = $req->plan;
				}

				// For enterprise users.
				if ( -1 === $req->requests_remaining ) {
					$updated['limit']     = __( 'unlimited', 'wpscan' );
					$updated['remaining'] = __( 'unlimited', 'wpscan' );
					$updated['reset']     = __( 'unlimited', 'wpscan' );
				}
			}

			update_option( $this->parent->OPT_ACCOUNT_STATUS, $updated );
		}
	}

	/**
	 * Add meta box
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function add_account_summary_meta_box() {
		if ( $this->parent->classes['settings']->api_token_set() ) {
			add_meta_box(
				'wpscan-account-summary',
				__( 'Account Status', 'wpscan' ),
				array( $this, 'do_meta_box_account_summary' ),
				'wpscan',
				'side',
				'low'
			);
		}
	}

	/**
	 * Get account status
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function get_account_status() {
		$defaults = array(
			'plan'      => 'NO DATA',
			'limit'     => 50,
			'remaining' => 50,
			'reset'     => time(),
		);

		return get_option( $this->parent->OPT_ACCOUNT_STATUS, $defaults );
	}

	/**
	 * Render account status metabox
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function do_meta_box_account_summary() {
		extract( $this->get_account_status() );

		if ( 'enterprise' !== $plan ) {
			if ( ! isset( $limit ) || ! is_numeric( $limit ) ) {
				return;
			}

			// Reset time in hours.
			$diff          = $reset - time();
			$days          = floor( $diff / ( 60 * 60 * 24 ) );
			$hours         = round( ( $diff - $days * 60 * 60 * 24 ) / ( 60 * 60 ) );
			$hours_display = $hours > 1 ? __( 'Hours', 'wpscan' ) : __( 'Hour', 'wpscan' );

			// Used.
			$used = $limit - $remaining;

			// Usage percentage.
			$percentage = 0 !== $limit ? ( $used * 100 ) / $limit : 0;

			// Usage color.
			if ( $percentage < 50 ) {
				$usage_color = 'wpscan-status-green';
			} elseif ( $percentage >= 50 && $percentage < 95 ) {
				$usage_color = 'wpscan-status-orange';
			} else {
				$usage_color = 'wpscan-status-red';
			}
		} else {
			// For enterprise users.
			$used          = $limit;
			$hours         = $reset;
			$hours_display = null;
			$usage_color   = 'wpscan-status-green';
		}

		// Upgrade button.
		$btn_text = 'free' === $plan ? __( 'Upgrade', 'wpscan' ) : __( 'Manage', 'wpscan' );
		$btn_url  = WPSCAN_PROFILE_URL;

		// Output data.
		echo '<ul>';
		echo '<li>' . __( 'Plan', 'wpscan' ) . '<span>' . esc_html( $plan ) . '</span></li>';

		if ( 'enterprise' !== $plan ) {
			echo '<li>' . __( 'Usage', 'wpscan' ) . "<span class='$usage_color'> $used / $limit </span></li>";
			echo '<li>' . __( 'Resets In', 'wpscan' ) . "<span> $hours $hours_display </span></li>";
		}
		echo '</ul>';

		// Output upgrade/manage button.
		echo "<a class='button button-primary' href='$btn_url' target='_blank'>$btn_text</a>";
	}
}
