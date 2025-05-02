<?php

namespace WPML\StringTranslation\Application\StringPackage\Repository;

use WPML\StringTranslation\Application\StringPackage\Query\Dto\StringPackageWithTranslationStatusDto;

interface WidgetPackageRepositoryInterface {

	/**
	 * Check if the given package belongs to Block Widget package.
	 *
	 * @param StringPackageWithTranslationStatusDto $stringPackage
	 * @return bool
	 */
	public function isWidgetPackage( StringPackageWithTranslationStatusDto $stringPackage ): bool;

	/**
	 * Append package title with sidebar names.
	 *
	 * @param string $title
	 * @return string
	 */
	public function getUpdatedTitle( string $title ): string;

}
