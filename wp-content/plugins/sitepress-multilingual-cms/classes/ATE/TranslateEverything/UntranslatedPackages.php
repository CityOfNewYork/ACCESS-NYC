<?php

namespace WPML\TM\ATE\TranslateEverything;

use WPML\API\PostTypes;
use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\Infrastructure\WordPress\Component\StringPackage\Application\Query\PackageDefinitionQuery;
use WPML\Setup\Option;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\AutomaticTranslation\Actions\Actions;
use function WPML\FP\pipe;

class UntranslatedPackages extends AbstractUntranslatedElements {

	/** @var PackageDefinitionQuery */
	private $translatablePackages;

	public function __construct(
		\wpdb $wpdb,
		\WPML_TM_Old_Jobs_Editor $oldJobsEditor = null,
		PackageDefinitionQuery $translatablePackages = null
	) {
		parent::__construct( $wpdb, $oldJobsEditor );
		$this->translatablePackages = $translatablePackages ?: new PackageDefinitionQuery();
	}

	/**
	 * @return array|null
	 */
	public function getTypeWithLanguagesToProcess() {
		$packageKinds = $this->getPackageKindToTranslate(
			$this->getTypes(),
			$this->getEligibleLanguageCodes()
		);

		return wpml_collect( $packageKinds )
			->first();
	}

	public function getElementsToProcess( $languages, $type, $queueSize ) {
		$wpdb = $this->wpdb;

		if ( empty( $languages ) ) {
			return [];
		}

		$language_parts      = Lst::join( ' UNION ALL ', Fns::map( Str::replace( '__', Fns::__, "SELECT '__' AS code" ), $languages ) );
		$acceptable_statuses = ICL_TM_NOT_TRANSLATED . ', ' . ICL_TM_ATE_CANCELLED;

		$oldEditorCondition = $this->buildOldEditorCondition();

		$sql = "
			SELECT original_element.element_id, languages.code
			FROM {$this->wpdb->prefix}icl_translations original_element
			INNER JOIN ( {$language_parts} ) as languages
    		LEFT JOIN {$this->wpdb->prefix}icl_translations translations ON translations.trid = original_element.trid AND translations.language_code = languages.code
    		LEFT JOIN {$this->wpdb->prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id

			INNER JOIN {$this->wpdb->prefix}icl_string_packages packages ON packages.ID = original_element.element_id

			WHERE original_element.element_type like %s 
				AND original_element.source_language_code IS NULL
    		AND original_element.language_code = %s
    		AND ( translation_status.status IS NULL OR translation_status.status IN ({$acceptable_statuses}) OR translation_status.needs_update = 1)
				{$oldEditorCondition}
			ORDER BY original_element.element_id, languages.code
			LIMIT %d
		";

		$result = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$sql,
				'package_' . $type,
				Languages::getDefaultCode(),
				$queueSize
			),
			ARRAY_N
		);

		return Fns::map( Obj::evolve( [ 0 => Cast::toInt() ] ), $result );
	}

	/**
	 * @param Actions $actions
	 * @param array $elements
	 * @param string $type
	 *
	 * @return array
	 */
	public function createTranslationJobs( Actions $actions, array $elements, $type ) {
		return $actions->createNewTranslationJobs( Languages::getDefaultCode(), $elements, 'package_' . $type );
	}

	/**
	 * @param array $packageKinds
	 * @param array $languages
	 *
	 * @return array
	 */
	private function getPackageKindToTranslate( array $packageKinds, array $languages ) {
		$completed                           = $this->getCompleted();
		$getLanguageCodesNotCompletedForKind = pipe( Obj::propOr( [], Fns::__, $completed ), Lst::diff( $languages ) );

		$getPackageKindToTranslate = pipe(
			Fns::map(
				function ( $kind ) use ( $getLanguageCodesNotCompletedForKind ) {
					return [ $kind, $getLanguageCodesNotCompletedForKind( $kind ) ];
				}
			),
			Fns::filter( pipe( Obj::prop( 1 ), Lst::length() ) )
		);

		return $getPackageKindToTranslate( $packageKinds );
	}

	/**
	 * @return array<stirng: string[]>
	 */
	protected function getCompleted(): array {
		return Option::getTranslateEverythingCompletedPackages();
	}

	/**
	 * @param array<string: string[]> $completed
	 */
	protected function setCompleted( array $completed ) {
		Option::setTranslateEverythingCompletedPackages( $completed );
	}

	/**
	 * @return string[]
	 */
	protected function getTypes(): array {
		return $this->translatablePackages->getNamesList();
	}


}
