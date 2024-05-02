<?php

namespace WPML\TM\ATE;

use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Left;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Right;
use WPML\FP\Str;
use WPML\FP\Wrapper;
use WPML\LIB\WP\Post;
use WPML\Media\Option as MediaOption;
use WPML\Records\Translations;
use WPML\Setup\Option;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\ATE\TranslateEverything\UntranslatedPosts;
use WPML\TM\AutomaticTranslation\Actions\Actions;
use WPML\Utilities\KeyedLock;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class TranslateEverything {
	/**
	 * @var UntranslatedPosts
	 */
	private $untranslatedPosts;

	const LOCK_RELEASE_TIMEOUT = 2 * MINUTE_IN_SECONDS;
	const QUEUE_SIZE = 15;

	public function __construct( UntranslatedPosts $untranslatedPosts ) {
		$this->untranslatedPosts = $untranslatedPosts;
	}

	public function run(
		Collection $data,
		Actions $actions
	) {
		if ( ! MediaOption::isSetupFinished() ) {
			return Left::of( [ 'key' => 'media-setup-not-finished' ] );
		}

		$lock = make( KeyedLock::class, [ ':name' => self::class ] );
		$key  = $lock->create( $data->get( 'key' ), self::LOCK_RELEASE_TIMEOUT );

		if ( $key ) {
			$createdJobs = [];
			if ( Option::shouldTranslateEverything() ) {
				$createdJobs = $this->translateEverything( $actions );
			}

			if ( self::isEverythingProcessed() || ! Option::shouldTranslateEverything() ) {
				$lock->release();
				$key = false;
			}

			return Right::of( [ 'key' => $key, 'createdJobs' => $createdJobs ] );
		} else {
			return Left::of( [ 'key' => 'in-use', ] );
		}
	}

	/**
	 * @param Actions $actions
	 */
	private function translateEverything( Actions $actions ) {
		list( $postType, $languagesToProcess ) = self::getPostTypeToProcess();
		if ( ! $postType || ! $languagesToProcess ) {
			return [];
		}

		$elements = $this->untranslatedPosts->get( $languagesToProcess, $postType, self::QUEUE_SIZE  + 1 );

		if ( count( $elements ) <= self::QUEUE_SIZE  ) {
			/**
			 * We mark $postType as completed in all secondary languages, not only in eligible for automatic translations.
			 * This is important due to the problem:
			 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1456/Changing-translation-engines-configuration-may-trigger-Translate-Everything-process
			 *
			 * When we activate a new secondary language and it does not support automatic translations, we mark it as completed by default.
			 * It is done to prevent unexpected triggering Translate Everything process for that language,
			 * when it suddently becomes eligible, for example because adjustment of translation engines.
			 */
			Option::markPostTypeAsCompleted( $postType, Languages::getSecondaryCodes() );
		}

		return count( $elements ) ?
			$actions->createNewTranslationJobs( Languages::getDefaultCode(), Lst::slice( 0, self::QUEUE_SIZE, $elements ) ) :
			[];
	}

	/**
	 * @return array Eg. ['post', ['fr', 'de', 'es']]
	 */
	private static function getPostTypeToProcess() {
		$postTypes = self::getPostTypesToTranslate(
			PostTypes::getAutomaticTranslatable(),
			LanguageMappings::geCodesEligibleForAutomaticTranslations()
		);

		return wpml_collect( $postTypes )
			->prioritize( Relation::propEq(0, 'post') )
			->prioritize( Relation::propEq(0, 'page') )
			->first();
	}

	/**
	 * @param array $postTypes
	 * @param array $targetLanguages
	 *
	 * @return array Eg. [['post', ['fr', 'de', 'es']], ['page', ['fr', 'de', 'es']]]
	 */
	public static function getPostTypesToTranslate( array $postTypes, array $targetLanguages ) {
		$completed                               = Option::getTranslateEverythingCompleted();
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
	 * @param string $postType
	 * @param array $targetLanguages
	 *
	 * @return string[] Eg. ['fr', 'de', 'es']
	 */
	public static function getLanguagesToTranslate( $postType, array $targetLanguages ) {
		$completed = Option::getTranslateEverythingCompleted();

		return Lst::diff( $targetLanguages, Obj::propOr( [], $postType, $completed ) );
	}

	/**
	 * Checks if Translate Everything is processed for a given Post Type and Language.
	 *
	 * @param string|bool $postType
	 * @param string $language
	 *
	 * @return bool
	 */
	public static function isEverythingProcessedForPostTypeAndLanguage( $postType, $language ) {
		$completed = Option::getTranslateEverythingCompleted();
		return isset( $completed[ $postType ] ) && in_array(  $language, $completed[ $postType ] );
	}

	/**
	 * @param bool $cached
	 *
	 * @return bool
	 */
	public static function isEverythingProcessed( $cached = false ) {
		$postTypes       = PostTypes::getAutomaticTranslatable();
		$getTargetLanguages = [ $cached ? CachedLanguageMappings::class : LanguageMappings::class, 'geCodesEligibleForAutomaticTranslations'];

		return count( self::getPostTypesToTranslate( $postTypes, $getTargetLanguages() ) ) === 0;
	}
}
