<?php

namespace WPML\TM\ATE\API;

use WPML\TM\ATE\ClonedSites\SecondaryDomains;

class FingerprintGenerator {
	const SITE_FINGERPRINT_HEADER     = 'SITE-FINGERPRINT';
	const NEW_SITE_FINGERPRINT_HEADER = 'NEW-SITE-FINGERPRINT';

	/** @var SecondaryDomains */
	private $secondaryDomains;

	/**
	 * @param SecondaryDomains $secondaryDomains
	 */
	public function __construct( SecondaryDomains $secondaryDomains ) {
		$this->secondaryDomains = $secondaryDomains;
	}


	public function getSiteFingerprint() {
		$siteFingerprint = [
			'wp_url' => $this->getSiteUrl(),
		];

		return json_encode( $siteFingerprint );
	}

	protected function getSiteUrl() {

		$siteUrl = defined( 'ATE_CLONED_SITE_URL' )
			? ATE_CLONED_SITE_URL
			: $this->secondaryDomains->maybeFallBackToTheOriginalURL( site_url() );

		return $this->getDefaultSiteUrl( $siteUrl );
	}

	private function getDefaultSiteUrl( $siteUrl ) {
		global $sitepress;
		$filteredSiteUrl = false;
		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			/* @var WPML_URL_Converter $wpml_url_converter */
			global $wpml_url_converter;
			$site_url_default_lang = $wpml_url_converter->get_default_site_url();
			$filteredSiteUrl       = filter_var( $site_url_default_lang, FILTER_SANITIZE_URL );
		}

		$defaultSiteUrl = $filteredSiteUrl ? $filteredSiteUrl : $siteUrl;
		$defaultSiteUrl = defined( 'ATE_CLONED_DEFAULT_SITE_URL' ) ? ATE_CLONED_DEFAULT_SITE_URL : $defaultSiteUrl;

		return $defaultSiteUrl;
	}
}
