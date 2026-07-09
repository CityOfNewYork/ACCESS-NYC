<?php

namespace WPML\ST\StringsScanning\JS;

use WPML\ST\Rest\Base;
use WPML\ST\WP\App\Resources;
use WPML\StringTranslation\UserInterface\RestApi\StringSettingsApiController;
use WPML\UIPage;

class SettingsHooks implements \IWPML_Action {

	const SECTION_ID = 'ml-content-setup-string-translation';

	const PRIORITY_AFTER_MEDIA_SETTINGS = 20;

	/** @var bool $isDetectionEnabled */
	private $isDetectionEnabled;

	public function __construct( bool $isDetectionEnabled ) {
		$this->isDetectionEnabled = $isDetectionEnabled;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAppScript' ] );
		add_action( 'icl_tm_menu_mcsetup', [ $this, 'insertSettingSection' ], self::PRIORITY_AFTER_MEDIA_SETTINGS );
		add_filter( 'wpml_mcsetup_navigation_links', [ $this, 'insertMenuElement' ], self::PRIORITY_AFTER_MEDIA_SETTINGS );
		add_action( 'wpml_st_before_localization_ui_table', [ $this, 'showLocalizationUINotice' ] );
	}

	/**
	 * @param string $hookSuffix
	 *
	 * @return void
	 */
	public function enqueueAppScript( $hookSuffix ) {
		if ( 'wpml_page_tm/menu/settings' === $hookSuffix ) {
			$app = Resources::enqueueApp( 'wpml-st-settings' );
			$app( [
				'name' => 'wpmlSTSettings',
				'data' => [
					'restEndpoint' => trailingslashit( get_rest_url() ) . trailingslashit( Base::NAMESPACE ) . StringSettingsApiController::ROUTE,
					'nonce'        => wp_create_nonce( 'wp_rest' ),
				],
			] );
		}
	}

	public function insertSettingSection() {
		$rootStateClass = $this->isDetectionEnabled ? 'on' : 'off';

		?>
		<div class="wpml-section" id="<?php echo esc_attr( self::SECTION_ID ); ?>">
			<div class="wpml-section-header">
				<h3><?php echo $this->getSectionTitle() ?></h3>
			</div>

			<div class="wpml-section-content wpml-section-content-wide">
				<div class="wpml-settings-list">
					<div role="presentation">
						<ul class="settings-ul">
							<li aria-label="detect-js-strings" id="detect-js-strings" class="setting-item <?php echo esc_attr( $rootStateClass ); ?>">
								<div id="toggle-detect-js-string-spinner" style="display: none">
									<span class="detect-js-string-spinner"></span>
								</div>
								<div class="setting-item-title">
									<span class="setting-item-title-label">
										<?php esc_html_e( 'Detect strings in JavaScript files', 'wpml-string-translation' ); ?>
									</span>
									<span class="setting-item-title-sublabel">
										<?php
											echo sprintf(
												/* translators: Placeholders are for open and close link tag */
												esc_html__( 'When enabled, WPML tracks JavaScript files loaded on your site\'s pages. Texts (strings) from these files will be included when you scan a theme or plugin in %1$sWPML > Theme and plugins localization%2$s.', 'wpml-string-translation' ),
												'<a href="' . esc_url( admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/theme-localization.php' ) ) . '">',
												'</a>'
											);
										?>
									</span>
								</div>
								<label for="toggle-detect-js-string" class="wpml-on-off-switch gray-dark">
									<input id="toggle-detect-js-string" aria-labelledby="toggle-detect-js-string" type="checkbox" <?php if ( $this->isDetectionEnabled ): ?>checked="checked" <?php endif; ?>/>
									<span aria-hidden="false" class="on"><?php esc_html_e( 'ON', 'sitepress' ); ?></span>
									<span aria-hidden="true" class="off"><?php esc_html_e( 'OFF', 'sitepress' ); ?></span>
									<span class="visually-hidden"></span>
								</label>
							</li>
						</ul>

						<div id="" class="warning notice-warning otgs-notice wpml-settings-list-notice">
							<p><?php
								echo sprintf(
									/* translators: Placeholders are for open and close bold tag */
									esc_html__( '%1$sNote:%2$s This feature may affect site performance. We recommend disabling it after scanning is complete.', 'wpml-string-translation' ),
									'<b>',
									'</b>'
								);
								?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array $sections
	 *
	 * @return array
	 */
	public function insertMenuElement( array $sections ) {
		$sections[ self::SECTION_ID ] = $this->getSectionTitle();

		return $sections;
	}

	/**
	 * @return string
	 */
	private function getSectionTitle() {
		return esc_html__( 'String Translation', 'wpml-string-translation' );
	}

	public function showLocalizationUINotice() {
		$urlToSettings = self::getSettingsURL();

		if ( $this->isDetectionEnabled ) {
			$text = sprintf(
				/* translators: Placeholders are for open and close link tag */
				esc_html__( 'JavaScript file scanning is active and may affect performance. %1$sDisable this feature%2$s in WPML Settings once your texts (strings) are registered.', 'wpml-string-translation' ),
				'<a href="'. $urlToSettings . '">',
				'</a>'
			);
		} else {
			$text = sprintf(
				/* translators: Placeholders are for open and close link tag */
				esc_html__( 'Missing texts (strings) after scanning? %1$sEnable Scan strings in JavaScript files%2$s in WPML Settings, visit the page containing the string, then scan again.', 'wpml-string-translation' ),
				'<a href="'. $urlToSettings . '">',
				'</a>'
			);
		}

		?>
		<div class="warning-content-wrap">
			<div class="warning-content">
				<p><?php echo $text; ?></p>
			</div>
		</div>
		<?php
	}

	public static function getSettingsURL(): string {
		return UIPage::getSettings() . '#' . self::SECTION_ID;
	}
}
