<?php

namespace WPML\TM\ATE\TranslateEverything;

interface CompletedTranslationsInterface {

	/**
	 * @param bool $cached
	 *
	 * @return bool
	 */
	public function isEverythingProcessed( $cached );

	/**
	 * @return void
	 */
	public function markEverythingAsCompleted();


	/**
	 * @return void
	 */
	public function markEverythingAsUncompleted();

	/**
	 * @param string[] $languages
	 *
	 * @return void
	 */
	public function markLanguagesAsCompleted( array $languages );

	/**
	 * @param string[] $languages
	 *
	 * @return void
	 */
	public function markLanguagesAsUncompleted( array $languages );
}
