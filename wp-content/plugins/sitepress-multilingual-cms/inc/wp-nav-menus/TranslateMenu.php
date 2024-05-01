<?php

namespace WPML\Core\Menu;

use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use function WPML\FP\spreadArgs;

class Translate implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wp_get_nav_menu_items', 10, 2 )
		     ->then( spreadArgs( [ self::class, 'translate' ] ) );
	}

	/**
	 * @param array    $items An array of menu item post objects.
	 * @param \WP_Term $menu The menu object.
	 *
	 * @return array
	 */
	public static function translate( $items, $menu ) {
		if ( self::doesNotHaveMenuInCurrentLanguage( $menu ) ) {

			$items = wpml_collect( $items )
				->filter( [ self::class, 'hasTranslation' ] )
				->map( [ self::class, 'translateItem' ] )
				->filter( [ self::class, 'canView' ] )
				->values()
				->toArray();
		}

		return $items;
	}

	/**
	 * @param \WP_Post $item Menu item - post object.
	 *
	 * @return bool
	 */
	public static function hasTranslation( $item ) {
		global $sitepress;
		return 'post_type' !== $item->type || (bool) self::getTranslatedId( $item ) || $sitepress->is_display_as_translated_post_type( $item->object );
	}

	/**
	 * @param \WP_Post $item Menu item - post object.
	 *
	 * @return \WP_Post
	 */
	public static function translateItem( $item ) {
		if ( 'post_type' === $item->type ) {
			$translatedId = self::getTranslatedId( $item, true );
			$post         = Post::get( $translatedId );
			if ( ! $post instanceof \WP_Post ) {
				return $item;
			}
			foreach ( get_object_vars( $post ) as $key => $value ) {
				// We won't send the translated ID, since it affects front-end styles negatively.
				if ( ! in_array( $key, [ 'menu_order', 'post_type', 'ID' ] ) ) {
					$item->$key = $value;
				}
			}
			$item->object_id = (string) $translatedId;
			$item->title     = $item->post_title;
		}

		return $item;
	}

	/**
	 * @param \WP_Post $item Menu item - post object.
	 *
	 * @return bool
	 */
	public static function canView( $item ) {
		return current_user_can( 'administrator' ) || 'post_type' !== $item->type || 'draft' !== $item->post_status;
	}

	/**
	 * @param \WP_Term $menu The menu object.
	 *
	 * @return bool
	 */
	private static function doesNotHaveMenuInCurrentLanguage( $menu ) {
		return ! wpml_object_id_filter( $menu->term_id, 'nav_menu' );
	}

	/**
	 * @param \WP_Post $item Menu item - post object.
	 * @param bool     $return_original_if_missing
	 * @return int|null
	 */
	private static function getTranslatedId( $item, $return_original_if_missing = false ) {
		return wpml_object_id_filter( $item->object_id, $item->object, $return_original_if_missing );
	}
}
