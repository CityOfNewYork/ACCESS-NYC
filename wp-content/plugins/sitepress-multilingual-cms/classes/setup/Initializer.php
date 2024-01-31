<?php

namespace WPML\Setup;

use WPML\Ajax\Endpoint\Upload;
use WPML\Core\LanguageNegotiation;
use WPML\Core\WP\App\Resources;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Wrapper;
use WPML\LIB\WP\Option as WPOption;
use WPML\LIB\WP\User;
use WPML\Setup\Endpoint\CheckTMAllowed;
use WPML\Setup\Endpoint\CurrentStep;
use WPML\TM\ATE\TranslateEverything\Pause\View as PauseTranslateEverything;
use WPML\TM\ATE\TranslateEverything\TranslatableData\View as TranslatableData;
use WPML\TM\ATE\TranslateEverything\TranslatableData\DataPreSetup;

use WPML\TM\Menu\TranslationMethod\TranslationMethodSettings;
use WPML\TranslationMode\Endpoint\SetTranslateEverything;
use WPML\TranslationRoles\UI\Initializer as TranslationRolesInitializer;
use WPML\UIPage;
use function WPML\Container\make;

class Initializer {
	public static function loadJS() {
		Wrapper::of( self::getData() )->map( Resources::enqueueApp( 'setup' ) );
	}

	public static function getData() {
		$currentStep = Option::getCurrentStep();

		if ( CurrentStep::STEP_HIGH_COSTS_WARNING === $currentStep ) {
			// The user stopped the wizard on the high costs warning step.
			// In this case we need to start the wizard one step before.
			$currentStep = CurrentStep::STEP_TRANSLATION_SETTINGS;
		}

		$siteUrl = self::getSiteUrl();


		$defaultLang  = self::getDefaultLang();
		$originalLang = Option::getOriginalLang();
		if ( ! $originalLang ) {
			$originalLang = $defaultLang;
			Option::setOriginalLang( $originalLang );
		}

		$userLang = Languages::getUserLanguageCode()->getOrElse( $defaultLang );

		if ( defined( 'OTGS_INSTALLER_SITE_KEY_WPML' ) ) {
			self::savePredefinedSiteKey( OTGS_INSTALLER_SITE_KEY_WPML );
		}

		return [
			'name' => 'wpml_wizard',
			'data' => [
				'currentStep'          => $currentStep,
				'endpoints'            => Lst::concat( [
					'setOriginalLanguage'    => Endpoint\SetOriginalLanguage::class,
					'setSupport'             => Endpoint\SetSupport::class,
					'setSecondaryLanguages'  => Endpoint\SetSecondaryLanguages::class,
					'currentStep'            => Endpoint\CurrentStep::class,
					'addressStep'            => Endpoint\AddressStep::class,
					'licenseStep'            => Endpoint\LicenseStep::class,
					'translationStep'        => Endpoint\TranslationStep::class,
					'setTranslateEverything' => SetTranslateEverything::class,
					'pauseTranslateEverything' => PauseTranslateEverything::class,
					'recommendedPlugins'     => Endpoint\RecommendedPlugins::class,
					'finishStep'             => Endpoint\FinishStep::class,
					'addLanguages'           => Endpoint\AddLanguages::class,
					'upload'                 => Upload::class,
					'checkTMAllowed'         => CheckTMAllowed::class,
					'translatableData'         => TranslatableData::class,
				], TranslationRolesInitializer::getEndPoints() ),
				'languages'            => [
					'list'                  => Obj::values( Languages::withFlags( Languages::getAll( $userLang ) ) ),
					'secondaries'           => Fns::map( Languages::getLanguageDetails(), Option::getTranslationLangs() ),
					'original'              => Languages::getLanguageDetails( $originalLang ),
					'customFlagsDir'        => self::getCustomFlagsDir(),
					'predefinedFlagsDir'    => \WPML_Flags::get_wpml_flags_url(),
					'flagsByLocalesFileUrl' => \WPML_Flags::get_wpml_flags_by_locales_url(),
				],
				'siteAddUrl'           => 'https://wpml.org/account/sites/?add=' . urlencode( $siteUrl ) . '&wpml_version=' . self::getWPMLVersion(),
				'siteKey'              => self::getSiteKey(),
				'usePredefinedSiteKey' => self::isPredefinedSiteKeySaved(),
				'supportValue'         => \OTGS_Installer_WP_Share_Local_Components_Setting::get_setting( 'wpml' ),
				'address'              => [
					'siteUrl'       => $siteUrl,
					'mode'          => self::getLanguageNegotiationMode(),
					'domains'       => LanguageNegotiation::getDomains() ?: [],
					'gotUrlRewrite' => got_url_rewrite(),
				],
				'isTMAllowed'              => Option::isTMAllowed() === true,
				'isTMDisabled'             => Option::isTMAllowed() === false,
				'ateBaseUrl'               => self::getATEBaseUrl(),
				'whenFinishedUrlLanguages' => admin_url( UIPage::getLanguages() ),
				'whenFinishedUrlTM'        => admin_url( UIPage::getTM() ),
				'ateSignUpUrl'             => admin_url( UIPage::getTMATE() ),
				'languagesMenuUrl'         => admin_url( UIPage::getLanguages() ),
				'adminUserName'            => User::getCurrent()->display_name,
				'translation'              => Lst::concat(
					TranslationMethodSettings::getModeSettingsData(),
					TranslationRolesInitializer::getTranslationData( null, false )
				),

				'license'                  => [
					'actions'  => [
						'registerSiteKey' => Endpoint\LicenseStep::ACTION_REGISTER_SITE_KEY,
						'getSiteType'     => Endpoint\LicenseStep::ACTION_GET_SITE_TYPE,
					],
					'siteType' => [
						'production'  => \OTGS_Installer_Subscription::SITE_KEY_TYPE_PRODUCTION,
						'development' => \OTGS_Installer_Subscription::SITE_KEY_TYPE_DEVELOPMENT,
					],
				],

				'translatableData'         => [
					'actions' => [
						'listTranslatables' => TranslatableData::ACTION_LIST_TRANSLATABLES,
						'fetchData'         => TranslatableData::ACTION_FETCH_DATA,
					],
					'types'   => [
						'postTypes'  => DataPreSetup::KEY_POST_TYPES,
						'taxonomies' => DataPreSetup::KEY_TAXONOMIES,
					],
				],
			],
		];
	}

	/**
	 * @return bool
	 */
	private static function isPredefinedSiteKeySaved() {
		return function_exists( 'OTGS_Installer' )
		       && defined( 'OTGS_INSTALLER_SITE_KEY_WPML' )
		       && OTGS_INSTALLER_SITE_KEY_WPML
		       && OTGS_Installer()->get_site_key( 'wpml' ) === OTGS_INSTALLER_SITE_KEY_WPML;
	}

	/**
	 * @param string $siteKey
	 */
	private static function savePredefinedSiteKey( $siteKey ) {
		if ( function_exists( 'OTGS_Installer' ) ) {
			$args   = [
				'repository_id' => 'wpml',
				'nonce'         => wp_create_nonce( 'save_site_key_wpml' ),
				'site_key'      => $siteKey,
				'return'        => 1,
			];
			$result = OTGS_Installer()->save_site_key( $args );
			if ( empty( $result['error'] ) ) {
				icl_set_setting( 'site_key', $siteKey, true );
			}
		}
	}

	/**
	 * @return string
	 */
	private static function getLanguageNegotiationMode() {
		if (
			Option::getCurrentStep() === 'address'
			&& ! got_url_rewrite()
			&& LanguageNegotiation::getMode() === WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY

		) {
			return LanguageNegotiation::getModeAsString( WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER );
		}

		return LanguageNegotiation::getModeAsString();
	}

	/**
	 * @return string
	 */
	private static function getDefaultLang() {
		$getLangFromConstant = function () {
			global $sitepress;

			return Maybe::fromNullable( $sitepress->get_wp_api()->constant( 'WP_LANG' ) )
			            ->map( Languages::localeToCode() )
			            ->getOrElse( 'en' );
		};

		return Maybe::fromNullable( WPOption::getOr( 'WPLANG', null ) )
		            ->map( Languages::localeToCode() )
		            ->getOrElse( $getLangFromConstant );

	}

	private static function getCustomFlagsDir() {
		return sprintf( '%s/flags/', Obj::propOr( '', 'baseurl', wp_upload_dir() ) );
	}

	private static function getATEBaseUrl() {
		return make( \WPML_TM_ATE_AMS_Endpoints::class )->get_base_url( \WPML_TM_ATE_AMS_Endpoints::SERVICE_ATE );
	}

	/**
	 * @return string
	 */
	private static function getWPMLVersion() {
		return Obj::prop( 'Version', get_plugin_data( WPML_PLUGIN_PATH . '/' . WPML_PLUGIN_FILE ) );
	}

	/**
	 * @return string
	 */
	private static function getSiteKey() {
		$siteKey = wpml_get_setting( 'site_key', (string) OTGS_Installer()->get_site_key( 'wpml' ) );
		return is_string( $siteKey ) && strlen( $siteKey ) === 10 ? $siteKey : '';
	}

	private static function getSiteUrl() {
		return OTGS_Installer()->get_installer_site_url('wpml');
	}
}

