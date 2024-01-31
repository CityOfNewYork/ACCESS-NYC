<?php

namespace WPML\ST\Gettext;

use wpdb;
use WPML\FP\Obj;
use WPML\ST\MO\Hooks\PreloadThemeMoFile;
use WPML\ST\Package\Domains;
use function wpml_collect;
use WPML_ST_Settings;

class AutoRegisterSettings {

	const KEY_EXCLUDED_DOMAINS   = 'wpml_st_auto_reg_excluded_contexts';
	const KEY_ENABLED            = 'auto_register_enabled';
	const RESET_AUTOLOAD_TIMEOUT = 2 * HOUR_IN_SECONDS;

	/**
	 * @var wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @var WPML_ST_Settings
	 */
	private $settings;

	/**
	 * @var Domains
	 */
	private $package_domains;

	/** @var \WPML_Localization  */
	private $localization;

	/**
	 * @var array
	 */
	private $excluded_domains;

	public function __construct(
		wpdb $wpdb,
		WPML_ST_Settings $settings,
		Domains $package_domains,
		\WPML_Localization $localization
	) {
		$this->wpdb            = $wpdb;
		$this->settings        = $settings;
		$this->package_domains = $package_domains;
		$this->localization    = $localization;
	}

	/** @return bool */
	public function isEnabled() {
		$setting = $this->getSetting( self::KEY_ENABLED, [ 'enabled' => false ] );

		if ( $setting['enabled'] ) {
			$elapsed_time       = time() - $setting['time'];
			$isStillEnabled = self::RESET_AUTOLOAD_TIMEOUT > $elapsed_time;
			$setting['enabled'] = $isStillEnabled;
			if ( ! $isStillEnabled ) {
				$this->setEnabled( false );
			}
		}

		return $setting['enabled'];
	}

	/**
	 * @param bool $isEnabled
	 */
	public function setEnabled( $isEnabled ) {
		$setting = $this->getSetting( self::KEY_ENABLED, [ 'enabled' => false ] );

		$setting['enabled'] = ( $isEnabled && $this->getTimeToAutoDisable() > 0 );

		$this->settings->update_setting( self::KEY_ENABLED, $setting, true );

		$this->setWpmlSettingForThemeLocalization( $isEnabled );
	}

	/**
	 * @return int number of seconds before auto-disable
	 */
	public function getTimeToAutoDisable() {
		$setting = $this->getSetting( self::KEY_ENABLED, [ 'enabled' => false ] );

		if ( isset( $setting['time'] ) ) {
			$elapsed_time         = time() - $setting['time'];
			$time_to_auto_disable = self::RESET_AUTOLOAD_TIMEOUT - $elapsed_time;

			if ( $time_to_auto_disable > 0 ) {
				return $time_to_auto_disable;
			}
		}

		return 0;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed|null
	 */
	private function getSetting( $key, $default = null ) {
		$setting = $this->settings->get_setting( $key );
		return null !== $setting ? $setting : $default;
	}

	/**
	 * @return array
	 */
	public function getExcludedDomains() {
		if ( ! $this->excluded_domains ) {
			$excluded               = $this->getSetting( self::KEY_EXCLUDED_DOMAINS, [] );
			$this->excluded_domains = wpml_collect( $excluded )
				->reject( [ $this, 'isAdminOrPackageDomain' ] )
				->toArray();
		}

		return $this->excluded_domains;
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	public function isExcludedDomain( $domain ) {
		return in_array( $domain, $this->getExcludedDomains(), true );
	}

	/**
	 * @return array
	 * @todo: Remove this method, looks like dead code.
	 */
	public function get_included_contexts() {
		return array_values( array_diff( $this->getAllDomains(), $this->getExcludedDomains() ) );
	}

	/**
	 * @return array
	 */
	public function getAllDomains() {
		$sql = "
			SELECT DISTINCT context
			FROM {$this->wpdb->prefix}icl_strings 
		";

		return wpml_collect( $this->wpdb->get_col( $sql ) )
			->reject( [ $this, 'isAdminOrPackageDomain' ] )
			->merge( wpml_collect( $this->getExcludedDomains() ) )
			->unique()
			->toArray();
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	public function isAdminOrPackageDomain( $domain ) {
		return 0 === strpos( $domain, \WPML_Admin_Texts::DOMAIN_NAME_PREFIX )
			   || $this->package_domains->isPackage( $domain );
	}

	/**
	 * @return array
	 */
	public function getDomainsAndTheirExcludeStatus() {
		$contexts = $this->getAllDomains();
		$excluded = $this->getExcludedDomains();

		$result = array();
		foreach ( $contexts as $context ) {
			$result[ $context ] = in_array( $context, $excluded );
		}

		return $result;
	}

	public function saveExcludedContexts() {
		$nonce    = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$is_valid = wp_verify_nonce( $nonce, 'wpml-st-cancel-button' );

		if ( $is_valid ) {
			$is_enabled        = [ 'enabled' => false ];
			$excluded_contexts = [];

			if ( isset( $_POST[ self::KEY_EXCLUDED_DOMAINS ] ) && is_array( $_POST[ self::KEY_EXCLUDED_DOMAINS ] ) ) {
				$excluded_contexts = array_map( 'stripslashes', $_POST[ self::KEY_EXCLUDED_DOMAINS ] );
			}

			$this->settings->update_setting( self::KEY_EXCLUDED_DOMAINS, $excluded_contexts, true );

			if ( isset( $_POST[ self::KEY_ENABLED ] ) && 'true' === $_POST[ self::KEY_ENABLED ] ) {
				$is_enabled = [
					'enabled' => true,
					'time'    => time(),
				];
			}

			$this->settings->update_setting( self::KEY_ENABLED, $is_enabled, true );
			$this->setWpmlSettingForThemeLocalization( $is_enabled[ 'enabled' ] );

			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Nonce value is invalid', 'wpml-string-translation' ) );
		}
	}

	/** @return string */
	public function getFeatureEnabledDescription() {
		return '<span class="icon otgs-ico-warning"></span> '
			. __( "Automatic string registration will remain active for %s. Please visit the site's front-end to allow WPML to find strings for translation.", 'wpml-string-translation' );
	}

	/** @return string */
	public function getFeatureDisabledDescription() {
		return __( '* This feature is only intended for sites that are in development. It will significantly slow down the site, but help you find strings that WPML cannot detect in the PHP code.', 'wpml-string-translation' );
	}

	public function getDomainsWithStringsTranslationData() {
		$excluded = $this->getExcludedDomains();
		$domains  = wpml_collect( $this->getAllDomains() )->merge( $excluded )->unique()->toArray();
		$stats    = $this->localization->get_domain_stats( $domains, 'default', false, true );

		$result = [];
		foreach ( $domains as $domain ) {
			$completed_strings_count  = (int) Obj::path( [ $domain, 'complete' ], $stats );
			$incomplete_strings_count = (int) Obj::path( [ $domain, 'incomplete' ], $stats );

			$result[ $domain ] = [
				'name'                     => $domain,
				'translated_strings_count' => $completed_strings_count,
				'total_strings_count'      => $incomplete_strings_count + $completed_strings_count,
				'is_blocked'               => in_array( $domain, $excluded ),
			];
		}

		return $result;
	}

	private function setWpmlSettingForThemeLocalization( $isEnabled ) {
		if ( $isEnabled ) {
			$this->enableWpmlSettingForThemeLocalizationIfNecessary();
		} else {
			$this->disableWpmlSettingForThemeLocalizationIfNecessary();
		}
	}

	private function enableWpmlSettingForThemeLocalizationIfNecessary() {
		$previousLoadThemeSetting = (int) apply_filters(
			'wpml_get_setting',
			0,
			PreloadThemeMoFile::SETTING_KEY
		);
		if ( $previousLoadThemeSetting === PreloadThemeMoFile::SETTING_DISABLED ) {
			do_action(
				'wpml_set_setting',
				PreloadThemeMoFile::SETTING_KEY,
				PreloadThemeMoFile::SETTING_ENABLED_FOR_LOAD_TEXT_DOMAIN,
				true
			);
		}
	}

	private function disableWpmlSettingForThemeLocalizationIfNecessary() {
		$previousLoadThemeSetting = (int) apply_filters(
			'wpml_get_setting',
			0,
			PreloadThemeMoFile::SETTING_KEY
		);
		if ( $previousLoadThemeSetting === PreloadThemeMoFile::SETTING_ENABLED_FOR_LOAD_TEXT_DOMAIN ) {
			do_action(
				'wpml_set_setting',
				PreloadThemeMoFile::SETTING_KEY,
				PreloadThemeMoFile::SETTING_DISABLED,
				true
			);
		}
	}
}
