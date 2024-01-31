<?php

namespace WPML\Element\API;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\FP\Nothing;
use WPML\FP\Just;
use WPML\LIB\WP\User;
use function WPML\FP\curryN;
use function WPML\FP\pipe;
use WPML\API\Settings;
use WPML\FP\Invoker\BeforeAfter;

/**
 * @method static callable|string getCodeByName( ...$name ) - Curried :: string->string
 *
 * It returns language code according to the given name in the current display language.
 *
 * eg. 'Französisch' in German will return 'fr'
 *
 * @method static array getActive()
 *
 * It returns an array of the active languages.
 *
 * The returned array is indexed by language code and every element has the following structure:
 * ```
 *  'fr' => [
 *      'code'           => 'fr',
 *      'id'             => 3,
 *      'english_name'   => 'French',
 *      'native_name'    => 'Français',
 *      'major'          => 1,
 *      'default_locale' => 'fr_FR',
 *      'encode_url'     => 0,
 *      'tag'            => 'fr ,
 *      'display_name'   => 'French
 *  ]
 * ```
 * @method static array getSecondaries()
 *
 * It returns an array of the secondary languages.
 *
 * The returned array is indexed by language code and every element has the following structure:
 * ```
 *  'fr' => [
 *      'code'           => 'fr',
 *      'id'             => 3,
 *      'english_name'   => 'French',
 *      'native_name'    => 'Français',
 *      'major'          => 1,
 *      'default_locale' => 'fr_FR',
 *      'encode_url'     => 0,
 *      'tag'            => 'fr ,
 *      'display_name'   => 'French
 *  ]
 * ```
 * @method static array getSecondaryCodes()
 *
 * It returns an array of the secondary language codes.
 *
 * @method static array|callback getLanguageDetails( ...$code ) - Curried :: string->array
 *
 * It returns details of a language.
 *
 * An example output:
 * ```
 * [
 *      'code'           => 'fr',
 *      'id'             => 3,
 *      'english_name'   => 'French',
 *      'native_name'    => 'Français',
 *      'major'          => 1,
 *      'default_locale' => 'fr_FR',
 *      'encode_url'     => 0,
 *      'tag'            => 'fr ,
 *      'display_name'   => 'French
 *  ]
 * ```
 *
 *
 * @method static array getDefault()
 *
 * It returns a default language details.
 *
 * An example output:
 *```
 *[
 *      'code'           => 'fr',
 *      'id'             => 3,
 *      'english_name'   => 'French',
 *      'native_name'    => 'Français',
 *      'major'          => 1,
 *      'default_locale' => 'fr_FR',
 *      'encode_url'     => 0,
 *      'tag'            => 'fr ,
 *      'display_name'   => 'French
 * ]
 *```
 *
 * @method static string getDefaultCode()
 *
 * It returns a default language code.
 *
 * @method static string getCurrentCode()
 *
 * It returns a current language code.
 *
 * @method static callable|string getFlagUrl( ...$code ) - Curried :: string → string
 *
 * Gets the flag url for the given language code.
 *
 * @method static callable|string getFlag( ...$code ) - Curried :: string → [string, bool]
 *
 * Returns flag url and from_template
 *
 * @method static callable|array withFlags( ...$langs ) - Curried :: [code => lang] → [code => lang]
 *
 * Adds the language flag url to the array of languages.
 *
 * @method static array getAll( $lang = false ) string|false → [lang]
 *
 * It returns an array of the all the languages.
 *
 * The returned array is indexed by language code and every element has the following structure:
 * ```
 *  'fr' => [
 *      'code'           => 'fr',
 *      'id'             => 3,
 *      'english_name'   => 'French',
 *      'native_name'    => 'Français',
 *      'major'          => 1,
 *      'default_locale' => 'fr_FR',
 *      'encode_url'     => 0,
 *      'tag'            => 'fr ,
 *      'display_name'   => 'French
 *  ]
 * ```
 *
 * @method static callable|int|false setLanguageTranslation( ...$langCode, ...$displayLangCode, ...$name ) - Curried :: string->string->string->int|false
 *
 * It sets a language translation.
 *
 * @method static callable|int|false setFlag( ...$langCode, ...$flag, ...$fromTemplate ) - Curried :: string->string->bool->int|false
 *
 * It sets a language flag.
 *
 * @method static callable|string getWPLocale( ...$langDetails ) - Curried :: array->string
 *
 * @method static callable|string downloadWPLocale( $locale ) - Curried :: string->string
 *
 * It attempts to download a WP language pack for a specific locale, stores the result in settings.
 */
class Languages {
	use Macroable;

	const LANGUAGES_MAPPING_OPTION = 'wpml_languages_mapping';

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'getCodeByName', curryN( 1, function ( $name ) {
			global $wpdb;

			$lang_code_query = "
				SELECT language_code
				FROM {$wpdb->prefix}icl_languages_translations
				WHERE name = %s AND display_language_code = %s
			";

			return $wpdb->get_var( $wpdb->prepare( $lang_code_query, $name, self::getCurrentCode() ) );
		} ) );

		self::macro( 'getActive', function () {
			global $sitepress;

			return self::withBuiltInInfo( $sitepress->get_active_languages() );
		} );

		self::macro( 'getLanguageDetails', curryN( 1, function ( $code ) {
			global $sitepress;

			return self::addBuiltInInfo( $sitepress->get_language_details( $code ) );
		} ) );

		self::macro( 'getDefaultCode', function () {
			global $sitepress;

			return $sitepress->get_default_language();
		} );

		self::macro('getCurrentCode', function() {
			global $sitepress;

			return $sitepress->get_current_language();
		});

		self::macro( 'getDefault', function() {
			$defaultCode = self::getDefaultCode();

			return $defaultCode ? self::getLanguageDetails( $defaultCode ) : null;
		} );

		self::macro(
			'getSecondaries',
			function() {
				$activeLanguages = self::getActive();

				return array_filter(
					$activeLanguages,
					Logic::complement( Relation::propEq( 'code', self::getDefaultCode() ) )
				);
			}
		);

		self::macro( 'getSecondaryCodes', pipe( [ self::class, 'getSecondaries' ], Lst::pluck( 'code' ), Obj::values() ) );

		self::macro( 'getAll', function ( $userLang = false ) {
			global $sitepress;

			return self::withBuiltInInfo(  $sitepress->get_languages( $userLang ) );
		} );

		self::macro( 'getFlagUrl', curryN( 1, function ( $code ) {
			global $sitepress;

			return $sitepress->get_flag_url( $code );
		} ) );

		self::macro( 'getFlag', curryN( 1, function ( $code ) {
			global $sitepress;

			return $sitepress->get_flag( $code );
		} ) );

		self::macro( 'withFlags', curryN( 1, function ( $langs ) {
			$addFlag = function ( $lang, $code ) {
				$flag = self::getFlag( $code );

				$lang['flag_url']           = self::getFlagUrl( $code );
				$lang['flag_from_template'] = Obj::prop( 'from_template', $flag );

				return $lang;
			};

			return Fns::map( $addFlag, $langs );
		} ) );

		self::macro( 'setLanguageTranslation', curryN( 3, function ( $langCode, $displayLangCode, $name ) {
			global $wpdb;

			$sql = "
				REPLACE INTO {$wpdb->prefix}icl_languages_translations (`language_code`, `display_language_code`, `name`) 
				VALUE (%s, %s, %s) 
			";

			return $wpdb->query( $wpdb->prepare( $sql, $langCode, $displayLangCode, $name ) ) ? $wpdb->insert_id : false;
		} ) );


		self::macro( 'setFlag', curryN( 3, function ( $langCode, $flag, $fromTemplate ) {
			global $wpdb;

			$sql = "
				REPLACE INTO {$wpdb->prefix}icl_flags (`lang_code`, `flag`, `from_template`)
				VALUES (%s, %s, %d)		
			";

			return $wpdb->query( $wpdb->prepare( $sql, $langCode, $flag, $fromTemplate ) ) ? $wpdb->insert_id : false;
		} ) );

		self::macro( 'getWPLocale', curryN( 1, function ( array $langDetails ) {
			return Logic::firstSatisfying( Logic::isTruthy(), [
				pipe( Obj::prop( 'default_locale' ), [ self::class, 'downloadWPLocale'] ),
				pipe( Obj::prop( 'tag' ), [ self::class, 'downloadWPLocale'] ),
				pipe( Obj::prop( 'code' ), [ self::class, 'downloadWPLocale'] ),
				Obj::prop( 'default_locale' ),
			], $langDetails );
		} ) );

		self::macro( 'downloadWPLocale', curryN( 1, function ( $locale ) {
			if ( ! function_exists( 'wp_download_language_pack' ) ) {
				require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			}

			$downloaded_locales = Settings::get( Settings::WPML_DOWNLOADED_LOCALES_KEY, [] );

			if ( ! $downloaded_locales || ! isset( $downloaded_locales[ $locale ] ) ) {

				$downloaded_locale = wp_download_language_pack( $locale );
				if ( false === $downloaded_locale ) {
					return false;
				}

				$downloaded_locales[ $locale ] = $downloaded_locale;
				Settings::setAndSave( Settings::WPML_DOWNLOADED_LOCALES_KEY, $downloaded_locales );
			}

			return $downloaded_locales[ $locale ];
		} ) );
	}

	/**
	 * Curried :: string → bool
	 * Determine if the language is Right to Left
	 *
	 * @param string|null $code
	 *
	 * @return callable|bool
	 */
	public static function isRtl( $code = null ) {
		$isRtl = function ( $code ) {
			global $sitepress;

			return $sitepress->is_rtl( $code );
		};

		return call_user_func_array( curryN( 1, $isRtl ), func_get_args() );
	}

	/**
	 * Curried :: [code => lang] → [code => lang]
	 *
	 * Adds language direction, right to left, to the languages data
	 *
	 * @param string[] $langs
	 *
	 * @return callable|mixed[]
	 */
	public static function withRtl( $langs = null ) {
		$withRtl = function ( $langs ) {
			$addRtl = function ( $lang, $code ) {
				$lang['rtl'] = self::isRtl( $code );

				return $lang;
			};

			return Fns::map( $addRtl, $langs );
		};

		return call_user_func_array( curryN( 1, $withRtl ), func_get_args() );
	}

	/**
	 * Curried :: string -> string|false
	 *
	 * Returns the language code given a locale
	 *
	 * @param string|null $locale
	 *
	 * @return callable|string|false
	 */
	public static function localeToCode( $locale = null ) {
		$localeToCode = function ( $locale ) {
			$allLangs = Obj::values( self::getAll() );

			$guessedCode = Maybe::of( $locale )
			                    ->map( Str::split( '_' ) )
			                    ->map( Lst::nth( 0 ) )
			                    ->filter( Lst::includes( Fns::__, Lst::pluck( 'code', $allLangs ) ) )
			                    ->getOrElse( false );

			$getByNonEmptyLocales = pipe( Fns::filter( Obj::prop( 'default_locale' ) ), Lst::keyBy( 'default_locale') );

			return Obj::pathOr(
				$guessedCode,
				[ $locale, 'code' ],
				$getByNonEmptyLocales( $allLangs )
			);
		};

		return call_user_func_array( curryN( 1, $localeToCode ), func_get_args() );
	}

	/**
	 * @param string $code
	 * @param string $english_name
	 * @param string $default_locale
	 * @param int    $major
	 * @param int    $active
	 * @param int    $encode_url
	 * @param string $tag
	 * @param string $country
	 *
	 * @return bool|int
	 */
	public static function add( $code, $english_name, $default_locale, $major = 0, $active = 0, $encode_url = 0, $tag = '', $country = null ) {
		global $wpdb;

		$languages     = self::getAll();
		$existingCodes = Obj::keys( $languages );

		$res = $wpdb->insert(
			$wpdb->prefix . 'icl_languages', [
				'code'           => $code,
				'english_name'   => $english_name,
				'default_locale' => $default_locale,
				'major'          => $major,
				'active'         => $active,
				'encode_url'     => $encode_url,
				'tag'            => $tag,
				'country'        => $country,
			]
		);

		if ( ! $res ) {
			return false;
		}

		$languageId = $wpdb->insert_id;
		$codes      = Lst::concat( $existingCodes, [ $code ] );

		Fns::map( self::setLanguageTranslation( $code, Fns::__, $english_name ), $codes );

		Fns::map( Fns::converge(
			self::setLanguageTranslation( Fns::__, $code, Fns::__ ),
			[ Obj::prop( 'code' ), Obj::prop( 'english_name' ) ]
		), $languages );

		return $languageId;
	}

	/**
	 * @return Just|Nothing
	 */
	public static function getUserLanguageCode() {
		return Maybe::fromNullable( User::getCurrent() )
		            ->map( function ( $user ) {
			            return $user->locale ?: null;
		            } )
		            ->map( self::localeToCode() );
	}

	public static function withBuiltInInfo( $languages ) {
		return Fns::map( [ self::class, 'addBuiltInInfo' ], $languages );
	}

	public static function addBuiltInInfo( $language ) {
		if ( $language ) {
			$builtInLanguageCodes = Obj::values( \icl_get_languages_codes() );
			$isBuiltIn            = pipe( Obj::prop( 'code' ), Lst::includes( Fns::__, $builtInLanguageCodes ) );

			return Obj::addProp( 'built_in', $isBuiltIn, $language );
		} else {
			return $language;
		}
	}

	/**
	 * It lets you run a function in a specific language.
	 *
	 * ```php
	 *  $result = Languages::whileInLanguage( 'de' )
	 *		->invoke( 'my_function' )
	 *		->runWith( 1, 2, 'some' );
	 * ```
	 *
	 * @param string $lang
	 *
	 * @return BeforeAfter
	 */
	public static function whileInLanguage( $lang ) {
		global $sitepress;
		$old_lang = null;
		$before = function() use ( &$old_lang, $sitepress, $lang ) {
			$old_lang = $sitepress->get_current_language();
			$sitepress->switch_lang( $lang );
		};
		$after = function() use ( $sitepress, &$old_lang ) {
			$sitepress->switch_lang( $old_lang );
		};
		return BeforeAfter::of( $before, $after );
	}
}

Languages::init();
