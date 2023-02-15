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
	 * @param array $items An array of menu item post objects.
	 * @param object $menu The menu object.
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
	 * @param object $item Menu item - post object
	 *
	 * @return bool
	 */
	public static function hasTranslation( $item ) {
		return $item->type !== 'post_type' || (bool) self::getTranslatedId( $item );
	}

	/**
	 * @param object $item Menu item - post object
	 *
	 * @return object
	 */
	public static function translateItem( $item ) {
		if ( $item->type === 'post_type' ) {
			$translatedId = self::getTranslatedId( $item );
			foreach ( get_object_vars( Post::get( $translatedId ) ) as $key => $value ) {
				if ( $key !== 'menu_order' ) {
					$item->$key = $value;
				}
			}
			$item->object_id = (string) $translatedId;
			$item->title     = $item->post_title;
		}

		return $item;
	}

	/**
	 * @param object $item Menu item - post object
	 *
	 * @return bool
	 */
	public static function canView ( $item ) {
		return current_user_can( 'administrator' ) || $item->type !== 'post_type' || $item->post_status !== 'draft';
	}

	/**
	 * @param object $menu The menu object.
	 *
	 * @return bool
	 */
	private static function doesNotHaveMenuInCurrentLanguage( $menu ) {
		return ! wpml_object_id_filter( $menu->term_id, 'nav_menu' );
	}

	/**
	 * @param object $item Menu item - post object
	 *
	 * @return int|null
	 */
	private static function getTranslatedId( $item ) {
		return wpml_object_id_filter( $item->object_id, $item->object );
	}
}