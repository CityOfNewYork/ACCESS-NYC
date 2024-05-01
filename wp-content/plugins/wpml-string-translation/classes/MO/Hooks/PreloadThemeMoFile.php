<?php

namespace WPML\ST\MO\Hooks;

use WPML\Collect\Support\Collection;
use WPML\ST\Gettext\AutoRegisterSettings;
use function WPML\Container\make;

class PreloadThemeMoFile implements \IWPML_Action {

	const SETTING_KEY = 'theme_localization_load_textdomain';
	const SETTING_DISABLED = 0;
	const SETTING_ENABLED = 1;
	const SETTING_ENABLED_FOR_LOAD_TEXT_DOMAIN = 2;

	/** @var \SitePress */
	private $sitepress;

	/** @var \wpdb */
	private $wpdb;

	public function __construct( \SitePress $sitepress, \wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}


	public function add_hooks() {
		$domainsSetting = $this->sitepress->get_setting( 'gettext_theme_domain_name' );
		$domains = empty( $domainsSetting ) ? [] : explode( ',', $domainsSetting );
		$domains = \wpml_collect( array_map( 'trim', $domains ) );

		$loadTextDomainSetting = (int) $this->sitepress->get_setting( static::SETTING_KEY );
		$isEnabled = $loadTextDomainSetting === static::SETTING_ENABLED;

		if ( $loadTextDomainSetting === static::SETTING_ENABLED_FOR_LOAD_TEXT_DOMAIN ) {
			/** @var AutoRegisterSettings $autoStrings */
			$autoStrings = make( AutoRegisterSettings::class );
			$isEnabled = $autoStrings->isEnabled();
		}

		if ( $isEnabled && $domains->count() ) {
			$this->getMOFilesByDomainsAndLocale( $domains, get_locale() )->map( function ( $fileResult ) {
				load_textdomain( $fileResult->domain, $fileResult->file_path );
			} );
		}
	}

	/**
	 * @param Collection<string> $domains
	 * @param string $locale
	 *
	 * @return Collection
	 */
	private function getMOFilesByDomainsAndLocale( $domains, $locale ) {
		$domainsClause   = wpml_prepare_in( $domains->toArray(), '%s' );
		$sql = "
			SELECT file_path, domain
			FROM {$this->wpdb->prefix}icl_mo_files_domains
			WHERE domain IN ({$domainsClause}) AND file_path REGEXP %s
		";

		/** @var string $sql */
		$sql = $this->wpdb->prepare(
			$sql,
			'((\\/|-)' . $locale . '(\\.|-))+'
		);

		return \wpml_collect( $this->wpdb->get_results( $sql ) );
	}
}