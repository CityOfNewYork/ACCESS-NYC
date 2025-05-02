<?php

namespace WPML\Setup;

use WPML\Element\API\Entity\LanguageMapping;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Relation;
use WPML\LIB\WP\PostType;
use WPML\WP\OptionManager;

class Option {
	const POSTS_LIMIT_FOR_AUTOMATIC_TRANSLATION = 10;

	const OPTION_GROUP = 'setup';
	const CURRENT_STEP = 'current-step';

	const ORIGINAL_LANG = 'original-lang';
	const TRANSLATED_LANGS = 'translated-langs';
	const LANGUAGES_MAPPING = 'languages-mapping';

	const WHO_MODE = 'who-mode';
	const TRANSLATE_EVERYTHING = 'translate-everything';
	/** @since 4.7 */
	const TRANSLATE_EVERYTHING_DRAFTS = 'translate-everything-drafts';
	const HAS_TRANSLATE_EVERYTHING_BEEN_EVER_USED = 'has-translate-everything-been-ever-used';
	const TRANSLATE_EVERYTHING_COMPLETED = 'translate-everything-completed';
	const TRANSLATE_EVERYTHING_POSTS = 'translate-everything-posts';
	const TRANSLATE_EVERYTHING_PACKAGES_COMPLETED = 'translate-everything-packages';
	const TRANSLATE_EVERYTHING_SRTINGS_COMPLETED = 'translate-everything-strings';
	const TM_ALLOWED = 'is-tm-allowed';
	const REVIEW_MODE = 'review-mode';

	const NO_REVIEW = 'no-review';
	const PUBLISH_AND_REVIEW = 'publish-and-review';
	const HOLD_FOR_REVIEW = 'before-publish';


	public static function getCurrentStep() {
		return self::get( self::CURRENT_STEP, 'languages' );
	}

	public static function saveCurrentStep( $step ) {
		self::set( self::CURRENT_STEP, $step );
	}

	public static function getOriginalLang() {
		return self::get( self::ORIGINAL_LANG );
	}

	public static function setOriginalLang( $lang ) {
		self::set( self::ORIGINAL_LANG, $lang );
	}

	public static function getTranslationLangs() {
		return self::get( self::TRANSLATED_LANGS, [] );
	}

	public static function setTranslationLangs( array $langs ) {
		self::set( self::TRANSLATED_LANGS, $langs );
	}

	/**
	 * Sets service as default translation mode if there's a default Translation Service linked to this instance.
	 *
	 * @param bool $hasPreferredTranslationService
	 */
	public static function setDefaultTranslationMode( $hasPreferredTranslationService = false ) {
		if ( self::get( self::WHO_MODE, null ) === null ) {

			$defaultTranslationMode = $hasPreferredTranslationService ? 'service' : 'myself';
			self::setTranslationMode( [ $defaultTranslationMode ] );
		}
	}

	public static function setOnlyMyselfAsDefault() {
		if ( self::get( self::WHO_MODE, null ) === null ) {
			self::setTranslationMode( [ 'myself' ] );
		}
	}

	public static function setTranslationMode( array $mode ) {
		self::set( self::WHO_MODE, $mode );
	}

	public static function getTranslationMode() {
		return self::get( self::WHO_MODE, [] );
	}

	public static function setTranslateEverythingDefault() {
		if ( self::get( self::TRANSLATE_EVERYTHING, null ) === null ) {
			// Since 4.7 the Translate Everything option is 'false' by default until user sends some content to automatic translation
			self::setTranslateEverything( false );
		}
	}

	/**
	 * @param bool $default
	 *
	 * @return bool
	 */
	public static function shouldTranslateEverything( $default = false ) {
		return self::get( self::TRANSLATE_EVERYTHING, $default );
	}

	/** @param bool $state */
	public static function setTranslateEverything( $state ) {
		if ( self::isTMAllowed() ) {
			self::set( self::TRANSLATE_EVERYTHING, $state );
		} else {
			self::set( self::TRANSLATE_EVERYTHING, false );
		}
	}

	/**
	 * @param bool $state
	 *
	 * @return void
	 *
	 * @since 4.7
	 */
	public static function setHasTranslateEverythingBeenEverUsed( $state = false ) {
		self::set( self::HAS_TRANSLATE_EVERYTHING_BEEN_EVER_USED, $state );
	}

	/**
	 * @return bool
	 *
	 * @since 4.7
	 */
	public static function getHasTranslateEverythingBeenEverUsed() {
		return self::get( self::HAS_TRANSLATE_EVERYTHING_BEEN_EVER_USED, false );
	}

	/**
	 * @return bool
	 */
	public static function getTranslateEverything() {
		return self::get( self::TRANSLATE_EVERYTHING, false );
	}


	public static function isTMAllowed() {
		return self::get( self::TM_ALLOWED );
	}

	public static function setTMAllowed( $isTMAllowed ) {
		self::set( self::TM_ALLOWED, $isTMAllowed );
	}

	public static function setReviewMode( $mode ) {
		// Starting from WPML 4.7, review mode won't have a default value selected after user finishes the setup wizard
		// so, it can be set to NULL and then value can change when user sends content to automatic translation
		$allowedOptions = [ null, self::PUBLISH_AND_REVIEW, self::NO_REVIEW, self::HOLD_FOR_REVIEW ];
		if ( Lst::includes( $mode, $allowedOptions ) ) {
			self::set( self::REVIEW_MODE, $mode );
		}
	}

	public static function getReviewMode( $default = null ) {
		return self::get( self::REVIEW_MODE, $default );
	}

	public static function shouldBeReviewed() {
		return self::getReviewMode() !== self::NO_REVIEW;
	}

	/**
	 * @return LanguageMapping[]
	 */
	public static function getLanguageMappings() {
		return self::get( self::LANGUAGES_MAPPING, [] );
	}

	/**
	 * @param LanguageMapping $languageMapping
	 */
	public static function addLanguageMapping( LanguageMapping $languageMapping ) {
		self::set( self::LANGUAGES_MAPPING, Lst::append( $languageMapping, self::getLanguageMappings() ) );
	}

	private static function get( $key, $default = null ) {
		return ( new OptionManager() )->get( self::OPTION_GROUP, $key, $default );
	}

	private static function set( $key, $value ) {
		return ( new OptionManager() )->set( self::OPTION_GROUP, $key, $value );
	}

	/**
	 * @param bool $hasPreferredTranslationService
	 *
	 * @return bool
	 */
	public static function getTranslateEverythingDefaultInSetup( $hasPreferredTranslationService = false ) {
		if ( $hasPreferredTranslationService ) {
			return false;
		}

		return PostType::getPublishedCount( 'post' ) + PostType::getPublishedCount( 'page' ) > self::POSTS_LIMIT_FOR_AUTOMATIC_TRANSLATION
			? false
			: true;
	}


	/**
	 * @param array<string: string[]> $completed For example: { 'post': ['fr', 'de'], 'page': ['fr', 'de'] }
	 *
	 * @return void
	 */
	public static function setTranslateEverythingCompletedPosts( array $completed ) {
		self::set( self::TRANSLATE_EVERYTHING_POSTS, $completed );
	}

	/**
	 * @return array<string: string[]> For example: { 'post': ['fr', 'de'], 'page': ['fr', 'de'] }
	 */
	public static function getTranslateEverythingCompletedPosts(): array {
		return self::get( self::TRANSLATE_EVERYTHING_POSTS, [] );
	}

	/**
	 * @param array<string: string[]> $completed For example: { 'gravity_form': ['fr', 'de'], 'ninja_form': ['fr', 'de'] }
	 *
	 * @return void
	 */
	public static function setTranslateEverythingCompletedPackages( array $completed ) {
		self::set( self::TRANSLATE_EVERYTHING_PACKAGES_COMPLETED, $completed );
	}

	/**
	 * @return array<string: string[]> For example: { 'gravity_form': ['fr', 'de'], 'ninja_form': ['fr', 'de'] }
	 */
	public static function getTranslateEverythingCompletedPackages(): array {
		return self::get( self::TRANSLATE_EVERYTHING_PACKAGES_COMPLETED, [] );
	}

	/**
	 * @param array $completed For example ['fr', 'de']
	 *
	 * @return void
	 */
	public static function setTranslateEverythingCompletedStrings( array $completed ) {
		self::set( self::TRANSLATE_EVERYTHING_SRTINGS_COMPLETED, $completed );
	}

	/**
	 * @return array For example ['fr', 'de']
	 */
	public static function getTranslateEverythingCompletedStrings(): array {
		return self::get( self::TRANSLATE_EVERYTHING_SRTINGS_COMPLETED, [] );
	}

	/**
	 * @return int
  	 */
	public static function getTranslateEverythingDrafts() {
		return self::get( self::TRANSLATE_EVERYTHING_DRAFTS, 0 );
	}

	/**
	 * @param int $isActive
	 * @return void
	 */
	public static function setTranslateEverythingDrafts( $isActive ) {
		return self::set( self::TRANSLATE_EVERYTHING_DRAFTS, $isActive );
	}
}
