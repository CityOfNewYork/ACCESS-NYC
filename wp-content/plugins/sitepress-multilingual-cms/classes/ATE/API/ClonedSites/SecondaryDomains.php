<?php

namespace WPML\TM\ATE\ClonedSites;

use WPML\LIB\WP\Option;
use WPML\TM\ATE\API\FingerprintGenerator;

/**
 * One physical site can have multiple domains.
 * In such situation, we have to take a note about it in order to use a proper domain while communicating with AMS/ATE.
 *
 * It is a different case than when a user decides to copy or move a site to another domain.
 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-2026
 */
class SecondaryDomains {
	const OPTION = 'wpml_tm_ate_secondary_domains';
	const ORIGINAL_SITE_URL = 'wpml_tm_ate_original_site_url';

	/**
	 * @param string $domain
	 * @param string $originalSiteUrl
	 *
	 * @return string[]
	 */
	public function add( $domain, $originalSiteUrl ) {
		$domains = $this->get();
		if ( ! in_array( $domain, $domains, true ) ) {
			$domains[] = $domain;
		}

		Option::update( self::OPTION, $domains );
		Option::update( self::ORIGINAL_SITE_URL, $originalSiteUrl );

		return $domains;
	}

	/**
	 * The purpose of the method is to fall back to the original site URL
	 *  in the case when the current site URL is a secondary domain of the same site,
	 *
	 * 1. If the current site URL is the same as the original site URL,
	 *      then we can use the current site URL.
	 * 2. If the current site URL is different from the original site URL and is registered as a secondary domain,
	 *      then we can use the current site URL.
	 * 3. If the current site URL is different from the original site URL and is not registered as a secondary domain,
	 *      then we return the current site url which eventually will cause ATE error with code 421.
	 *
	 * @return string
	 */
	public function maybeFallBackToTheOriginalURL( $currentSiteUrl ) {
		$originalSiteUrl = Option::get( self::ORIGINAL_SITE_URL );

		if ( $currentSiteUrl === $originalSiteUrl ) {
			return $currentSiteUrl;
		}

		if ( $this->isRegistered( $currentSiteUrl ) ) {
			return $originalSiteUrl; // the fallback to the original site URL
		}

		return $currentSiteUrl;
	}

	/**
	 * @return array{originalSiteUrl: string, aliasDomains: string[]}|null
	 */
	public function getInfo() {
		$domains = $this->get();

		if ( ! $domains ) {
			return null;
		}

		return [
			'originalSiteUrl' => Option::get( self::ORIGINAL_SITE_URL ),
			'aliasDomains'    => $domains,
		];
	}

	/**
	 * @return string[]
	 */
	private function get() {
		return Option::getOr( self::OPTION, [] );
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isRegistered( $domain ) {
		return in_array( $domain, $this->get(), true );
	}
}