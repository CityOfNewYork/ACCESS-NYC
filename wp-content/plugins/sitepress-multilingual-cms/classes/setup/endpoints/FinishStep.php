<?php

namespace WPML\Setup\Endpoint;

use WPML\AdminLanguageSwitcher\AdminLanguageSwitcher;
use WPML\Ajax\IHandler;
use WPML\API\Settings;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\LIB\WP\User;
use WPML\TM\ATE\AutoTranslate\Endpoint\EnableATE;
use WPML\UrlHandling\WPLoginUrlConverter;
use function WPML\Container\make;
use WPML\FP\Lst;
use WPML\FP\Right;
use WPML\Setup\Option;
use WPML\TM\Menu\TranslationServices\Endpoints\Deactivate;
use WPML\TranslationMode\Endpoint\SetTranslateEverything;

class FinishStep implements IHandler {

	public function run( Collection $data ) {
		// Prepare media setup which will run right after finishing WPML setup.
		\WPML\Media\Option::prepareSetup();

		$wpmlInstallation = wpml_get_setup_instance();
		$originalLanguage = Option::getOriginalLang();
		$wpmlInstallation->finish_step1( $originalLanguage );
		$wpmlInstallation->finish_step2( Lst::append( $originalLanguage, Option::getTranslationLangs() ) );
		$wpmlInstallation->finish_installation();

		self::enableFooterLanguageSwitcher();

		if ( Option::isPausedTranslateEverything() ) {
			// Resave translate everything settings as now languages
			// are activated, which happened on 'finish_step2'.
			make( SetTranslateEverything::class )->run(
				wpml_collect( [ 'onlyNew' => true ] )
			);
		}

		$translationMode = Option::getTranslationMode();
		if ( ! Lst::includes( 'users', $translationMode ) ) {
			make( \WPML_Translator_Records::class )->delete_all();
		}

		if ( ! Lst::includes( 'manager', $translationMode ) ) {
			make( \WPML_Translation_Manager_Records::class )->delete_all();
		}


		if ( Lst::includes( 'myself', $translationMode ) ) {
			self::setCurrentUserToTranslateAllLangs();
		}

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

	private static function setCurrentUserToTranslateAllLangs() {
		$currentUser = User::getCurrent();
		$currentUser->add_cap( \WPML\LIB\WP\User::CAP_TRANSLATE );
		User::updateMeta( $currentUser->ID, \WPML_TM_Wizard_Options::ONLY_I_USER_META, true );

		make( \WPML_Language_Pair_Records::class )->store(
			$currentUser->ID,
			\WPML_All_Language_Pairs::get()
		);
	}
}
