<?php


namespace WPML\TM\ATE\Review;


use WPML\Element\API\Post;
use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\TM\API\Translators;
use WPML_Translations_Queue;
use function WPML\FP\invoke;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class NextTranslationLink {

	public static function get( $currentJob ) {
		global $sitepress;

		$doNotFilterPreviewLang  = Fns::always( false );
		$addPreviewLangFilter    = function () use ( $doNotFilterPreviewLang ) {
			add_filter( 'wpml_should_filter_preview_lang', $doNotFilterPreviewLang );
		};
		$removePreviewLangFilter = function () use ( $doNotFilterPreviewLang ) {
			remove_filter( 'wpml_should_filter_preview_lang', $doNotFilterPreviewLang );
		};

		$getTranslationPostId = Fns::memorize( self::getTranslationPostId() );
		$switchToPostLang = function ( $job ) use ( $sitepress, $getTranslationPostId ) {
			Maybe::of( $job )
				->chain( $getTranslationPostId )
				->map( Post::getLang() )
				->map( [ $sitepress, 'switch_lang' ] );
		};

		$restoreLang = function () use ( $sitepress ) {
			$sitepress->switch_lang( null );
		};

		$getLink = Fns::converge( Fns::liftA2( PreviewLink::get() ), [
			$getTranslationPostId,
			Maybe::safe( invoke( 'get_translate_job_id' ) )
		] );

		return Maybe::of( $currentJob )
			->map( self::getNextJob() )
			->map( Fns::tap( $switchToPostLang ) )
			->map( Fns::tap( $addPreviewLangFilter ) )
			->chain( $getLink )
			->map( Fns::tap( $removePreviewLangFilter ) )
			->map( Fns::tap( $restoreLang ) )
			->getOrElse( null );
	}

	private static function getTranslationPostId() {
		return function ( $nextJob ) {
			return Maybe::of( $nextJob )
				->map( pipe( invoke( 'get_original_element_id' ), PostTranslations::get() ) )
				->map( Obj::prop( $nextJob->get_target_language() ) )
				->map( Obj::prop( 'element_id' ) );
		};
	}

	/**
	 * @return \Closure :: \stdClass -> \WPML_TM_Post_Job_Entity
	 */
	private static function getNextJob() {
		return function ( $currentJob ) {
			$getJob = function ( $sourceLanguage, $targetLanguages ) use ( $currentJob ) {
				$excludeCurrentJob = pipe( invoke( 'get_translate_job_id' ), Relation::equals( (int) $currentJob->job_id ), Logic::not() );

				$samePostTypes = function ( $nextJob ) use ( $currentJob ) {
					$currentJobPostType = \get_post_type( $currentJob->original_doc_id );
					$nextJobPostType    = \get_post_type( $nextJob->get_original_element_id() );

					return $currentJobPostType === $nextJobPostType;
				};

				$nextJob = \wpml_collect(wpml_tm_get_jobs_repository()
					->get( self::buildSearchParams( $sourceLanguage, $targetLanguages ) ) )
					->filter( $samePostTypes )
					->first( $excludeCurrentJob );

				if ( ! $nextJob ) {
					$nextJob = \wpml_collect( wpml_tm_get_jobs_repository()
						->get( self::buildSearchParams( $sourceLanguage, $targetLanguages ) ) )
						->first( $excludeCurrentJob );
				}

				return $nextJob;
			};

			$languagePairs = \wpml_collect( Obj::propOr( [], 'language_pairs', Translators::getCurrent() ) );

			$filterTargetLanguages = function ( $targetLanguages, $sourceLanguage ) {
				$icl_translation_filter = WPML_Translations_Queue::get_cookie_filters();
				if ( isset( $icl_translation_filter['to'] ) && '' !== $icl_translation_filter['to'] ) {
					return [
						'source'  => $sourceLanguage,
						'targets' => [ $icl_translation_filter['to'] ],
					];
				}
				return [
					'source'  => $sourceLanguage,
					'targets' => $targetLanguages,
				];
			};

			$filterJobByPairOfLanguages = function ( $job, $pairOfLanguages ) use ( $getJob ) {
				return $job ?: $getJob( Obj::prop( 'source', $pairOfLanguages ), Obj::prop( 'targets', $pairOfLanguages ) );
			};

			return $languagePairs
				->map($filterTargetLanguages)
				->reduce($filterJobByPairOfLanguages);
		};
	}

	/**
	 * @param string   $sourceLang
	 * @param string[] $targetLanguages
	 *
	 * @return \WPML_TM_Jobs_Search_Params
	 */
	private static function buildSearchParams( $sourceLang, array $targetLanguages ) {
		return ( new \WPML_TM_Jobs_Search_Params() )
			->set_needs_review()
			->set_source_language( $sourceLang )
			->set_target_language( $targetLanguages );
	}

}
