<?php

namespace WPML;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\curryN;

/**
 * Class UIPage
 * @package WPML
 *
 * @method static callback|bool isLanguages( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTranslationManagement( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTMDashboard( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTMBasket( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTMJobs( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTMTranslators( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTMATE( ...$get ) - Curried :: array → bool
 * @method static callback|bool isTroubleshooting( ...$get ) - Curried :: array → bool
 * @method static callback|bool isPage( ...$page, ...$get ) - Curried :: string → array → bool
 * @method static string getLanguages()
 * @method static string getTroubleshooting()
 * @method static string getTM()
 * @method static string getTMDashboard()
 * @method static string getTMATE()
 * @method static string getTMTranslators()
 * @method static string getTMJobs()
 * @method static string getTranslationQueue()
 */
class UIPage {

	const TM_PAGE = 'tm/menu/main.php';

	use Macroable;

	public static function init() {
		self::macro( 'isPage', Relation::propEq( 'page' ) );

		self::macro( 'isLanguages', self::isPage( WPML_PLUGIN_FOLDER . '/menu/languages.php' ) );

		self::macro( 'isTranslationManagement', self::isPage( self::TM_PAGE ) );

		self::macro( 'isTMDashboard', Logic::both(
			self::isTranslationManagement(),
			Logic::anyPass( [ Relation::propEq( 'sm', null ), Relation::propEq( 'sm', 'dashboard' ) ] )
		) );

		self::macro( 'isTMBasket', Logic::both( self::isTranslationManagement(), Relation::propEq( 'sm', 'basket' ) ) );

		self::macro( 'isTMJobs', Logic::both( self::isTranslationManagement(), Relation::propEq( 'sm', 'jobs' ) ) );

		self::macro( 'isTMTranslators', Logic::both( self::isTranslationManagement(), Relation::propEq( 'sm', 'translators' ) ) );

		self::macro( 'isTMATE', Logic::both( self::isTranslationManagement(), Relation::propEq( 'sm', 'ate-ams' ) ) );

		self::macro( 'isTroubleshooting', self::isPage( WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) );

		self::macro( 'getTM', Fns::always( 'admin.php?page=' . self::TM_PAGE ) );

		self::macro( 'getTMDashboard', Fns::always( self::getTM() . '&sm=dashboard' ) );

		self::macro( 'getTMATE', Fns::always( self::getTM() . '&sm=ate-ams' ) );

		self::macro( 'getTMTranslators', Fns::always( self::getTM() . '&sm=translators' ) );

		self::macro( 'getTMJobs', Fns::always( self::getTM() . '&sm=jobs' ) );

		self::macro( 'getLanguages', Fns::always( 'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/languages.php' ) );

		self::macro( 'getTranslationQueue', Fns::always( 'admin.php?page=tm/menu/translations-queue.php' ) );

		self::macro( 'getTroubleshooting', Fns::always( 'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) );

	}

	public static function isSettings( array $get = null ) {
		$isSettings = function ( $get ) {
			return defined( 'WPML_TM_FOLDER' )
				? self::isPage( WPML_TM_FOLDER . '/menu/settings', $get )
				: self::isPage( WPML_PLUGIN_FOLDER . '/menu/translation-options.php', $get );
		};

		return call_user_func_array( curryN( 1, $isSettings ), func_get_args() );
	}

	/**
	 * @param array|null $get
	 *
	 * @return bool
	 */
	public static function isMainSettingsTab( array $get = null ) {
		return self::isSettingTab( 'mcsetup', $get );
	}

	/**
	 * @param array|null $get
	 *
	 * @return bool
	 */
	public static function isNotificationSettingsTab( array $get = null ) {
		return self::isSettingTab( 'notifications', $get );
	}

	/**
	 * @param array|null $get
	 *
	 * @return bool
	 */
	public static function isCustomXMLConfigSettingsTab( array $get = null ) {
		return self::isSettingTab( 'custom-xml-config', $get );
	}


	/**
	 * @param string|null $tab
	 * @param array|null $get
	 *
	 * @return bool
	 */
	public static function isSettingTab( $tab = null, array $get = null ) {
		$fn = function ( $tab, $get ) {
			if ( self::isSettings( $get ) ) {
				return Obj::propOr( 'mcsetup', 'sm', $get ) === $tab;
			}

			return false;
		};

		return call_user_func_array( curryN( 2, $fn ), func_get_args() );
	}

	public static function getSettings() {
		$page = defined( 'WPML_TM_FOLDER' ) ? WPML_TM_FOLDER . '/menu/settings' : WPML_PLUGIN_FOLDER . '/menu/translation-options.php';
		return 'admin.php?page=' . $page;
	}

}

UIPage::init();

