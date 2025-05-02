<?php

namespace WPML\TM\ATE\TranslateEverything;

use WPML\API\PostTypes;
use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\Setup\Option;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\AutomaticTranslation\Actions\Actions;
use function WPML\FP\pipe;

class UntranslatedPosts extends AbstractUntranslatedElements{

	/**
	 * @return array
	 */
	public function getTypeWithLanguagesToProcess() {
		$postTypes = $this->getPostTypesToTranslate(
			$this->getTypes(),
			$this->getEligibleLanguageCodes()
		);

		return wpml_collect( $postTypes )
			->prioritize( Relation::propEq( 0, 'post' ) )
			->prioritize( Relation::propEq( 0, 'page' ) )
			->first();
	}

	/**
	 * @param array $postTypes
	 * @param array $targetLanguages
	 *
	 * @return array
	 */
	private function getPostTypesToTranslate( array $postTypes, array $targetLanguages ) {
		$completed                               = $this->getCompleted();
		$getLanguageCodesNotCompletedForPostType = pipe( Obj::propOr( [], Fns::__, $completed ), Lst::diff( $targetLanguages ) );

		$getPostTypesToTranslate = pipe(
			Fns::map( function ( $postType ) use ( $getLanguageCodesNotCompletedForPostType ) {
				return [ $postType, $getLanguageCodesNotCompletedForPostType( $postType ) ];
			} ),
			Fns::filter( pipe( Obj::prop( 1 ), Lst::length() ) )
		);

		return $getPostTypesToTranslate( $postTypes );
	}

	/**
	 * @param array $languages
	 * @param string $type
	 * @param int $queueSize
	 *
	 * @return array
	 */
	public function getElementsToProcess( $languages, $type, $queueSize ) {
		if ( empty( $languages ) ) {
			// Without secondaryLanguages there won't be any posts, and
			// the following query will throw an error.
			return [];
		}

		$languagesPart      = Lst::join( ' UNION ALL ', Fns::map( Str::replace( '__', Fns::__, "SELECT '__' AS code" ), $languages ) );
		$acceptableStatuses = ICL_TM_NOT_TRANSLATED . ', ' . ICL_TM_ATE_CANCELLED;

		$oldEditorCondition = $this->buildOldEditorCondition();

		$sql = "
			SELECT original_element.element_id, languages.code
			FROM {$this->wpdb->prefix}icl_translations original_element
			INNER JOIN ( $languagesPart ) as languages
			LEFT JOIN {$this->wpdb->prefix}icl_translations translations ON translations.trid = original_element.trid AND translations.language_code = languages.code
			LEFT JOIN {$this->wpdb->prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			
			INNER JOIN {$this->wpdb->posts} posts ON posts.ID = original_element.element_id
			
			LEFT JOIN {$this->wpdb->postmeta} postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key = %s
			
			WHERE original_element.element_type = %s 
			  	AND original_element.source_language_code IS NULL
			    AND original_element.language_code = %s
			    AND ( translation_status.status IS NULL OR translation_status.status IN ({$acceptableStatuses}) OR translation_status.needs_update = 1) 
			    AND posts.post_status IN ( 'publish', 'inherit' )
					AND ( postmeta.meta_value IS NULL OR postmeta.meta_value = 'no' )
			    {$oldEditorCondition		}
			ORDER BY original_element.element_id, languages.code
			LIMIT %d
		";

		$result = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$sql,
				\WPML_TM_Post_Edit_TM_Editor_Mode::POST_META_KEY_USE_NATIVE,
				'post_' . $type,
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
		return $actions->createNewTranslationJobs( Languages::getDefaultCode(), $elements, 'post_' . $type );
	}


	/**
	 * Notice that this method is specific for UntranslatedPosts.
	 * You can't find it in the UntranslatedElementsInterface.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function markPostTypeAsUncompleted( string $type ) {
		$completed = $this->getCompleted();
		$completed[ $type ] = [];

		$this->setCompleted( $completed );
	}

	/**
	 * Notice that this method is specific for UntranslatedPosts.
	 * You can't find it in the UntranslatedElementsInterface.
	 *
	 * @param string $type
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function isPostTypeProcessedForTypeAndLanguage( string $type, string $languageCode ): bool {
		$completed = $this->getCompleted();
		$completedLanguages = $completed[ $type ] ?? [];

		return in_array( $languageCode, $completedLanguages );
	}

	/**
	 * @return array<string: string[]>
	 */
	protected function getCompleted(): array {
		return Option::getTranslateEverythingCompletedPosts();
	}

	/**
	 * @param array<string: string[]> $completed
	 */
	protected function setCompleted( array $completed ) {
		Option::setTranslateEverythingCompletedPosts( $completed );
	}

	protected function getTypes(): array {
		return PostTypes::getAutomaticTranslatable();
	}


}
