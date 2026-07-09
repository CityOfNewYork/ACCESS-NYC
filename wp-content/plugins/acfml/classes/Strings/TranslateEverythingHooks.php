<?php

namespace ACFML\Strings;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslateEverythingHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const KINDS = [
		Package::FIELD_GROUP_PACKAGE_KIND_SLUG => [
			'title'  => 'ACF Field Group',
			'plural' => 'ACF Field Groups',
			'slug'   => Package::FIELD_GROUP_PACKAGE_KIND_SLUG,
		],
		Package::CPT_PACKAGE_KIND_SLUG         => [
			'title'  => 'ACF Custom Post Type',
			'plural' => 'ACF Custom Post Types',
			'slug'   => Package::CPT_PACKAGE_KIND_SLUG,
		],
		Package::TAXONOMY_PACKAGE_KIND_SLUG    => [
			'title'  => 'ACF Custom Taxonomy',
			'plural' => 'ACF Custom Taxonomies',
			'slug'   => Package::TAXONOMY_PACKAGE_KIND_SLUG,
		],
		Package::OPTION_PAGE_PACKAGE_KIND_SLUG => [
			'title'  => 'ACF Option Page',
			'plural' => 'ACF Option Pages',
			'slug'   => Package::OPTION_PAGE_PACKAGE_KIND_SLUG,
		],
	];

	public function add_hooks() {
		Hooks::onFilter( 'wpml_active_string_package_kinds' )
			->then( spreadArgs( [ $this, 'registerActiveStringPackageKinds' ] ) );
	}

	/**
	 * @param array $kinds
	 *
	 * @return array
	 */
	public function registerActiveStringPackageKinds( $kinds ) {
		return array_merge( $kinds, self::KINDS );
	}
}
