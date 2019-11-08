<?php

class WPML_Sticky_Posts_Sync {
	/** @var SitePress */
	private $sitepress;

	/** @var  WPML_Post_Translation $post_translation */
	private $post_translation;

	/** @var WPML_Sticky_Posts_Lang_Filter */
	private $populate_lang_option;

	/**
	 * @param SitePress                     $sitepress
	 * @param WPML_Post_Translation         $post_translation
	 * @param WPML_Sticky_Posts_Lang_Filter $populate_lang_option
	 */
	public function __construct(
		SitePress $sitepress,
		WPML_Post_Translation $post_translation,
		WPML_Sticky_Posts_Lang_Filter $populate_lang_option
	) {
		$this->sitepress            = $sitepress;
		$this->post_translation     = $post_translation;
		$this->populate_lang_option = $populate_lang_option;
	}

	/**
	 * It returns only those sticky posts which belong to a current language
	 *
	 * @return array|false
	 */
	public function pre_option_sticky_posts_filter() {
		$current_language = $this->sitepress->get_current_language();
		if ( 'all' === $current_language ) {
			return false;
		}

		$option = 'sticky_posts_' . $current_language;
		$posts  = get_option( $option );
		if ( false === $posts ) {
			$posts = $this->get_unfiltered_sticky_posts_option();
			if ( $posts ) {
				$posts = $this->populate_lang_option->filter_by_language(
					$this->get_unfiltered_sticky_posts_option(),
					$current_language
				);
				update_option( $option, $posts, true );
			}
		}

		return $posts;
	}

	/**
	 * Ensure that the original main `sticky_posts` option contains sticky posts from ALL languages
	 *
	 * @param array $posts
	 *
	 * @return array
	 */
	public function pre_update_option_sticky_posts( $posts ) {
		$langs = array_keys( $this->sitepress->get_active_languages() );
		$langs = array_diff( $langs, array( $this->sitepress->get_current_language() ) );

		$lang_parts   = array_map( array( $this, 'get_option_by_lang' ), $langs );
		$lang_parts[] = array_map( 'intval', $posts );

		return array_unique( call_user_func_array( 'array_merge', $lang_parts ) );
	}

	/**
	 * It marks as `sticky` all posts which are translation of the post or have the same original post.
	 * Basically, it means that they have the same trid in icl_translations table.
	 *
	 * @param int $post_id
	 */
	public function on_post_stuck( $post_id ) {
		$translations = $this->get_post_translations( $post_id );
		if ( $translations ) {
			foreach ( $translations as $lang => $translated_post_id ) {
				$this->add_post_id( 'sticky_posts_' . $lang, (int) $translated_post_id );
				$this->add_post_id_to_original_option( (int) $translated_post_id );
			}
		} else {
			$this->add_post_id( 'sticky_posts_' . $this->sitepress->get_current_language(), (int) $post_id );
		}
	}

	/**
	 * It un-marks as `sticky` all posts which are translation of the post or have the same original post.
	 *
	 * @param int $post_id
	 */
	public function on_post_unstuck( $post_id ) {
		foreach ( $this->get_post_translations( $post_id ) as $lang => $translated_post_id ) {
			$this->remove_post_id( 'sticky_posts_' . $lang, (int) $translated_post_id );
			$this->remove_post_id_from_original_option( (int) $translated_post_id );
		}
	}

	/**
	 * It returns an original, unfiltered `sticky_posts` option which contains sticky posts from ALL languages
	 *
	 * @return array|false
	 */
	public function get_unfiltered_sticky_posts_option() {
		remove_filter( 'pre_option_sticky_posts', array( $this, 'pre_option_sticky_posts_filter' ) );
		$posts = get_option( 'sticky_posts' );
		add_filter( 'pre_option_sticky_posts', array( $this, 'pre_option_sticky_posts_filter' ), 10, 0 );

		return $posts;
	}

	/**
	 * @param int $post_id
	 *
	 * @return array
	 */
	private function get_post_translations( $post_id ) {
		$this->post_translation->reload();
		$trid = $this->post_translation->get_element_trid( $post_id );

		return $this->post_translation->get_element_translations( false, $trid, false );
	}

	/**
	 * @param int $post_id
	 */
	private function add_post_id_to_original_option( $post_id ) {
		$this->update_original_option( $post_id, array( $this, 'add_post_id' ) );
	}

	/**
	 * @param string $option
	 * @param int    $post_id
	 */
	private function add_post_id( $option, $post_id ) {
		$sticky_posts = get_option( $option, array() );

		if ( ! in_array( $post_id, $sticky_posts, true ) ) {
			$sticky_posts[] = $post_id;
			update_option( $option, $sticky_posts, true );
		}
	}

	/**
	 * @param int $post_id
	 */
	private function remove_post_id_from_original_option( $post_id ) {
		$this->update_original_option( $post_id, array( $this, 'remove_post_id' ) );
	}

	/**
	 * @param int      $post_id
	 * @param callable $callback
	 */
	private function update_original_option( $post_id, $callback ) {
		remove_filter( 'pre_option_sticky_posts', array( $this, 'pre_option_sticky_posts_filter' ) );
		remove_filter( 'pre_update_option_sticky_posts', array( $this, 'pre_update_option_sticky_posts' ) );

		call_user_func( $callback, 'sticky_posts', $post_id );

		add_filter( 'pre_update_option_sticky_posts', array( $this, 'pre_update_option_sticky_posts' ), 10, 1 );
		add_filter( 'pre_option_sticky_posts', array( $this, 'pre_option_sticky_posts_filter' ), 10, 0 );
	}

	/**
	 * @param string $option
	 * @param int    $post_id
	 */
	private function remove_post_id( $option, $post_id ) {
		$sticky_posts = get_option( $option, array() );

		if ( ( $key = array_search( $post_id, $sticky_posts ) ) !== false ) {
			unset( $sticky_posts[ $key ] );
			update_option( $option, array_values( $sticky_posts ), true );
		}
	}

	/**
	 * @param string $lang
	 *
	 * @return array
	 */
	private function get_option_by_lang( $lang ) {
		return get_option( 'sticky_posts_' . $lang, array() );
	}
}