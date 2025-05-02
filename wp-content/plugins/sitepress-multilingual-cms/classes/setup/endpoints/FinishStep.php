<?php

namespace WPML\Setup\Endpoint;

use WPML\AdminLanguageSwitcher\AdminLanguageSwitcher;
use WPML\Ajax\IHandler;
use WPML\API\Settings;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\LIB\WP\User;
use WPML\TM\ATE\AutoTranslate\Endpoint\EnableATE;
use WPML\TranslationRoles\Service\AdministratorRoleManager;
use WPML\UrlHandling\WPLoginUrlConverter;
use function WPML\Container\make;
use WPML\FP\Lst;
use WPML\FP\Right;
use WPML\Setup\Option;
use WPML\TM\Menu\TranslationServices\Endpoints\Deactivate;
use WPML\TranslationMode\Endpoint\SetTranslateEverything;

class FinishStep implements IHandler {

	private $administratorRoleManager;

	public function __construct(
		AdministratorRoleManager $administratorRoleManager
	) {
		$this->administratorRoleManager = $administratorRoleManager;
	}

	public function run( Collection $data ) {
		// Prepare media setup which will run right after finishing WPML setup.
		\WPML\Media\Option::prepareSetup();

		$wpmlInstallation = wpml_get_setup_instance();
		$originalLanguage = Option::getOriginalLang();
		$wpmlInstallation->finish_step1( $originalLanguage );
		$wpmlInstallation->finish_step2( Lst::append( $originalLanguage, Option::getTranslationLangs() ) );
		$wpmlInstallation->finish_installation();

		self::enableFooterLanguageSwitcher();

		/**
		 * 1. Setting 'translateEverything = false' because starting from WPML 4.7, when user finishes wizard,
		 * he should have TranslateEverything paused initially.
		 *
		 * 2. Setting 'reviewMode = null' because starting from WPML 4.7, user should have NO default review mode selected,
		 * he'll need to select review mode when he sends content to automatic translation
		 *
		 * 3. Setting 'onlyNew = true' to resave TranslateEverything settings as now languages are activated, which happened on 'finish_step2'.
		 */
		make( SetTranslateEverything::class )->run(
			wpml_collect( [
				'translateEverything' => false,
				'reviewMode'          => null,
				'onlyNew'             => true
			] )
		);

		$translationMode = Option::getTranslationMode();
		if ( ! Lst::includes( 'users', $translationMode ) ) {
			make( \WPML_Translator_Records::class )->delete_all();
		}

		if ( ! Lst::includes( 'manager', $translationMode ) ) {
			make( \WPML_Translation_Manager_Records::class )->delete_all();
		}


		$this->administratorRoleManager->initializeAllAdministrators();

		if ( Option::isTMAllowed( ) ) {
			Option::setTranslateEverythingDefault();

			if ( ! Lst::includes( 'service', $translationMode ) ) {
				make( Deactivate::class )->run( wpml_collect( [] ) );
			}
			make( EnableATE::class )->run( wpml_collect( [] ) );
		} else {
			Option::setTranslateEverything( false );
		}

		WPLoginUrlConverter::enable( true );
		AdminLanguageSwitcher::enable();

		return Right::of( true );
	}

	private static function enableFooterLanguageSwitcher() {
		\WPML_Config::load_config_run();

		/** @var \WPML_LS_Settings $lsSettings */
		$lsSettings = make( \WPML_LS_Dependencies_Factory::class )->settings();

		$settings = $lsSettings->get_settings();
		$settings['statics']['footer']->set( 'show', true );

		$lsSettings->save_settings( $settings );
	}

}
