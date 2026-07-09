<?php

namespace ACFML\Strings;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;

class Config {

	const DATA = [
		[
			'namespace' => 'group',
			'key'       => 'title',
			'title'     => 'Field group title',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'group',
			'key'       => 'description',
			'title'     => 'Field group description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'label',
			'title'     => 'Field label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'button_label',
			'title'     => 'Add row label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'instructions',
			'title'     => 'Field instructions',
			'type'      => 'AREA',
		],
		[
			'namespace' => 'field',
			'key'       => 'placeholder',
			'title'     => 'Field placeholder',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'prepend',
			'title'     => 'Field prepend',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'append',
			'title'     => 'Field append',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'choices',
			'title'     => 'Field choices',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'field',
			'key'       => 'message',
			'title'     => 'Field message',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'layout',
			'key'       => 'label',
			'title'     => 'Layout label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'description',
			'title'     => 'Description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'enter_title_here',
			'title'     => 'Title placeholder',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'name',
			'title'     => 'Plural label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'singular_name',
			'title'     => 'Singular label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'menu_name',
			'title'     => 'Menu name',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'all_items',
			'title'     => 'All items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'edit_item',
			'title'     => 'Edit item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'view_item',
			'title'     => 'View item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'view_items',
			'title'     => 'View items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'add_new_item',
			'title'     => 'Add new item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'add_new',
			'title'     => 'Add new',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'new_item',
			'title'     => 'New item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'parent_item_colon',
			'title'     => 'Parent item prefix',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'search_items',
			'title'     => 'Search items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'not_found',
			'title'     => 'No items found',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'not_found_in_trash',
			'title'     => 'No items found in trash',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'archives',
			'title'     => 'Archives nav menu',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'attributes',
			'title'     => 'Attributes meta box title',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'featured_image',
			'title'     => 'Featured image meta box title',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'set_featured_image',
			'title'     => 'Set featured image button label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'remove_featured_image',
			'title'     => 'Remove featured image button label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'use_featured_image',
			'title'     => 'Use featured image button label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'insert_into_item',
			'title'     => 'Insert media button label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'uploaded_to_this_item',
			'title'     => 'Uploaded to this item title',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'filter_items_list',
			'title'     => 'Filter items list',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'filter_by_date',
			'title'     => 'Filter by date',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'items_list_navigation',
			'title'     => 'Items list navigation',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'items_list',
			'title'     => 'Items list',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'item_published',
			'title'     => 'Item published notice',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'item_published_privately',
			'title'     => 'Item privately published notice',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'item_reverted_to_draft',
			'title'     => 'Item reverted to draft notice',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'item_scheduled',
			'title'     => 'Item scheduled notice',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'item_link',
			'title'     => 'Item link',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'cpt',
			'key'       => 'item_link_description',
			'title'     => 'Item link description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'description',
			'title'     => 'Description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'name',
			'title'     => 'Plural label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'singular_name',
			'title'     => 'Singular label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'menu_name',
			'title'     => 'Menu label',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'all_items',
			'title'     => 'All items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'edit_item',
			'title'     => 'Edit item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'view_item',
			'title'     => 'View item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'update_item',
			'title'     => 'Update item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'add_new_item',
			'title'     => 'Add new item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'new_item_name',
			'title'     => 'New item name',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'parent_item',
			'title'     => 'Parent item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'parent_item_colon',
			'title'     => 'Parent item with colon',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'search_items',
			'title'     => 'Search items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'popular_items',
			'title'     => 'Popular items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'separate_items_with_commas',
			'title'     => 'Separate items with commas',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'add_or_remove_items',
			'title'     => 'Add or remove items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'choose_from_most_used',
			'title'     => 'Choose from most used',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'most_used',
			'title'     => 'Most used',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'not_found',
			'title'     => 'Not found',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'no_terms',
			'title'     => 'No terms',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'name_field_description',
			'title'     => 'Name field description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'slug_field_description',
			'title'     => 'Slug field description',
			'type'      => 'AREA',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'parent_field_description',
			'title'     => 'Parent field description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'desc_field_description',
			'title'     => 'Description field description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'filter_by_item',
			'title'     => 'Filter by item',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'items_list_navigation',
			'title'     => 'Item list navigation',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'items_list',
			'title'     => 'Item list',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'back_to_items',
			'title'     => 'Back to items',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'item_link',
			'title'     => 'Item link',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'taxonomy',
			'key'       => 'item_link_description',
			'title'     => 'Item link description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'options-page',
			'key'       => 'page_title',
			'title'     => 'Page title',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'options-page',
			'key'       => 'menu_title',
			'title'     => 'Menu title',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'options-page',
			'key'       => 'description',
			'title'     => 'Description',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'options-page',
			'key'       => 'update_button',
			'title'     => 'Update button',
			'type'      => 'LINE',
		],
		[
			'namespace' => 'options-page',
			'key'       => 'updated_message',
			'title'     => 'Update message',
			'type'      => 'LINE',
		],
	];

	/**
	 *
	 * @param string $namespace
	 * @param string $key
	 *
	 * @return array
	 */
	public static function get( $namespace, $key ) {
		return Obj::propOr( [], 0, Fns::filter( Relation::propEq( 'key', $key ), self::getFor( $namespace ) ) );
	}

	/**
	 * @param string $namespace
	 *
	 * @return array
	 */
	private static function getFor( $namespace ) {
		return Fns::filter( Relation::propEq( 'namespace', $namespace ), self::DATA );
	}

	/**
	 * @return array
	 */
	public static function getForGroup() {
		return self::getFor( 'group' );
	}

	/**
	 * @return array
	 */
	public static function getForField() {
		return self::getFor( 'field' );
	}

	/**
	 * @return array
	 */
	public static function getForLayout() {
		return self::getFor( 'layout' );
	}

	/**
	 * @return array
	 */
	public static function getForCpt() {
		return self::getFor( 'cpt' );
	}

	/**
	 * @return array
	 */
	public static function getForTaxonomy() {
		return self::getFor( 'taxonomy' );
	}

	/**
	 * @return array
	 */
	public static function getForOptionsPage() {
		return self::getFor( 'options-page' );
	}

}
