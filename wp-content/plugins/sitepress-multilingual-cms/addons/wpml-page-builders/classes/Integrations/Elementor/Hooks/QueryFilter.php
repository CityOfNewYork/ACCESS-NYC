<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\Convert\Ids;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\LIB\WP\Hooks;
use WPML\PB\Elementor\DataConvert;
use WPML_Elementor_Data_Settings;

use function WPML\FP\spreadArgs;

class QueryFilter implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/**
	 * @var \SitePress
	 */
	private $sitepress;

	/**
	 * @var \WPML_Term_Translation
	 */
	private $wpmlTermTranslation;

	/**
	 * @param \SitePress             $sitepress
	 * @param \WPML_Term_Translation $wpmlTermTranslation
	 */
	public function __construct( \SitePress $sitepress, \WPML_Term_Translation $wpmlTermTranslation ) {
		$this->sitepress           = $sitepress;
		$this->wpmlTermTranslation = $wpmlTermTranslation;
	}

	public function add_hooks() {
		Hooks::onFilter( 'get_post_metadata', 10, 4 )
			->then( spreadArgs( Fns::withoutRecursion( Fns::identity(), [ $this, 'translateQueryIds' ] ) ) );
	}

	/**
	 * @param mixed  $value
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function translateQueryIds( $value, $object_id, $meta_key, $single ) {
		if ( WPML_Elementor_Data_Settings::META_KEY_DATA === $meta_key && $single ) {
			return Maybe::of( get_post_meta( $object_id, WPML_Elementor_Data_Settings::META_KEY_DATA, true ) )
				->map( function ( $data ) {
					return DataConvert::unserialize( $data, false );
				} )
				->map( function( $data ) {
					return $this->recursivelyTranslateQueryIds( $data ); }
				)
				->map( function ( $data ) {
					return DataConvert::serialize( $data, false );
				} )
				->getOrElse( $value );
		}

		return $value;
	}

	/**
	 * @param array|object|mixed $data
	 *
	 * @return array|object|mixed
	 */
	private function recursivelyTranslateQueryIds( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->recursivelyTranslateQueryIds( $value );
			}
		} elseif ( is_object( $data ) ) {
			if ( ! empty( $data->elements ) ) {
				$data->elements = $this->recursivelyTranslateQueryIds( $data->elements );
			}
			if ( ! empty( $data->settings->post_query_include_term_ids ) ) {
				$data->settings->post_query_include_term_ids = $this->convertTermTaxonomyIds( $data->settings->post_query_include_term_ids );
			}
			if ( ! empty( $data->settings->post_query_exclude_term_ids ) ) {
				$data->settings->post_query_exclude_term_ids = $this->convertTermTaxonomyIds( $data->settings->post_query_exclude_term_ids );
			}
			if ( ! empty( $data->settings->post_query_posts_ids ) ) {
				$data->settings->post_query_posts_ids = Ids::convert( $data->settings->post_query_posts_ids, Ids::ANY_POST );
			}
		}

		return $data;
	}

	/**
	 * @param int[] $ids
	 *
	 * @return int[]
	 */
	private function convertTermTaxonomyIds( $ids ) {
		$currentLanguage = $this->sitepress->get_current_language();

		$translateTermTaxonomyId = function ( $termTaxonomyId ) use ( $currentLanguage ) {
			return $this->wpmlTermTranslation->element_id_in( $termTaxonomyId, $currentLanguage, true );
		};

		return wpml_collect( $ids )
			->map( $translateTermTaxonomyId )
			->filter()
			->toArray();
	}
}
