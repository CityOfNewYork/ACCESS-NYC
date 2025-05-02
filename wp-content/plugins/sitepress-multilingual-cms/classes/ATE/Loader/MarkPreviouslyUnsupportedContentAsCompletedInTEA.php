<?php

namespace WPML\TM\ATE\Loader;

use WPML\API\PostTypes;
use WPML\Setup\Option;
use WPML\TM\ATE\Loader\MarkPreviouslyUnsupportedContentAsCompletedInTEA\ExecutionStatus;
use WPML\TM\ATE\Loader\MarkPreviouslyUnsupportedContentAsCompletedInTEA\PostTypesMigration;
use WPML\TM\ATE\Loader\MarkPreviouslyUnsupportedContentAsCompletedInTEA\StringsAndPackagesMigration;
use WPML\TM\ATE\TranslateEverything\UntranslatedPackages;
use WPML\TM\ATE\TranslateEverything\UntranslatedPosts;
use WPML\WP\OptionManager;
use WPML\StringTranslation\Infrastructure\TranslateEverything\UntranslatedStringsFactory;
use function WPML\Container\make;

class MarkPreviouslyUnsupportedContentAsCompletedInTEA {

	/** @var PostTypesMigration */
	private $postTypesMigration;

	/** @var ExecutionStatus */
	private $executionStatus;

	public function __construct(
		PostTypesMigration $postTypesMigration,
		ExecutionStatus $executionStatus
	) {
		$this->postTypesMigration        = $postTypesMigration;
		$this->executionStatus           = $executionStatus;
	}


	public function run() {
		/**
		 * If the both post types and string packages migrations are fully executed, we don't need to run them again.
		 */
		if ( $this->executionStatus->isFullyExecuted() ) {
			return;
		}

		/**
		 * If the Translate Everything is not enabled, we don't need to run the migrations.
		 * We just mark both migrations as done
		 */
		if ( ! Option::shouldTranslateEverything() ) {
			$this->executionStatus->markPostTypesAsExecuted();
			if ( wpml_is_st_loaded() ) {
				$this->executionStatus->markPackagesAsExecuted();
			}

			return;
		}

		if ( ! $this->executionStatus->arePostTypesExecuted() ) {
			$this->postTypesMigration->run();
		}

		/**
		 * We run the string packages migration only if ST plugin is enabled.
		 * We mark the string migration as done inside `StringsAndPackagesMigration` so if a user re-enables ST plugin
		 * after the post types migration is already done, then we will run only missing string migration.
		 */
		if ( ! $this->executionStatus->arePackagesExecuted() && wpml_is_st_loaded() ) {
			// StringsAndPackagesMigration dependency cannot be injected in the constructor
			// because if ST plugin is not activated, some of its dependencies will not be available.
			$stingAndPackagesMigration = make( StringsAndPackagesMigration::class );
			$stingAndPackagesMigration->run();
		}
	}

}
