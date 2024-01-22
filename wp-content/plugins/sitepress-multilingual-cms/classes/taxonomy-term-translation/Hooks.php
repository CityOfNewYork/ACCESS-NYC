<?php

namespace WPML\TaxonomyTermTranslation;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Lst;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks as WpHooks;
use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const KEY_SKIP_FILTERS = 'wpml_skip_filters';

	/** @var \SitePress */
	private $sitepress;

	/** @var \wpdb */
	private $wpdb;

	/** @var array */
	private $post_term_taxonomy_ids_before_sync = [];


	/**
	 * WHooks constructor.
	 *
	 * @param \SitePress $sitepress
	 * @param \wpdb      $wpdb
	 */
	public function __construct( \SitePress $sitepress, \wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}

	public function add_hooks() {
		WpHooks::onFilter( 'term_exists_default_query_args' )
			->then( spreadArgs( function( $args ) {
				return array_merge(
					$args,
					[
						'cache_domain'         => microtime(), // Prevent caching of the query
						self::KEY_SKIP_FILTERS => true,
					]
				);
			} ) );

		add_action( 'wpml_pro_translation_after_post_save', array( $this, 'beforeSyncCustomTermFieldsTranslations' ), 10, 1 );
		add_action( 'wpml_pro_translation_completed', array( $this, 'syncCustomTermFieldsTranslations' ), 10, 1 );
	}

	public function remove_hooks() {
		remove_action( 'wpml_pro_translation_after_post_save', array( $this, 'beforeSyncCustomTermFieldsTranslations' ), 10 );
		remove_action( 'wpml_pro_translation_completed', array( $this, 'syncCustomTermFieldsTranslations' ), 10 );
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function shouldSkip( $args ) {
		return (bool) Relation::propEq( self::KEY_SKIP_FILTERS, true, (array) $args );
	}

	/**
	 * @param int|false $postId
	 */
	public function beforeSyncCustomTermFieldsTranslations( $postId ) {
		$this->post_term_taxonomy_ids_before_sync = $postId ? $this->getAllPostTermTaxonomyIds( $postId ) : [];
	}

	/**
	 * @param int|false $postId
	 */
	public function syncCustomTermFieldsTranslations( $postId ) {

		if ( ! $postId ) {
			return;
		}

		$postTermTaxonomyIds    = $this->getAllPostTermTaxonomyIds( $postId );
		$newPostTermTaxonomyIds = Lst::diff( $postTermTaxonomyIds, $this->post_term_taxonomy_ids_before_sync );

		foreach ( $postTermTaxonomyIds as $termTaxonomyId ) {
			$isNewTerm = (bool) Lst::includes( $termTaxonomyId, $newPostTermTaxonomyIds );
			$sync      = new \WPML_Sync_Term_Meta_Action( $this->sitepress, (int) $termTaxonomyId, $isNewTerm );
			$sync->run();
		}
	}

	/**
	 * @param int $postId
	 *
	 * @return array
	 */
	private function getAllPostTermTaxonomyIds( $postId ) {
		$wpdb     = $this->wpdb;
		$termRels = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . 'term_relationships WHERE object_id = %d',
				$postId
			)
		);

		return Fns::map( Obj::prop( 'term_taxonomy_id' ), $termRels );
	}
}
