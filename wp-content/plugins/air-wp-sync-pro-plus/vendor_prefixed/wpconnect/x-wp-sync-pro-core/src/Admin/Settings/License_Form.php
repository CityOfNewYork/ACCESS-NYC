<?php
/**
 * Manages license form on the admin settings page.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\EDD\Licensing;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;

/**
 * License_Form class
 */
class License_Form {

	/**
	 * Plugin settings
	 *
	 * @var Options_Settings
	 */
	protected $options;

	/**
	 * Licensing
	 *
	 * @var Licensing
	 */
	protected $licensing;

	/**
	 * Plugin’s context.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Constructor
	 *
	 * @param Options_Settings $options Plugin settings.
	 * @param Licensing        $licensing Licensing.
	 * @param Plugin_Context   $plugin_context Plugin’s context.
	 */
	public function __construct( $options, $licensing, $plugin_context ) {
		$this->options        = $options;
		$this->licensing      = $licensing;
		$this->plugin_context = $plugin_context;

		add_action( 'admin_init', array( $this, 'check_license' ) );
		add_filter( 'airwpsync/admin/should_display_settings_page', '__return_true' );
		add_action( 'airwpsync/admin/pre-render-settings', array( $this, 'manage_license' ) );
		add_action( 'airwpsync/admin/render-settings-form', array( $this, 'display_license_form' ) );
		add_action( 'airwpsync/license_status_updated', array( $this, 'clear_license_cache' ), 10, 0 );

		add_action( 'after_plugin_row_meta', array( $this, 'after_plugin_row_meta' ), 10, 1 );
	}

	/**
	 * Checks and updates the license information.
	 *
	 * @return void
	 */
	public function check_license() {
		$last_check = $this->options->get( 'license_last_check' ) ?? '';
		if ( empty( $last_check ) || $last_check < strtotime( '-1 day' ) ) {
			$license_key = $this->options->get( 'license_key' );
			if ( $license_key ) {
				$license = $this->licensing->check_license( $license_key );
				if ( ! is_wp_error( $license ) ) {
					$this->options->set( 'license_status', $license['status'] );
					$this->options->set( 'license_expires', $license['expires'] ?? '' );
				}
				$this->options->set( 'license_last_check', time() );
				$this->options->save();
			}
		}
	}

	/**
	 * Manage license activation / deactivation and license settings update.
	 *
	 * @return void
	 */
	public function manage_license() {
		$this->maybe_activate_license();
		$this->maybe_deactivate_license();
		$this->maybe_update_settings();
	}

	/**
	 * Render license form.
	 */
	public function display_license_form() {
		$license_key     = $this->options->get( 'license_key' );
		$license_status  = $this->options->get( 'license_status' );
		$license_expires = $this->options->get( 'license_expires' );
		$error_message   = $license_status ? $this->licensing->get_license_error_message( $license_status, $license_expires ) : '';
		?><tr valign="top">
			<th scope="row">
				<label for="license_key">
					<span><?php esc_html_e( 'License Key', 'air-wp-sync-pro-plus' ); ?></span>
					<span class="airwpsync-tooltip" aria-label="<?php echo esc_attr__( 'Get your License Key from your <a href="https://wpconnect.co/my-account/" target="_blank">WP connect account</a>', 'air-wp-sync-pro-plus' ); ?>">?</span>
				</label>
			</th>
			<td>
				<div>
					<input class="regular-text ltr"
							type="text"
							name="license_key"
							value="<?php echo esc_attr( $license_key ); ?>" />
					<?php if ( 'valid' === $license_status ) : ?>
						<button name="air-wp-sync-pro-plus-license-deactivate" class="button airwpsync-button-delete"><?php esc_html_e( 'De-activate', 'air-wp-sync-pro-plus' ); ?></button>
						<p class="description airwpsync-valid"><?php esc_html_e( 'Your license is valid and activated.', 'air-wp-sync-pro-plus' ); ?></p>
					<?php else : ?>
						<button name="air-wp-sync-pro-plus-license-activate" class="button airwpsync-button-success"><?php esc_html_e( 'Activate', 'air-wp-sync-pro-plus' ); ?></button>
					<?php endif; ?>
				</div>
				<?php if ( 'valid' !== $license_status ) : ?>
					<p class="description airwpsync-error"><?php echo wp_kses_post( $error_message ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Activate License
	 */
	protected function maybe_activate_license() {
		if ( ! isset( $_POST['air-wp-sync-pro-plus-license-activate'] ) || ! check_admin_referer( 'air-wp-sync-pro-plus-settings-form' ) ) {
			return;
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$result      = $this->licensing->activate( $license_key );

		if ( is_wp_error( $result ) ) {
			$this->display_message( $result->get_error_message(), 'error' );
		} else {
			$this->options->set( 'license_status', $result );
			$this->options->set( 'license_key', $license_key );
			$this->options->save();
			$this->display_message( __( 'License successfully activated!', 'air-wp-sync-pro-plus' ), 'success' );
		}

		do_action( 'airwpsync/license_status_updated', $license_key, $result );
	}

	/**
	 * Deactivate License
	 */
	protected function maybe_deactivate_license() {
		if ( ! isset( $_POST['air-wp-sync-pro-plus-license-deactivate'] ) || ! check_admin_referer( 'air-wp-sync-pro-plus-settings-form' ) ) {
			return;
		}

		$license_key = $this->options->get( 'license_key' );
		$result      = $this->licensing->deactivate( $license_key );

		if ( is_wp_error( $result ) ) {
			$this->display_message( $result->get_error_message(), 'error' );
		} else {
			$this->options->delete( 'license_status' );
			$this->options->save();
			$this->display_message( __( 'License successfully deactivated.', 'air-wp-sync-pro-plus' ), 'success' );
		}
		do_action( 'airwpsync/license_status_updated', $license_key, $result );
	}

	/**
	 * Update settings based on form submission
	 */
	protected function maybe_update_settings() {
		if ( ! isset( $_POST['air-wp-sync-pro-plus-settings-update'] ) || ! check_admin_referer( 'air-wp-sync-pro-plus-settings-form' ) ) {
			return;
		}
		if ( ! isset( $_POST['air-wp-sync-pro-plus-settings-update'] ) ) {
			return;
		}
		$old_license_key = $this->options->get( 'license_key' );
		$new_license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		if ( $old_license_key !== $new_license_key ) {
			$this->options->set( 'license_key', $new_license_key );
			$this->options->delete( 'license_status' );
			do_action( 'airwpsync/license_status_updated', $new_license_key, null );
		}

		// Save options.
		$this->options->save();
		add_filter( 'airwpsync/admin/settings-saved', '__return_true' );
	}

	/**
	 * Display a WP notice
	 *
	 * @param string $message Message.
	 * @param string $type Message type (@see https://developer.wordpress.org/reference/hooks/admin_notices/#example).
	 */
	protected function display_message( $message, $type = 'info' ) {
		echo '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Clears the license check information to force a license re-check.
	 */
	public function clear_license_cache() {
		$this->options->delete( 'license_last_check' );
	}

	/**
	 * Displays license information if license is no longer valid.
	 *
	 * @param string $plugin_file Refer to {@see 'plugin_row_meta'} filter.
	 */
	public function after_plugin_row_meta( $plugin_file ) {
		if ( $this->plugin_context->get_plugin_basename() === $plugin_file ) {
			$license_key     = $this->options->get( 'license_key' );
			$license_status  = $this->options->get( 'license_status' ) ?? false;
			$license_expires = $this->options->get( 'license_expires' ) ?? '';
			$error_message   = $license_status ? $this->licensing->get_license_error_message( $license_status, $license_expires ) : '';
			if ( false === $license_status ) {
				$error_message = $this->licensing->get_license_error_message( 'inactive' );
				printf( '<div class="notice notice-error notice-alt inline">%s</div>', wp_kses_post( $error_message ) );
			} elseif ( $license_key && 'valid' !== $license_status && ! empty( $error_message ) ) {
				printf( '<div class="notice notice-error notice-alt inline">%s</div>', wp_kses_post( $error_message ) );
			}
		}
	}
}
