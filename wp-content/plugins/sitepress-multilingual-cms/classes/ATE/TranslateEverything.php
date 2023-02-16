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

	const LOCK_RELEASE_TIMEOUT = 2 * MINUTE_IN_SECONDS;
	const QUEUE_SIZE = 15;

	public function run(
		Collection $data,
		Actions $actions
	) {
		if ( ! MediaOption::isSetupFinished() ) {
			return Left::of( [ 'key' => 'waiting' ] );
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
		$defaultLang        = Languages::getDefaultCode();
		$secondaryLanguages = LanguageMappings::geCodesEligibleForAutomaticTranslations();
		$postType           = self::getPostTypeToProcess( $secondaryLanguages );

		$queueSize = $postType == 'attachment' ? self::QUEUE_SIZE * 2 : self::QUEUE_SIZE;

		$elements = UntranslatedPosts::get( $secondaryLanguages, $postType, $queueSize + 1 );

		if ( count( $elements ) <= $queueSize ) {
			Option::markPostTypeAsCompleted( $postType, $secondaryLanguages );
		}

		return count( $elements ) ?
			$actions->createNewTranslationJobs( $defaultLang, Lst::slice( 0, $queueSize, $elements ) ) :
			[];
	}

	/**
	 * @param string[] $secondaryLanguages
	 *
	 * @return string
	 */
	private static function getPostTypeToProcess( array $secondaryLanguages ) {
		$postTypes = self::getPostTypesToTranslate( PostTypes::getAutomaticTranslatable(), $secondaryLanguages );

		return wpml_collect( $postTypes )
			->prioritize( Relation::equals( 'post' ) )
			->prioritize( Relation::equals( 'page' ) )
			->first();
	}

	/**
	 * @param array $postTypes
	 * @param array $targetLanguages
	 *
	 * @return string[] E.g. ['post', 'page']
	 */
	public static function getPostTypesToTranslate( array $postTypes, array $targetLanguages ) {
		$completed = Option::getTranslateEverythingCompleted();
		$postTypesNotCompletedForTargets = pipe( Obj::propOr( [], Fns::__, $completed ), Lst::diff( $targetLanguages ), Lst::length() );

		return Fns::filter( $postTypesNotCompletedForTargets, $postTypes );
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
	 * @param string $postType
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
