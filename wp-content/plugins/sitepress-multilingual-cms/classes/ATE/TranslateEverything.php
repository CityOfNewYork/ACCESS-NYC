<?php

namespace WPML\TM\ATE;

use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Left;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Right;
use WPML\Media\Option as MediaOption;
use WPML\Setup\Option;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\ATE\TranslateEverything\CompletedTranslationsInterface;
use WPML\TM\ATE\TranslateEverything\UntranslatedElementsInterface;
use WPML\TM\ATE\TranslateEverything\UntranslatedPackages;
use WPML\TM\ATE\TranslateEverything\UntranslatedPosts;
use WPML\TM\AutomaticTranslation\Actions\Actions;
use WPML\Utilities\KeyedLock;
use function WPML\Container\make;

class TranslateEverything implements CompletedTranslationsInterface {

	/**
	 * @var UntranslatedElementsInterface[]
	 */
	private $untranslated_elements = [];

	const LOCK_RELEASE_TIMEOUT = 2 * MINUTE_IN_SECONDS;

	public function __construct( UntranslatedPosts $untranslated_posts, UntranslatedPackages $untranslated_packages ) {
		$this->untranslated_elements = [
			$untranslated_packages,
			$untranslated_posts,
		];

		$this->untranslated_elements = apply_filters(
			'wpml_translate_everything_untranslated_elements_strategies',
			$this->untranslated_elements
		);

		$this->untranslated_elements = Fns::filter( function ( $strategy ) {
			return $strategy instanceof UntranslatedElementsInterface;
		}, $this->untranslated_elements );
	}

	public function run(
		Collection $data,
		Actions $actions
	) {
		if ( ! MediaOption::isSetupFinished() ) {
			return Left::of( [ 'key' => 'media-setup-not-finished' ] );
		}

		if ( ! CachedLanguageMappings::doesDefaultLanguageSupportAutomaticTranslations() ) {
			return Left::of( [ 'error' => 'default-language-does-not-support-automatic-translations' ] );
		}

		$lock = make( KeyedLock::class, [ ':name' => self::class ] );
		$key  = $lock->create( $data->get( 'key' ), self::LOCK_RELEASE_TIMEOUT );

		if ( $key ) {
			$createdJobs = [];
			if ( Option::shouldTranslateEverything() ) {
				$createdJobs = $this->translateEverything( $actions );
			}

			if ( $this->isEverythingProcessed() || ! Option::shouldTranslateEverything() ) {
				$lock->release();
				$key = false;
			}

			return Right::of( [ 'key' => $key, 'createdJobs' => $createdJobs ] );
		} else {
			return Left::of( [ 'key' => 'in-use' ] );
		}
	}

	/**
	 * @param Actions $actions
	 */
	private function translateEverything( Actions $actions ) {
		foreach ( $this->untranslated_elements as $untranslated ) {
			while ( ! $untranslated->isEverythingProcessed() ) {
				list( $types, $languages ) = $untranslated->getTypeWithLanguagesToProcess();
				if ( ! $types || ! $languages ) {
					continue;
				}

				$queueSize = $untranslated->getQueueSize();
				$elements = $untranslated->getElementsToProcess( $languages, $types, $queueSize + 1 );

				if ( count( $elements ) <= $queueSize ) {
					$untranslated->markTypeAsCompleted( $types );
				}

				if ( count( $elements ) ) {
					return $untranslated->createTranslationJobs( $actions, Lst::slice( 0, $queueSize, $elements ), $types );
				}
			}
		}

		return [];
	}


	/**
	 * @param bool $cached
	 *
	 * @return bool
	 */
	public function isEverythingProcessed( $cached = false ) {
		foreach ( $this->untranslated_elements as $untranslated ) {
			if ( ! $untranslated->isEverythingProcessed( $cached ) ) {
				return false;
			}
		}

		return true;
	}

	public function markEverythingAsCompleted() {
		foreach ( $this->untranslated_elements as $untranslated ) {
			$untranslated->markEverythingAsCompleted();
		}
	}


	public function markEverythingAsUncompleted() {
		foreach ( $this->untranslated_elements as $untranslated ) {
			$untranslated->markEverythingAsUncompleted();
		}
	}

	public function markLanguagesAsCompleted( array $languages ) {
		foreach ( $this->untranslated_elements as $untranslated ) {
			$untranslated->markLanguagesAsCompleted( $languages );
		}
	}

	public function markLanguagesAsUncompleted( array $languages ) {
		foreach ( $this->untranslated_elements as $untranslated ) {
			$untranslated->markLanguagesAsUncompleted( $languages );
		}
	}


}
