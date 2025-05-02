<?php

namespace WPML\TM\ATE\Loader\MarkPreviouslyUnsupportedContentAsCompletedInTEA;

use WPML\Infrastructure\WordPress\Component\StringPackage\Application\Query\PackageDefinitionQuery;
use WPML\StringTranslation\Infrastructure\TranslateEverything\UntranslatedStringsFactory;
use WPML\TM\ATE\TranslateEverything\UntranslatedPackages;

class StringsAndPackagesMigration {

	/** @var UntranslatedPackages */
	private $untranslatedPackages;

	/** @var UntranslatedStringsFactory */
	private $untranslatedStringsFactory;

	/** @var PackageDefinitionQuery */
	private $translatablePackages;

	/** @var ExecutionStatus */
	private $executionStatus;


	public function __construct(
		UntranslatedPackages $untranslatedPackages,
		UntranslatedStringsFactory $untranslated,
		PackageDefinitionQuery $translatablePackages,
		ExecutionStatus $executionStatus
	) {
		$this->untranslatedPackages       = $untranslatedPackages;
		$this->untranslatedStringsFactory = $untranslated;
		$this->translatablePackages       = $translatablePackages;
		$this->executionStatus            = $executionStatus;
	}

	/**
	 * We mark all strings and packages as completed in TEA.
	 *
	 * We do the same for strings just to avoid a small glitch in TM Dashboard. Without this, a user would see
	 * a message in TM Dashboard: "Preparing content" for a few seconds. It'd eventually disappear without creating any issues.
	 * It is impossible that a user has strings in WPML 4.6 which can be included in TEA process in WPML 4.7.
	 *
	 * @return void
	 */
	public function run() {
		$translatablePackages = $this->translatablePackages->getNamesList();

		foreach ( $translatablePackages as $package ) {
			$this->untranslatedPackages->markTypeAsCompleted( $package );
		}

		$this->untranslatedStringsFactory->create()->markEverythingAsCompleted();

		$this->executionStatus->markPackagesAsExecuted();
	}

}
