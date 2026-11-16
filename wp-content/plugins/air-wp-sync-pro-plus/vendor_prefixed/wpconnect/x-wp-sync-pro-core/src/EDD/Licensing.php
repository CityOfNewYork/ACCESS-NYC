<?php
/**
 * Licensing.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\EDD;

use WP_Error;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Models\Licensing_Data;

/**
 * Class Licensing
 */
class Licensing {

	/**
	 * Licensing data.
	 *
	 * @var Licensing_Data
	 */
	protected $licensing_data;

	/**
	 * Constructor
	 *
	 * @param Licensing_Data $licensing_data Licensing data.
	 */
	public function __construct( $licensing_data ) {
		$this->licensing_data = $licensing_data;
	}

	/**
	 * Activate a license.
	 *
	 * @param string $license License key.
	 *
	 * @return string|WP_Error
	 */
	public function activate( $license = '' ) {
		$args = array(
			'timeout' => 25,
			'body'    => array(
				'edd_action'  => 'activate_license',
				'license'     => $license,
				'item_id'     => $this->licensing_data->get_wpc_product_id(),
				'url'         => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
				'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			),
		);

		$response = wp_remote_post( $this->licensing_data->get_wpc_url(), apply_filters( 'airwpsync/licensing/request_args', $args ) );

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$error = sprintf(
			/* translators: the HTTP response code */
				__( 'WP connect server returned an HTTP error, code: %s', 'air-wp-sync-pro-plus' ),
				wp_remote_retrieve_response_code( $response )
			);
		}

		if ( ! isset( $error ) ) {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( true === $license_data->success ) {
				return $license_data->license;
			}
			$error = $this->get_license_error_message( $license_data->error, $license_data->expires ?? '' );
		}

		return new WP_Error( 'license_activation_api_error', $error );
	}

	/**
	 * Deactivate a license.
	 *
	 * @param string $license License key.
	 *
	 * @return string|WP_Error
	 */
	public function deactivate( $license = '' ) {
		$args = array(
			'timeout' => 25,
			'body'    => array(
				'edd_action'  => 'deactivate_license',
				'license'     => $license,
				'item_id'     => $this->licensing_data->get_wpc_product_id(),
				'url'         => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
				'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			),
		);

		$response = wp_remote_post( $this->licensing_data->get_wpc_url(), apply_filters( 'airwpsync/licensing/request_args', $args ) );

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$error = sprintf(
			/* translators: the HTTP response code */
				__( 'WP connect server returned an HTTP error, code: %s', 'air-wp-sync-pro-plus' ),
				wp_remote_retrieve_response_code( $response )
			);
		}

		if ( isset( $error ) ) {
			return new WP_Error( 'license_deactivation_api_error', $error );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		return $license_data->license;
	}

	/**
	 * Check license status information
	 *
	 * @param string $license License key.
	 *
	 * @return  array|WP_Error
	 */
	public function check_license( $license = '' ) {
		$args = array(
			'timeout' => 25,
			'body'    => array(
				'edd_action'  => 'check_license',
				'license'     => $license,
				'item_id'     => $this->licensing_data->get_wpc_product_id(),
				'url'         => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
				'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			),
		);

		$response = wp_remote_get( $this->licensing_data->get_wpc_url(), apply_filters( 'airwpsync/licensing/request_args', $args ) );

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$error = sprintf(
			/* translators: the HTTP response code */
				__( 'WP connect server returned an HTTP error, code: %s', 'air-wp-sync-pro-plus' ),
				wp_remote_retrieve_response_code( $response )
			);
		}

		if ( isset( $error ) ) {
			return new WP_Error( 'license_check_api_error', $error );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		return array(
			'status'  => $license_data->license,
			'expires' => $license_data->expires ?? null,
		);
	}

	/**
	 * Check license status information
	 *
	 * @see https://easydigitaldownloads.com/docs/software-licensing-api/#check_license
	 *
	 * @param string $license_status License status.
	 * @param string $expires Expiry date (e.g. "2020-06-30 23:59:59").
	 *
	 * @return  string
	 */
	public function get_license_error_message( $license_status = '', $expires = '' ) {
		switch ( $license_status ) {
			case 'expired':
				$error = sprintf(
					/* translators: %1$s: license key expiration date, %2$s: WPconnect account page URL, %3$s: WPconnect pricing page URL  */
					__( 'Your license expired on %1$s. Please <a href="%2$s" target="_blank" rel="noopener noreferer">renew it on your WP connect account</a> to continue receiving updates and support. If you don’t have a license key, please see <a href="%3$s" target="_blank" rel="noopener noreferer">details & pricing</a>.', 'air-wp-sync-pro-plus' ),
					// phpcs:ignore: WordPress.DateTime.CurrentTimeTimestamp.Requested
					date_i18n( get_option( 'date_format' ), strtotime( $expires, current_time( 'timestamp' ) ) ),
					'https://wpconnect.co/my-account',
					$this->licensing_data->get_wpc_product_page_url() . '#pricing'
				);
				break;
			case 'disabled':
			case 'revoked':
				$error = sprintf(
					/* translators:  %1$s: WPconnect account page URL, %2$s: WPconnect pricing page URL  */
					__( 'Your license is currently disabled. Please <a href="%1$s" target="_blank" rel="noopener noreferer">renew or manage it in your WP connect account</a> to restore access to updates and support. If you don’t have a license key, please see <a href="%2$s" target="_blank" rel="noopener noreferer">details & pricing</a>.', 'air-wp-sync-pro-plus' ),
					'https://wpconnect.co/my-account',
					$this->licensing_data->get_wpc_product_page_url() . '#pricing'
				);
				break;
			case 'invalid':
			case 'invalid_item_id':
				$error = sprintf(
					/* translators:  %1$s: WPconnect account page URL, %3$s: WPconnect pricing page URL  */
					__( 'Your license is invalid. Please <a href="%1$s" target="_blank" rel="noopener noreferer">check your license key on your WP connect account</a> to continue receiving updates and support. If you don’t have a license key, please see <a href="%2$s" target="_blank" rel="noopener noreferer">details & pricing</a>.', 'air-wp-sync-pro-plus' ),
					'https://wpconnect.co/my-account',
					$this->licensing_data->get_wpc_product_page_url() . '#pricing'
				);
				break;
			case 'missing':
			case 'inactive':
			case 'site_inactive':
				$error = sprintf(
					/* translators:  %1$s: plugin’s settings page URL, %2$s: WPconnect account page URL, %3$s: WPconnect pricing page URL  */
					__( 'Your license is not activated. To enable updates and support, please enter your license key on the <a href="%1$s" target="_blank" rel="noopener noreferer">settings page</a>. You can find your license in your <a href="%2$s" target="_blank" rel="noopener noreferer">WP connect account</a> to restore access to updates and support. If you don’t have a license key, please see <a href="%3$s" target="_blank" rel="noopener noreferer">details & pricing</a>.', 'air-wp-sync-pro-plus' ),
					admin_url( 'edit.php?post_type=airwpsync-connection&page=air-wp-sync-pro-plus-settings' ),
					'https://wpconnect.co/my-account',
					$this->licensing_data->get_wpc_product_page_url() . '#pricing'
				);
				break;
			case 'no_activations_left':
				$error = sprintf(
					/* translators:  %1$s: WPconnect account page URL  */
					__( 'Your license key has reached its activation limit. Please <a href="%1$s" target="_blank" rel="noopener noreferer">check or upgrade your license on your WP connect account</a>.', 'air-wp-sync-pro-plus' ),
					'https://wpconnect.co/my-account'
				);
				break;
			default:
				$error = __( 'An error occurred, please try again.', 'air-wp-sync-pro-plus' );
				break;
		}
		return $error;
	}
}
