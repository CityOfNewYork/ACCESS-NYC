<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Repository;

use WPML\StringTranslation\Application\StringPackage\Query\Dto\StringPackageWithTranslationStatusDto;
use WPML\StringTranslation\Application\StringPackage\Repository\WidgetPackageRepositoryInterface;
use WPML\Infrastructure\WordPress\Port\Persistence\Options;

class WidgetPackageRepository implements WidgetPackageRepositoryInterface {

	const PACKAGE_TYPE = 'block';

	const PACKAGE_NAME = 'widget';

	/**
	 * @var Options
	 */
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	public function isWidgetPackage( StringPackageWithTranslationStatusDto $stringPackage ): bool {
		return $stringPackage->getType() === self::PACKAGE_TYPE
		       && $stringPackage->getName() === self::PACKAGE_NAME;
	}

	/**
	 * Append Widget package title with sidebar names.
	 *
	 * @param string $title Original Block package title.
	 *
	 * @return string Title with sidebar names.
	 * @throws \Exception
	 */
	public function getUpdatedTitle( string $title ): string {
		$registeredSidebars = $this->getRegisteredSidebars();
		$sidebarNames = [];

		$blockIds = array_map( function( $id ) {
				return "block-$id";
			}, array_filter( array_keys( $this->getWidgetOptions() ), 'is_int' )
		);

		foreach ( $this->getSidebarWidgets() as $sidebarId => $sidebar ) {
			if ( $sidebar && is_array( $sidebar ) && array_intersect( $blockIds, $sidebar ) ) {
				if ( $sidebarId === 'wp_inactive_widgets' ) {
					$sidebarNames[] = __( 'Inactive Widgets', 'wpml-string-translation' );
					continue;
				}
				$sidebarNames[] = $registeredSidebars[$sidebarId]['name'] ?? $sidebarId;
			}
		}

		return $sidebarNames
			? __( 'Widgets', 'wpml-string-translation' ) . ' - ' . implode( ', ', $sidebarNames )
			: $title;
	}

	private function getWidgetOptions(): array {
		return (array) $this->options->get( 'widget_block', [] );
	}

	private function getSidebarWidgets(): array {
		return (array) $this->options->get( 'sidebars_widgets', [] );
	}

	private function getRegisteredSidebars(): array {
	 	return isset( $GLOBALS['wp_registered_sidebars'] ) && is_array( $GLOBALS['wp_registered_sidebars'] )
		    ? $GLOBALS['wp_registered_sidebars']
		    : [];
	}

}
