<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\Gettext;

use SitePress;

class Settings {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var AutoRegisterSettings $auto_register_settings */
	private $auto_register_settings;

	public function __construct(
		SitePress $sitepress,
		AutoRegisterSettings $auto_register_settings
	) {
		$this->sitepress              = $sitepress;
		$this->auto_register_settings = $auto_register_settings;
	}

	/** @return bool */
	public function isTrackStringsEnabled() {
		return (bool) $this->getSTSetting( 'track_strings', false );
	}

	/** @return string */
	public function getTrackStringColor() {
		return (string) $this->getSTSetting( 'hl_color', '' );
	}

	/** @return bool */
	public function isAutoRegistrationEnabled() {
		return (bool) $this->auto_register_settings->isEnabled();
	}

	/**
	 * @param string|array $domain
	 *
	 * @return bool
	 */
	public function isDomainRegistrationExcluded( $domain ) {
		if ( is_array( $domain ) && array_key_exists( 'domain', $domain ) ) {
			$domain = $domain[ 'domain' ];
		}
		return (bool) $this->auto_register_settings->isExcludedDomain( $domain );
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed|null
	 */
	private function getSTSetting( $key, $default = null ) {
		$settings = $this->sitepress->get_setting( 'st' );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
}
