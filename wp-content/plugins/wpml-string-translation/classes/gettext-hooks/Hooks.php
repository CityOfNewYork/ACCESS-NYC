<?php
/**
 * WPML\ST\Gettext\Hooks class file.
 *
 * @package WPML\ST
 */

namespace WPML\ST\Gettext;

use SitePress;
use function WPML\Container\make;
use WPML\ST\Gettext\Filters\IFilter;
use WPML\ST\StringsFilter\Provider;

/**
 * Class WPML\ST\Gettext\Hooks
 */
class Hooks implements \IWPML_Action {

	/** @var Filters\IFilter[] $filters */
	private $filters = [];

	/**
	 * @var SitePress SitePress
	 */
	private $sitepress;

	/**
	 * @var array
	 */
	private $string_cache = [];

	/**
	 * @var string|null
	 */
	private $lang;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Init hooks.
	 */
	public function add_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init_gettext_hooks' ), 2 );
	}

	public function addFilter( IFilter $filter ) {
		$this->filters[] = $filter;
	}

	public function clearFilters() {
		$this->filters = [];
	}

	/**
	 * @param string $lang
	 */
	public function switch_language_hook( $lang ) {
		$this->lang = $lang;
	}

	/**
	 * @throws \WPML\Auryn\InjectionException
	 * @deprecated since WPML ST 3.0.0
	 */
	public function clear_filters() {
		// @todo: This is used in WCML tests, we will have to adjust there accordingly.
		/** @var Provider $filter_provider */
		$filter_provider = make( Provider::class );
		$filter_provider->clearFilters();
	}

	/**
	 * Init gettext hooks.
	 */
	public function init_gettext_hooks() {
		add_filter( 'gettext', [ $this, 'gettext_filter' ], 9, 3 );
		add_filter( 'gettext_with_context', [ $this, 'gettext_with_context_filter' ], 1, 4 );
		add_filter( 'ngettext', [ $this, 'ngettext_filter' ], 9, 5 );
		add_filter( 'ngettext_with_context', [ $this, 'ngettext_with_context_filter' ], 9, 6 );
		add_action( 'wpml_language_has_switched', [ $this, 'switch_language_hook' ] );
	}

	/**
	 * @param string       $translation
	 * @param string       $text
	 * @param string|array $domain
	 * @param string|false $name Deprecated since WPML ST 3.0.0 (the name should be automatically created as a hash)
	 *
	 * @return string
	 */
	public function gettext_filter( $translation, $text, $domain, $name = false ) {
		if ( is_array( $domain ) ) {
			$domain_key = implode( '', $domain );
		} else {
			$domain_key = $domain;
		}

		if ( ! $name ) {
			$name = md5( $text );
		}

		if ( ! $this->lang ) {
			$this->lang = $this->sitepress->get_current_language();
		}

		$key = $translation . $text . $domain_key . $name . $this->lang;

		if ( isset( $this->string_cache[ $key ] ) ) {
			return $this->string_cache[ $key ];
		}

		foreach ( $this->filters as $filter ) {
			$translation = $filter->filter( $translation, $text, $domain, $name );
		}

		$this->string_cache[ $key ] = $translation;

		return $translation;
	}

	/**
	 * @param string       $translation
	 * @param string       $text
	 * @param string|false $context
	 * @param string       $domain
	 *
	 * @return string
	 */
	public function gettext_with_context_filter( $translation, $text, $context, $domain ) {
		if ( $context ) {
			$domain = [
				'domain'  => $domain,
				'context' => $context,
			];
		}

		return $this->gettext_filter( $translation, $text, $domain );
	}

	/**
	 * @param string       $translation
	 * @param string       $single
	 * @param string       $plural
	 * @param string       $number
	 * @param string       $domain
	 * @param string|false $context
	 *
	 * @return string
	 */
	public function ngettext_filter( $translation, $single, $plural, $number, $domain, $context = false ) {
		if ( $number == 1 ) {
			$string_to_translate = $single;
		} else {
			$string_to_translate = $plural;
		}

		return $this->gettext_with_context_filter( $translation, $string_to_translate, $context, $domain );
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
	 */
	public function ngettext_with_context_filter( $translation, $single, $plural, $number, $context, $domain ) {
		return $this->ngettext_filter( $translation, $single, $plural, $number, $domain, $context );
	}
}
