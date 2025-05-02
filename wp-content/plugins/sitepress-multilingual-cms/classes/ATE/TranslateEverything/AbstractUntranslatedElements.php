<?php

namespace WPML\TM\ATE\TranslateEverything;

use WPML\API\PostTypes;
use WPML\Element\API\Languages;
use WPML\FP\Lst;
use WPML\Setup\Option;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\API\ATE\LanguageMappings;

abstract class AbstractUntranslatedElements implements UntranslatedElementsInterface {

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * @var \WPML_TM_Old_Jobs_Editor
	 */
	private $oldJobsEditor;

	public function __construct( \wpdb $wpdb, \WPML_TM_Old_Jobs_Editor $oldJobsEditor = null ) {
		$this->wpdb = $wpdb;

		if ( $oldJobsEditor ) {
			$this->oldJobsEditor = $oldJobsEditor;
		} else {
			$this->oldJobsEditor = \wpml_tm_load_old_jobs_editor();
		}
	}

	public function getQueueSize(): int {
		return 15;
	}

	/**
	 * @param bool $cached
	 *
	 * @return string[]
	 */
	public function getEligibleLanguageCodes( bool $cached = false ): array {
		$mapper = $cached ? CachedLanguageMappings::class : LanguageMappings::class;

		return $mapper::geCodesEligibleForAutomaticTranslations();
	}

	/**
	 * @param bool $cached
	 *
	 * @return bool
	 */
	public function isEverythingProcessed( $cached = false ) {
		$completed = $this->getCompleted();
		$languages = $this->getEligibleLanguageCodes( $cached );

		foreach ( $this->getTypes() as $type ) {
			$completedLanguages = $completed[ $type ] ?? [];
			/** @var string[] $remainingLanguages */
			$remainingLanguages = Lst::diff( $languages, $completedLanguages );
			if ( count( $remainingLanguages ) > 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	public function markTypeAsCompleted( string $type ) {
		$completed = $this->getCompleted();
		// It is important to get ALL secondary, not only eligible ones.
		// See the explanation in the interface.
		$languages          = Languages::getSecondaryCodes();
		$completed[ $type ] = array_merge( $completed[ $type ] ?? [], $languages );

		$this->setCompleted( $completed );
	}

	public function markEverythingAsCompleted() {
		$types     = $this->getTypes();
		$languages = Languages::getSecondaryCodes();
		$completed = $this->getCompleted();

		foreach ( $types as $type ) {
			$completed[ $type ] = array_merge( $completed[ $type ] ?? [], $languages );
		}

		$this->setCompleted( $completed );
	}


	public function markEverythingAsUncompleted() {
		$this->setCompleted( [] );
	}

	public function markLanguagesAsCompleted( array $languages ) {
		$types     = $this->getTypes();
		$completed = $this->getCompleted();

		foreach ( $types as $type ) {
			$completed[ $type ] = array_merge( $completed[ $type ] ?? [], $languages );
		}

		$this->setCompleted( $completed );
	}

	public function markLanguagesAsUncompleted( array $languages ) {
		$types     = $this->getTypes();
		$completed = $this->getCompleted();

		foreach ( $types as $type ) {
			$typeValues         = Lst::diff( $completed[ $type ] ?? [], $languages );
			$completed[ $type ] = is_array( $typeValues ) ? array_values( $typeValues ) : $typeValues;
		}

		$this->setCompleted( $completed );
	}

	/**
	 * @return array<{>string: string[]> For example: { 'post': ['fr', 'de'], 'page': ['fr', 'de'] }
	 */
	abstract protected function getCompleted(): array;

	/**
	 * @param array<string: string[]> $completed
	 *
	 * @return void
	 */
	abstract protected function setCompleted( array $completed );

	/**
	 * @return string[] for example ['post', 'page']
	 */
	abstract protected function getTypes(): array;

	/**
	 * We should exclude from TEA those needs update translations which were originally created using CTE editor
	 * and a user chose to keep CTE editor for them in WPML > Settings.
	 * @see wpmldev-3871
	 *
	 * @return string
	 */
	protected function buildOldEditorCondition(): string {
		$oldEditorCondition = '';

		if ( $this->oldJobsEditor->editorForTranslationsPreviouslyCreatedUsingCTE() === \WPML_TM_Editors::WPML ) {
			$editor = \WPML_TM_Editors::WPML;
			// Run the subquery checking only if the current job has "needs_update" status.
			// It is important due to performance reasons.
			$oldEditorCondition = "AND (
				translation_status.needs_update = 0 OR IFNULL(
					(
					  SELECT jobs.editor FROM {$this->wpdb->prefix}icl_translate_job jobs
					  WHERE jobs.job_id = (
					    SELECT MAX( job_id ) FROM {$this->wpdb->prefix}icl_translate_job
					    WHERE rid = translation_status.rid
					  )
					), 
					'ate'
				) != '{$editor}'
			)";
		}

		return $oldEditorCondition;
	}
}
