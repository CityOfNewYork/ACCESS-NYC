<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\Convert\Ids;
use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;
use WPML\PB\Elementor\DataConvert;
use WPML_Elementor_Data_Settings;

use function WPML\FP\spreadArgs;

class QueryFilter implements \IWPML_Frontend_Action {

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
			$value = get_post_meta( $object_id, WPML_Elementor_Data_Settings::META_KEY_DATA, true );
			if ( $value ) {
				$value = DataConvert::unserialize( $value, false );
				$value = $this->recursivelyTranslateQueryIds( $value );
				$value = DataConvert::serialize( $value, false );
			}
		}

		return $value;
	}

	/**
	 * @param array|object $data
	 *
	 * @return array|object
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
				$data->settings->post_query_include_term_ids = Ids::convert( $data->settings->post_query_include_term_ids, 'any_term' );
			}
			if ( ! empty( $data->settings->post_query_exclude_term_ids ) ) {
				$data->settings->post_query_exclude_term_ids = Ids::convert( $data->settings->post_query_exclude_term_ids, 'any_term' );
			}
			if ( ! empty( $data->settings->post_query_posts_ids ) ) {
				$data->settings->post_query_posts_ids = Ids::convert( $data->settings->post_query_posts_ids, 'any_post' );
			}
		}

		return $data;
	}
}
