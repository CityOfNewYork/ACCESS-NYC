<?php

namespace WPML\ST\MO\JustInTime;

use NOOP_Translations;
use WPML\ST\MO\LoadedMODictionary;

class MO extends \MO {

	/** @var LoadedMODictionary $loaded_mo_dictionary */
	private $loaded_mo_dictionary;

	/** @var string $locale */
	protected $locale;

	/** @var string $domain */
	private $domain;

	/** @var bool $isLoading */
	private $isLoading = false;

	/**
	 * @param LoadedMODictionary $loaded_mo_dictionary
	 * @param string             $locale
	 * @param string             $domain
	 */
	public function __construct(
		LoadedMODictionary $loaded_mo_dictionary,
		$locale,
		$domain
	) {
		$this->loaded_mo_dictionary = $loaded_mo_dictionary;
		$this->locale               = $locale;
		$this->domain               = $domain;
	}

	/**
	 * @param string $singular
	 * @param string $context
	 *
	 * @return string
	 */
	public function translate( $singular, $context = null ) {
		if ( $this->isLoading ) {
			return $singular;
		}

		$this->load();
		return _x( $singular, $context, $this->domain );
	}

	/**
	 * @param string $singular
	 * @param string $plural
	 * @param int    $count
	 * @param string $context
	 *
	 * @return string
	 */
	public function translate_plural( $singular, $plural, $count, $context = null ) {
		if ( $this->isLoading ) {
			return $count > 1 ? $plural : $singular;
		}

		$this->load();
		return _nx( $singular, $plural, $count, $context, $this->domain );
	}

	private function load() {
		$this->isLoading = true;
		$this->loadTextDomain();

		if ( ! $this->isLoaded() ) {
			/**
			 * If we could not load at least one MO file,
			 * we need to assign the domain with a `NOOP_Translations`
			 * object on the 'l10n' global.
			 * This will prevent recursive loop on the current object.
			 */
			$GLOBALS['l10n'][ $this->domain ] = new NOOP_Translations();
		}

		$this->isLoading = false;
	}

	protected function loadTextDomain() {
		$this->loaded_mo_dictionary
			->getFiles( $this->domain, $this->locale )
			->each( function( $mofile ) {
				load_textdomain( $this->domain, $mofile );
			} );
	}

	/**
	 * In some cases, themes or plugins are hooking on
	 * `override_load_textdomain` so that the function
	 * `load_textdomain` always returns `true` even
	 * if the domain is not set on the global `$l10n`.
	 *
	 * That's why we need to check on the global `$l10n`.
	 *
	 * @return bool
	 */
	private function isLoaded() {
		return isset( $GLOBALS['l10n'][ $this->domain ] )
		       && ! $GLOBALS['l10n'][ $this->domain ] instanceof self;
	}
}
