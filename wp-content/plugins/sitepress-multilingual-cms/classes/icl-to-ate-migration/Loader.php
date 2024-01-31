<?php

namespace WPML\ICLToATEMigration;

use WPML\Element\API\Languages;
use WPML\FP\Obj;
use WPML\ICLToATEMigration\Endpoints\AuthenticateICL;
use WPML\ICLToATEMigration\Endpoints\DeactivateICL;
use WPML\ICLToATEMigration\Endpoints\TranslationMemory\CheckMigrationStatus;
use WPML\ICLToATEMigration\Endpoints\TranslationMemory\StartMigration;
use WPML\ICLToATEMigration\Endpoints\Translators\GetFromICL;
use WPML\ICLToATEMigration\Endpoints\Translators\Save;
use WPML\LIB\WP\Hooks;
use WPML\Core\WP\App\Resources;
use WPML\UIPage;
use function WPML\Container\make;

class Loader implements \IWPML_Backend_Action {

	const ICL_NAME = 'ICanLocalize';

	public function add_hooks() {
		if ( self::shouldShowMigration() ) {
			Hooks::onAction( 'wp_loaded' )
				 ->then( [ self::class, 'getData' ] )
				 ->then( Resources::enqueueApp( 'icl-to-ate-migration' ) );
		}
	}

	/**
	 * @return bool
	 */
	public static function shouldShowMigration() {
		// TODO: Remove wpml_is_ajax condition once wpmltm-4351 is done.
		// phpcs:disable

		// This feature is disabled by default now. See wpmldev-857.
		if ( ! defined( 'WPML_ICL_ATE_MIGRATION_ENABLED' ) || ! WPML_ICL_ATE_MIGRATION_ENABLED ) {
			return false;
		}
		return ! wpml_is_ajax() && UIPage::isTroubleshooting( $_GET ) &&
		       ( isset( $_GET['icl-to-ate'] ) || make(ICLStatus::class)->isActivatedAndAuthorized() || Data::isICLDeactivated() );
		// phpcs:enable
	}

	public static function renderContainerIfNeeded() {
		if ( self::shouldShowMigration() ) {
			return '<div id="wpml-icl-to-ate-migration"></div>';
		}

		return '';
	}

	public static function getData() {
		$originalLanguageCode = Languages::getDefaultCode();
		$userLanguageCode     = Languages::getUserLanguageCode()->getOrElse( $originalLanguageCode );
		$languages            = Languages::withFlags( Languages::getAll( $userLanguageCode ) );

		return [
			'name' => 'wpmlIclToAteMigration',
			'data' => [
				'endpoints'      => [
					'GetTranslatorsFromICL'                => GetFromICL::class,
					'StartImportTranslationMemory'         => StartMigration::class,
					'CheckImportTranslationMemoryProgress' => CheckMigrationStatus::class,
					'SaveTranslators'                      => Save::class,
					'AuthenticateICL'                      => AuthenticateICL::class,
					'DeactivateICL'                        => DeactivateICL::class,
				],
				'languages'      => [
					'list'        => $languages ? Obj::values( $languages ) : [],
					'secondaries' => Languages::getSecondaryCodes(),
					'original'    => $originalLanguageCode,
				],
				'isICLActive'    => make( ICLStatus::class )->isActivatedAndAuthorized(),
				'migrationsDone' => [
					'memory' => Data::isMemoryMigrated(),
				],
				'ICLDeactivated' => Data::isICLDeactivated(),
			],
		];
	}
}

