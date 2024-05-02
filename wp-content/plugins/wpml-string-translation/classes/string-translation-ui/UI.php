<?php

namespace WPML\ST\Main;

use WPML\Element\API\Languages;
use WPML\FP\Relation;
use WPML\ST\Main\Ajax\FetchCompletedStrings;
use WPML\ST\Main\Ajax\SaveTranslation;
use WPML\ST\WP\App\Resources;
use WPML\LIB\WP\Hooks as WPHooks;

class UI implements \IWPML_Backend_Action_Loader {

	/**
	 * @return callable|null
	 */
	public function create() {
		$isAdminTextsPage = isset( $_GET['trop'] );

		if ( Relation::propEq( 'page', WPML_ST_FOLDER . '/menu/string-translation.php', $_GET ) && ! $isAdminTextsPage ) {

			return function () {
				WPHooks::onAction( 'admin_enqueue_scripts' )
				       ->then( [ self::class, 'localize' ] )
				       ->then( Resources::enqueueApp( 'main-ui' ) );
			};
		} else {
			return null;
		}
	}

	public static function localize() {
		/** @var array $languages */
		$languages = Languages::withFlags( Languages::getAll() );
		return [
			'name' => 'wpml_st_main_ui',
			'data' => [
				'defaultLang'     => Languages::getDefaultCode(),
				'languageDetails' => Languages::withRtl( $languages ),
				'endpoints'       => [
					'saveTranslation'   => SaveTranslation::class,
					'translationMemory' => apply_filters( 'wpml_st_translation_memory_endpoint', '' ),
					'fetchStrings'      => FetchCompletedStrings::class,
				],
			],
		];
	}
}
