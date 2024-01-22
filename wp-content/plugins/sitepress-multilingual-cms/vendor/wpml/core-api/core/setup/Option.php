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
	const TRANSLATE_EVERYTHING_COMPLETED = 'translate-everything-completed';
	const TRANSLATE_EVERYTHING_IS_PAUSED = 'translate-everything-is-paused';
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
			self::setTranslateEverything( self::getTranslateEverythingDefaultInSetup() );
		}
	}

	public static function shouldTranslateEverything( $default = false ) {
		return self::get( self::TRANSLATE_EVERYTHING, $default );
	}

	/** @param bool $state */
	public static function setTranslateEverything( $state ) {
		self::set( self::TRANSLATE_EVERYTHING, $state );
	}

	/**
	 * @return bool
	 */
	public static function isPausedTranslateEverything() {
		return self::get( self::TRANSLATE_EVERYTHING_IS_PAUSED, false );
	}

	/** @param bool $state */
	public static function setIsPausedTranslateEverything( $state ) {
		self::set( self::TRANSLATE_EVERYTHING_IS_PAUSED, (bool) $state );
	}

	/**
	 * @return bool
	 */
	public static function getTranslateEverything() {
		return self::get( self::TRANSLATE_EVERYTHING, false );
	}

	public static function setTranslateEverythingCompleted( $completed ) {
		self::set( self::TRANSLATE_EVERYTHING_COMPLETED, $completed );
	}

	public static function markPostTypeAsCompleted( $postType, $languages ) {
		$completed              = self::getTranslateEverythingCompleted();
		$completed[ $postType ] = $languages;

		self::setTranslateEverythingCompleted( $completed );
	}

	public static function removePostTypeFromCompleted( $postType ) {
		$completed = self::getTranslateEverythingCompleted();
		unset( $completed[ $postType ] );

		self::setTranslateEverythingCompleted( $completed );
	}

	public static function removeLanguageFromCompleted( $language ) {
		$removeLanguage = Fns::map( Fns::reject( Relation::equals( $language ) ) );

		self::setTranslateEverythingCompleted( $removeLanguage( self::getTranslateEverythingCompleted() ) );
	}

	public static function getTranslateEverythingCompleted() {
		return self::get( self::TRANSLATE_EVERYTHING_COMPLETED, [] );
	}

	public static function isTMAllowed() {
		return self::get( self::TM_ALLOWED );
	}

	public static function setTMAllowed( $isTMAllowed ) {
		self::set( self::TM_ALLOWED, $isTMAllowed );
	}

	public static function setReviewMode( $mode ) {
		$allowedOptions = [ self::PUBLISH_AND_REVIEW, self::NO_REVIEW, self::HOLD_FOR_REVIEW ];
		if ( Lst::includes( $mode, $allowedOptions ) ) {
			self::set( self::REVIEW_MODE, $mode );
		}
	}

	public static function getReviewMode( $default = self::HOLD_FOR_REVIEW ) {
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
	public static function addLanguageMapping( LanguageMapping $languageMapping) {
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
}
