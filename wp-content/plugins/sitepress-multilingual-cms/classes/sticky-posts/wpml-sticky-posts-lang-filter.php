<?php

class WPML_Sticky_Posts_Lang_Filter {
	/** @var SitePress */
	private $sitepress;

	/** @var WPML_Post_Translation */
	private $post_translation;

	/** @var array */
	private $post_valid_in_all_langs_cache = array();

	/**
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translation
	 */
	public function __construct( SitePress $sitepress, WPML_Post_Translation $post_translation ) {
		$this->sitepress        = $sitepress;
		$this->post_translation = $post_translation;
	}

	/**
	 * @param array $posts
	 * @param string $lang
	 *
	 * @return array
	 */
	public function filter_by_language( array $posts, $lang ) {
		if ( ! $posts ) {
			return $posts;
		}

		$result = array();
		foreach ( $posts as $post_id ) {
			if (
				$this->post_translation->get_element_lang_code( $post_id ) === $lang ||
				$this->is_post_type_valid_in_any_language( $this->post_translation->get_type( $post_id ) )
			) {
				$result[] = $post_id;
			}
		}

		return $result;
	}

	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function is_post_type_valid_in_any_language( $post_type ) {
		if ( ! array_key_exists( $post_type, $this->post_valid_in_all_langs_cache ) ) {
			$this->post_valid_in_all_langs_cache[ $post_type ] = ! $this->sitepress->is_translated_post_type( $post_type )
			                                                     || $this->sitepress->is_display_as_translated_post_type( $post_type );
		}

		return $this->post_valid_in_all_langs_cache[ $post_type ];
	}
}