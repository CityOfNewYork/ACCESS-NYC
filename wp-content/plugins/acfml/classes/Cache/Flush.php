<?php

namespace ACFML\Cache;

use WPML\Element\API\Languages;
use WPML\FP\Obj;

class Flush implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const OBJECT_TYPES_TO_CACHE_KEYS = [
		'field_group'     => 'acf_get_field_group_posts:%s',
		'post_type'       => 'acf_get_post_type_posts:%s',
		'taxonomy'        => 'acf_get_taxonomy_posts:%s',
		'ui_options_page' => 'acf_get_ui_options_page_posts:%s',
	];

	const ACTIONS = [
		'acf/update_%s',
		'acf/delete_%s',
		'acf/trash_%s',
		'acf/untrash_%s',
	];

	const FLUSH_PRIORITY_AFTER_ACF_LOGIC = 99;

	/**
	 * @var string|null|false
	 */
	private $defaultLanguage;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->defaultLanguage = $sitepress->get_default_language();
	}

	public function add_hooks() {
		foreach ( self::ACTIONS as $actionPrefix ) {
			$this->registerFlushOnAction( $actionPrefix );
			$this->registerFieldFlushOnAction( $actionPrefix );
		}
	}

	/**
	 * @param string $actionPrefix
	 */
	private function registerFlushOnAction( $actionPrefix ) {
		foreach ( self::OBJECT_TYPES_TO_CACHE_KEYS as $objectType => $cacheKey ) {
			add_action(
				sprintf( $actionPrefix, $objectType ),
				function() use ( $cacheKey ) {
					$this->flushMultilingualCache( $cacheKey );
				},
				self::FLUSH_PRIORITY_AFTER_ACF_LOGIC
			);
		}
	}

	/**
	 * @param string $actionPrefix
	 */
	private function registerFieldFlushOnAction( $actionPrefix ) {
		if ( 'acf/update_%s' === $actionPrefix ) {
			// For fields, the 'acf/update_field' hook is a filter, not an action,
			// and contrary to other 'acf/update_%s' actions, it runs BEFORE the main cache cleanup.
			// Here, the 'acf/updated_field' action is fired in the right moment.
			$actionPrefix = 'acf/updated_%s';
		}
		add_action(
			sprintf( $actionPrefix, 'field' ),
			function( $field ) {
				$name   = Obj::prop( 'name', $field );
				$key    = Obj::prop( 'key', $field );
				$parent = Obj::prop( 'parent', $field );
				if ( $name ) {
					$this->flushMultilingualCache( "acf_get_field_post:name:{$name}:%s" );
				}
				if ( $key ) {
					$this->flushMultilingualCache( "acf_get_field_post:key:{$key}:%s" );
				}
				if ( $parent ) {
					$this->flushMultilingualCache( "acf_get_field_posts:{$parent}:%s" );
				}
			},
			self::FLUSH_PRIORITY_AFTER_ACF_LOGIC
		);
	}

	/**
	 * @param string $cacheKey
	 */
	private function flushMultilingualCache( $cacheKey ) {
		$activeLanguages = Languages::getActive();
		foreach ( $activeLanguages as $languageCode => $language ) {
			wp_cache_delete( sprintf( $cacheKey, $languageCode ), 'acf' );
		}
	}

}
