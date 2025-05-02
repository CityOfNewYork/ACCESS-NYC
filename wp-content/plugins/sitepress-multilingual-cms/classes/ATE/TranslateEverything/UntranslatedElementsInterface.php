<?php

namespace WPML\TM\ATE\TranslateEverything;

use WPML\TM\AutomaticTranslation\Actions\Actions;

interface UntranslatedElementsInterface extends CompletedTranslationsInterface {

	/**
	 * @return {
	 *   0: string,
	 *   1: string[]
	 * } 0: type, 1: languageCodes
	 */
	public function getTypeWithLanguagesToProcess();

	/**
	 * @param array  $languages
	 * @param string $type
	 * @param int    $queueSize
	 *
	 * @return {
	 *   0: int
	 *   1: string
	 * }[] For example [ [element_id1, language_code1], [element_id1, language_code2], ... ]
	 */
	public function getElementsToProcess( $languages, $type, $queueSize );


	/**
	 * @param Actions $actions
	 * @param array   $elements
	 * @param string  $type
	 *
	 * @return {
	 *  elementId: int,
	 *  lang: string,
	 *  elementType: string,
	 *  jobId: int,
	 * }[] For example [[elementId: 14, lang: fr, elementType: post, jobId: 123], ...]
	 */
	public function createTranslationJobs( Actions $actions, array $elements, $type );

	/**
	 * How many elements should be sent to translation in one iteration.
	 *
	 * @return int
	 */
	public function getQueueSize(): int;


	/**
	 * @param bool $cached
	 *
	 * @return string[]
	 */
	public function getEligibleLanguageCodes( bool $cached = false ): array;

	/**
	 * We mark $postType as completed in all secondary languages, not only in eligible for automatic translations.
	 * This is important due to the problem:
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1456/Changing-translation-engines-configuration-may-trigger-Translate-Everything-process
	 *
	 * When we activate a new secondary language and it does not support automatic translations, we mark it as completed by default.
	 * It is done to prevent unexpected triggering Translate Everything process for that language,
	 * when it suddenly becomes eligible, for example because adjustment of translation engines.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function markTypeAsCompleted( string $type );

}
