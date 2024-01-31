<?php

namespace WPML\ST\MO\Hooks;


use WPML\ST\Gettext\Settings;

class DetectPrematurelyTranslatedStrings implements \IWPML_Action {
	/** @var string[] */
	private $domains = [];

	/** @var string[] */
	private $preloadedDomains = [];

	/** @var \SitePress */
	private $sitepress;

	/** @var Settings */
	private $gettextHooksSettings;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress, Settings $settings ) {
		$this->sitepress            = $sitepress;
		$this->gettextHooksSettings = $settings;
	}

	/**
	 * Init gettext hooks.
	 */
	public function add_hooks() {
		if ( $this->gettextHooksSettings->isAutoRegistrationEnabled() ) {
			$domains                = $this->sitepress->get_setting( 'gettext_theme_domain_name' );
			$this->preloadedDomains = array_filter( array_map( 'trim', explode( ',', $domains ) ) );

			add_filter( 'gettext', [ $this, 'gettext_filter' ], 9, 3 );
			add_filter( 'gettext_with_context', [ $this, 'gettext_with_context_filter' ], 1, 4 );
			add_filter( 'ngettext', [ $this, 'ngettext_filter' ], 9, 5 );
			add_filter( 'ngettext_with_context', [ $this, 'ngettext_with_context_filter' ], 9, 6 );

			add_filter( 'override_load_textdomain', [ $this, 'registerDomainToPreloading' ], 10, 2 );
		}
	}

	/**
	 * @param string       $translation
	 * @param string       $text
	 * @param string|array $domain
	 *
	 * @return string
	 */
	public function gettext_filter( $translation, $text, $domain ) {
		$this->registerDomain( $domain );

		return $translation;
	}

	/**
	 * @param string $translation
	 * @param string $text
	 * @param string $context
	 * @param string $domain
	 *
	 * @return string
	 */
	public function gettext_with_context_filter( $translation, $text, $context, $domain ) {
		$this->registerDomain( $domain );

		return $translation;
	}

	/**
	 * @param string       $translation
	 * @param string       $single
	 * @param string       $plural
	 * @param string       $number
	 * @param string|array $domain
	 *
	 * @return string
	 */
	public function ngettext_filter( $translation, $single, $plural, $number, $domain ) {
		$this->registerDomain( $domain );

		return $translation;
	}

	/**
	 * @param string $translation
	 * @param string $single
	 * @param string $plural
	 * @param string $number
	 * @param string $context
	 * @param string $domain
	 *
	 * @return string
	 *
	 */
	public function ngettext_with_context_filter( $translation, $single, $plural, $number, $context, $domain ) {
		$this->registerDomain( $domain );

		return $translation;
	}

	private function registerDomain( $domain ) {
		if ( ! in_array( $domain, $this->preloadedDomains ) ) {
			$this->domains[ $domain ] = true;
		}
	}

	public function registerDomainToPreloading( $plugin_override, $domain ) {
		if ( array_key_exists( $domain, $this->domains ) && ! in_array( $domain, $this->preloadedDomains, true ) ) {
			$this->preloadedDomains[] = $domain;

			$this->sitepress->set_setting(
				'gettext_theme_domain_name',
				implode( ',', array_unique( $this->preloadedDomains ) )
			);
			$this->sitepress->save_settings();
		}


		return $plugin_override;
	}
}