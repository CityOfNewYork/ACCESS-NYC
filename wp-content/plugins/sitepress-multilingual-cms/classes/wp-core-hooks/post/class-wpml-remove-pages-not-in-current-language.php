<?php

use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Fns;
use WPML\FP\Lst;
use function WPML\FP\pipe;
use WPML\API\PostTypes;

class WPML_Remove_Pages_Not_In_Current_Language {
	/** @var \wpdb */
	private $wpdb;
	/** @var \SitePress */
	private $sitepress;

	/**
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}


	/**
	 * @param array $posts Array of posts to filter
	 * @param array $get_page_arguments Arguments passed to the `get_pages` function
	 * @param \WP_Post[]|array[]|int[] $posts Array of posts or post IDs to filter (post IDs are required in tests but it might not be a real case)
	 *
	 * @return array
	 */
	function filter_pages( $posts, $get_page_arguments ) {
		$filtered_posts   = $posts;
		$current_language = $this->sitepress->get_current_language();

		if ( 'all' !== $current_language && 0 !== count( $posts ) ) {
			$post_type = $this->find_post_type( $get_page_arguments, $posts );

			if ( Lst::includes( $post_type, PostTypes::getTranslatable() ) ) {
				$white_list = $this->get_posts_in_current_languages( $post_type, $current_language );
				if ( $this->sitepress->is_display_as_translated_post_type( $post_type ) ) {
					$original_posts_in_other_language = $this->get_original_posts_in_other_languages( $post_type, $current_language );
					$white_list                       = Lst::concat( $white_list, $original_posts_in_other_language );
				}
				$get_post_id          = Logic::ifElse( 'is_numeric', Fns::identity(), Obj::prop( 'ID' ) );
				$filter_not_belonging = Fns::filter( pipe( $get_post_id, Lst::includes( Fns::__, $white_list ) ) );
				$filtered_posts       = $filter_not_belonging( $posts );
			}
		}

		return $filtered_posts;
	}

	/**
	 * @param string $post_type
	 * @param string $current_language
	 *
	 * @return array
	 */
	private function get_posts_in_current_languages( $post_type, $current_language ) {
		$query = "
				SELECT p.ID FROM {$this->wpdb->posts} p
				JOIN {$this->wpdb->prefix}icl_translations wpml_translations ON p.ID = wpml_translations.element_id
				WHERE wpml_translations.element_type=%s AND p.post_type=%s AND wpml_translations.language_code = %s
			";

		$args = [ 'post_' . $post_type, $post_type, $current_language ];

		return $this->get_post_ids( $this->wpdb->prepare( $query, $args ) );
	}

	/**
	 * @param string $post_type
	 * @param string $current_language
	 *
	 * @return array
	 */
	private function get_original_posts_in_other_languages( $post_type, $current_language ) {
		$query = "
				SELECT p.ID FROM {$this->wpdb->posts} p
				JOIN {$this->wpdb->prefix}icl_translations wpml_translations ON p.ID = wpml_translations.element_id
				WHERE wpml_translations.element_type=%s 
				  AND p.post_type=%s 
				  AND wpml_translations.language_code <> %s 
				  AND wpml_translations.source_language_code IS NULL
				  AND NOT EXISTS (
				      SELECT translation_id FROM {$this->wpdb->prefix}icl_translations as other_translation
				      WHERE other_translation.trid = wpml_translations.trid AND other_translation.language_code = %s
			      )
			";

		$args = [ 'post_' . $post_type, $post_type, $current_language, $current_language ];

		return $this->get_post_ids( $this->wpdb->prepare( $query, $args ) );
	}

	/**
	 * @param string $query
	 *
	 * @return int[]
	 */
	private function get_post_ids( $query ) {
		return Fns::map( Fns::unary( 'intval' ), $this->wpdb->get_col( $query ) );
	}

	/**
	 * @param array<string,string> $get_page_arguments
	 * @param array<string,string> $new_arr
	 *
	 * @return false|string
	 */
	private function find_post_type( $get_page_arguments, $new_arr ) {
		$post_type = 'page';
		if ( array_key_exists( 'post_type', $get_page_arguments ) ) {
			$post_type = $get_page_arguments['post_type'];

			return $post_type;
		} else {
			$temp_items = array_values( $new_arr );
			$first_item = $temp_items[0];
			if ( is_object( $first_item ) ) {
				$first_item = object_to_array( $first_item );
			}
			if ( is_array( $first_item ) ) {
				if ( array_key_exists( 'post_type', $first_item ) ) {
					$post_type = $first_item['post_type'];

					return $post_type;
				} elseif ( array_key_exists( 'ID', $first_item ) ) {
					$post_type = $this->sitepress->get_wp_api()->get_post_type( $first_item['ID'] );

					return $post_type;
				}

				return $post_type;
			} elseif ( is_numeric( $first_item ) ) {
				$post_type = $this->sitepress->get_wp_api()->get_post_type( $first_item );

				return $post_type;
			}

			return $post_type;
		}
	}
}