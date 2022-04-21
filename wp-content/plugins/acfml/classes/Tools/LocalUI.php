<?php

namespace ACFML\Tools;

use WPML\FP\Obj;

class LocalUI {

	/**
	 * @var string name
	 */
	public $name = 'acfml-local-settings';

	/**
	 * Title of the ACF/ACFML Tool page.
	 *
	 * @var string title
	 */
	public $title = '';

	public function __construct() {
		$this->title = __( 'Sync Translation Preferences for Local Fields', 'acfml' );
	}

	public function initialize() {
	}

	public function load() {
	}

	public function html() {
		?>
		<p>
		<?php
		esc_html_e(
			'If this option is checked, ACFML will scan your field groups stored in PHP files and the ACF-JSON directory and sync any changes to translation preferences. This can harm the site performance if you have a significant number of fields stored this way.',
			'acfml'
		);
		?>
		</p>
		<div class="acf-fields">
			<div class="acf-field">
				<ul class="acf-checkbox acf-bl">
					<li>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( LocalSettings::SCAN_LOCAL_FILES ); ?>" value="1" <?php checked( LocalSettings::isScanModeEnabled() ); ?> />
							<?php esc_html_e( 'Scan local fields for changes to translation preferences.', 'acfml' ); ?>
						</label>
					</li>
				</ul>
				<?php wp_nonce_field( 'nonce_' . LocalSettings::SCAN_LOCAL_FILES, 'nonce_' . LocalSettings::SCAN_LOCAL_FILES ); ?>
			</div>
		</div>

		<p class="acf-submit">
			<input type="submit" name="submit-scan-mode" class="button button-primary" value="<?php esc_attr_e( 'Save', 'acfml' ); ?>" />
		</p>
		<?php
	}

	private static function getNonceName() {
		return 'nonce_' . LocalSettings::SCAN_LOCAL_FILES;
	}

	public function submit() {
		// phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$isSavingScanMode = isset( $_POST['submit-scan-mode'] );
		// phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$nonceValue = sanitize_key( Obj::prop( self::getNonceName(), $_POST ) );

		if ( $isSavingScanMode && wp_verify_nonce( $nonceValue, self::getNonceName() ) ) {
			// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
			LocalSettings::enableScanMode( (bool) Obj::prop( LocalSettings::SCAN_LOCAL_FILES, $_POST ) );
			acf_add_admin_notice( __( 'Translation preferences scanning options saved.', 'acfml' ), 'success' );
		}
	}
}
