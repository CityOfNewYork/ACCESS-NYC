<?php

namespace WPML\StringTranslation\Infrastructure\TranslateEverything;

use WPML\FP\Lst;
use WPML\Setup\Option;
use WPML\StringTranslation\Application\StringHtml\Command\ProcessFrontendStringsObserverInterface;
use WPML\TM\AutomaticTranslation\Actions\Actions;

class ProcessFrontendStringsObserver implements ProcessFrontendStringsObserverInterface {

	/**
	 * @var UntranslatedStrings
	 */
	private $untranslatedStrings;

	/** @var Actions */
	private $actions;

	public function __construct( UntranslatedStrings $untranslatedStrings, Actions $actions ) {
		$this->untranslatedStrings = $untranslatedStrings;
		$this->actions             = $actions;
	}


	/**
	 * If any of the newly discovered strings do not currently have translations,
	 * reset the string completion status in TEA. This will rerun the entire TEA process, but solely for strings.
	 *
	 * This approach is safer and more convenient than sending only the missing strings for translation,
	 * as we do not need to be concerned about the extensive size of $stringIds.
	 * The TEA process will handle the proper chunking of data.
	 *
	 * @param int[] $stringIds
	 *
	 * @return void
	 */
	public function newFrontendStringsRegistered( array $stringIds ) {
		if ( count( $stringIds ) && Option::shouldTranslateEverything() ) {
			// check if any of newly found strings do not have translations yet
			$eligibleLanguages = $this->untranslatedStrings->getEligibleLanguageCodes( true );

			$notTranslatedFrontendStrings = $this->untranslatedStrings->getElementsToProcess(
				$eligibleLanguages,
				'string',
				1 // limit can be minimal as we just need to check if there is ANY such string
			);

			if ( count( $notTranslatedFrontendStrings ) ) {
				$elements = Lst::xprod( $stringIds, $eligibleLanguages );

				$this->untranslatedStrings->createTranslationJobs( $this->actions, $elements, 'string' );
			}
		}
	}

}
