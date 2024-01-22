<?php

use WPML\FP\Obj;
use WPML\Collect\Support\Collection;

class WPML_TM_Post_Edit_TM_Editor_Mode {

	const POST_META_KEY_USE_NATIVE = '_wpml_post_translation_editor_native';
	const TM_KEY_FOR_POST_TYPE_USE_NATIVE = 'post_translation_editor_native_for_post_type';
	const TM_KEY_GLOBAL_USE_NATIVE = 'post_translation_editor_native';

	/**
	 * Check post meta first
	 * Then check setting for post type
	 * Then finally check global setting
	 *
	 * @param SitePress $sitepress
	 * @param $post
	 *
	 * @return bool
	 */
	public static function is_using_tm_editor( SitePress $sitepress, $post_id ) {
		$post_id = self::get_source_id( $sitepress, $post_id, 'post_' . get_post_type( $post_id ) );

		$post_meta = get_post_meta( $post_id, self::POST_META_KEY_USE_NATIVE, true );
		if ( 'no' === $post_meta ) {
			return true;
		} elseif ( 'yes' === $post_meta ) {
			return false;
		}

		$tm_settings = self::init_settings( $sitepress );

		$post_type = get_post_type( $post_id );
		if ( isset( $tm_settings[ self::TM_KEY_FOR_POST_TYPE_USE_NATIVE ][ $post_type ] ) ) {
			return ! $tm_settings[ self::TM_KEY_FOR_POST_TYPE_USE_NATIVE ][ $post_type ];
		}

		return ! $tm_settings[ self::TM_KEY_GLOBAL_USE_NATIVE ];
	}

	/**
	 * @param SitePress $sitepress
	 * @param int $postId
	 *
	 * @return array
	 */
	public static function get_editor_settings( SitePress $sitepress, $postId ) {
		$useTmEditor = \WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $sitepress, $postId );
		$useTmEditor = apply_filters( 'wpml_use_tm_editor', $useTmEditor, $postId );

		$result = self::get_blocked_posts( [ $postId ] );

		if ( isset($result[$postId]) ) {
			$isWpmlEditorBlocked = true;
			$reason              = $result[$postId];
		} else {
			$isWpmlEditorBlocked = false;
			$reason              = '';
		}

		return [
			$useTmEditor,
			$isWpmlEditorBlocked,
			$reason,
		];
	}

	/**
	 * @param array $postIds list of post ids that should be checked is blocked.
	 *
	 * @return array list of post ids that are blocked and the reason why they are blocked.
	 */
	public static function get_blocked_posts( $postIds ) {
		/**
		 * Returns the editor settings for the posts - is the WPML editor blocked, and if so, why.
		 *
		 * Filter returns an array of: the reason why its blocked indexed by the post ID.
		 *
		 * @since 4.6.0
		 *
		 * @param array $defaultParams The default parameters that should be returned
		 * @param array $postIds An array of post IDs
		 */
		return apply_filters( 'wpml_tm_editor_exclude_posts', [], $postIds );
	}

	/**
	 * @param SitePress $sitepress
	 * @param int $post_id
	 * @param string $wpml_post_type
	 *
	 * @return int
	 */
	private static function get_source_id( SitePress $sitepress, $post_id, $wpml_post_type ) {
		$source_id    = $post_id;
		$trid         = $sitepress->get_element_trid( $post_id, $wpml_post_type );
		$translations = $sitepress->get_element_translations( $trid, $wpml_post_type );

		if ( ! $translations ) {
			return (int) $post_id;
		}

		foreach ( $translations as $translation ) {
			if ( $translation->original ) {
				$source_id = $translation->element_id;
				break;
			}
		}

		return (int) $source_id;
	}

	/**
	 * @param SitePress $sitepress
	 *
	 * @return array
	 */
	private static function init_settings( SitePress $sitepress ) {
		$tm_settings = $sitepress->get_setting( 'translation-management' );

		/**
		 * Until a user explicitly change the settings through
		 * the switcher ( @see WPML_TM_Post_Edit_TM_Editor_Select::save_mode ),
		 * we'll set it by default at run time:
		 * - Native editor set to true if using the manual method
		 * - Native editor set to false otherwise
		 */
		if ( ! isset( $tm_settings['post_translation_editor_native'] ) ) {
			if ( ( (string) ICL_TM_TMETHOD_MANUAL === (string) $tm_settings['doc_translation_method'] ) ) {
				$tm_settings['post_translation_editor_native'] = true;
			} else {
				$tm_settings['post_translation_editor_native'] = false;
			}

			if ( ! isset( $tm_settings['post_translation_editor_native_for_post_type'] ) ) {
				$tm_settings['post_translation_editor_native_for_post_type'] = [];
			}
		}

		return $tm_settings;
	}

	/**
	 * @param null|string $post_type
	 */
	public static function delete_all_posts_option( $post_type = null ) {
		global $wpdb;

		if ( $post_type ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE postmeta FROM {$wpdb->postmeta} AS postmeta
					 INNER JOIN {$wpdb->posts} AS posts ON posts.ID = postmeta.post_id
					 WHERE posts.post_type = %s AND postmeta.meta_key = %s",
					$post_type,
					self::POST_META_KEY_USE_NATIVE
				)
			);
		} else {
			delete_post_meta_by_key( self::POST_META_KEY_USE_NATIVE );
		}
	}
}
