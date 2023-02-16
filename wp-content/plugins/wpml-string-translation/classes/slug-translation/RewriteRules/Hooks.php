<?php

namespace WPML\ST\SlugTranslation\Hooks;

class Hooks {
	/** @var \WPML_Rewrite_Rule_Filter_Factory */
	private $factory;

	/** @var \WPML_ST_Slug_Translation_Settings $slug_translation_settings */
	private $slug_translation_settings;

	/** @var array|null */
	private $cache;

	/**
	 * @param \WPML_Rewrite_Rule_Filter_Factory  $factory
	 * @param \WPML_ST_Slug_Translation_Settings $slug_translation_settings
	 */
	public function __construct(
		\WPML_Rewrite_Rule_Filter_Factory $factory,
		\WPML_ST_Slug_Translation_Settings $slug_translation_settings
	) {
		$this->factory                   = $factory;
		$this->slug_translation_settings = $slug_translation_settings;
	}


	public function add_hooks() {
		add_action( 'init', [ $this, 'init' ], \WPML_Slug_Translation_Factory::INIT_PRIORITY );
	}

	public function init() {
		if ( $this->slug_translation_settings->is_enabled() ) {
			add_filter( 'option_rewrite_rules', [ $this, 'filter' ], 1, 1 );
			add_filter( 'flush_rewrite_rules_hard', [ $this, 'flushRewriteRulesHard' ] );
			add_action( 'registered_post_type', [ $this, 'clearCache' ] );
			add_action( 'registered_taxonomy', [ $this, 'clearCache' ] );
		}
	}

	/**
	 * @param array $value
	 *
	 * @return array
	 */
	public function filter( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		if ( ! $this->cache ) {
			$this->cache = $this->factory->create()->rewrite_rules_filter( $value );
		}

		return $this->cache;
	}

	public function clearCache() {
		$this->cache = null;
	}

	/**
	 * @param bool $hard
	 *
	 * @return mixed
	 */
	public function flushRewriteRulesHard( $hard ) {
		$this->clearCache();

		return $hard;
	}
}
