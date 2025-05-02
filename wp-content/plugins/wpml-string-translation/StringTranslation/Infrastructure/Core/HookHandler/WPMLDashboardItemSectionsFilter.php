<?php

namespace WPML\StringTranslation\Infrastructure\Core\HookHandler;


use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractFilterHookHandler;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\ViewModel\ItemSection;

class WPMLDashboardItemSectionsFilter  extends AbstractFilterHookHandler {
	const FILTER_NAME = 'wpml_tm_dashboard_item_sections';
	const FILTER_ARGS = 1;
	const FILTER_PRIORITY = 9;

	protected function onFilter( ...$args ) {
		/** @var ItemSection $itemSections */
		$itemSections = $args[0];

		$kindId = 'stringPackage';
		$itemSectionPattern = '%s/%s';

		$package_kinds = $this->getTranslatableStringPackages();

		// Add any packages found to the $types array
		foreach ( $package_kinds as $package_data ) {
			$package_type_slug = $package_data->kind_slug;
			$package_type      = $package_data->kind;
			$itemSection = new ItemSection(
				sprintf( $itemSectionPattern, $kindId, $package_type_slug ),
				$package_type,
				$package_type,
				$package_type,
				$kindId,
				false,
				$package_type_slug,
				[
					'title' => ''
				]
			);
			$itemSections[] = $itemSection;
		}

		return $itemSections;
	}

	private function getTranslatableStringPackages() {
		global $wpdb;

		$package_kinds = $wpdb->get_results( "SELECT kind, kind_slug FROM {$wpdb->prefix}icl_string_packages WHERE id>0 and post_id is null group by kind_slug, kind" );

		// Group and exclude string packages where ``post_id`` is not null.
		// For example, Gutenberg.
		return $package_kinds;
	}
}