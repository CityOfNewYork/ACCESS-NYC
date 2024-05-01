<?php


namespace WPML\Settings;

use WPML\API\PostTypes;
use WPML\Core\WP\App\Resources;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\LIB\WP\Hooks;
use WPML\Setup\Option;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetNumberOfPosts;
use WPML\TM\ATE\AutoTranslate\Endpoint\SetForPostType;
use WPML\UIPage;

class UI implements \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onAction( 'admin_enqueue_scripts' )
		     ->then( Fns::always( $_GET ) )
		     ->then( Logic::anyPass( [ [ UIPage::class, 'isMainSettingsTab' ], [ UIPage::class, 'isTroubleshooting' ] ] ) )
		     ->then( Either::fromBool() )
		     ->then( [ self::class, 'getData' ] )
		     ->then( Resources::enqueueApp( 'settings' ) );
	}

	public static function getData() {
		return [
			'name' => 'wpmlSettingsUI',
			'data' => [
				'endpoints'                 => [
					'getCount'     => GetNumberOfPosts::class,
					'setAutomatic' => SetForPostType::class,
				],
				'shouldTranslateEverything' => Option::shouldTranslateEverything(),
				'settingsUrl'               => admin_url( UIPage::getSettings() ),
				'existingPostTypes'         => PostTypes::getOnlyTranslatable(),
				'isTMLoaded'                => ! wpml_is_setup_complete() || Option::isTMAllowed(),
			]
		];
	}
}
