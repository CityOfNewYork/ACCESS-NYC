<?php

namespace WPML\Languages;

use WPML\API\PostTypes;
use WPML\Core\WP\App\Resources;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\PostType;
use WPML\Posts\CountPerPostType;
use WPML\Posts\UntranslatedCount;
use WPML\Setup\Option;
use WPML\TM\ATE\AutoTranslate\Endpoint\ActivateLanguage;
use WPML\TM\ATE\AutoTranslate\Endpoint\CheckLanguageSupport;
use WPML\UIPage;
use function WPML\FP\partial;

class UI implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( UIPage::isLanguages( $_GET ) ) {

			if ( self::isEditLanguagePage() ) {
				Hooks::onAction( 'admin_enqueue_scripts' )
				     ->then( Fns::unary( partial( [ self::class, 'getData' ], true ) ) )
				     ->then( Resources::enqueueApp( 'languages' ) );
			} else {
				Hooks::onAction( 'admin_enqueue_scripts' )
				     ->then( Fns::unary( partial( [ self::class, 'getData' ], false ) ) )
				     ->then( Resources::enqueueApp( 'languages' ) );
			}

		}
	}

	public static function getData( $editPage ) {
		$getPostTypeName = function ( $postType ) {
			return PostType::getPluralName( $postType )->getOrElse( $postType );
		};

		$data = [
			'endpoints' => [
				'checkSupportOfLanguages'          => CheckLanguageSupport::class,
				'skipTranslationOfExistingContent' => ActivateLanguage::class,
				'postsToTranslatePerTypeCount'     => CountPerPostType::class,
			],
			'postTypes' => Fns::map( $getPostTypeName, PostTypes::getAutomaticTranslatable() ),
			'shouldTranslateEverything' => Option::shouldTranslateEverything(),
		];

		if ( $editPage ) {
			$existingLanguages = Obj::values( Fns::map( function ( $language ) {
				return Lst::concat( $language, [
					'mapping' => [
						'targetId'   => Obj::pathOr( '', [ 'mapping', 'targetId' ], $language ),
						'targetCode' => Obj::pathOr( '', [ 'mapping', 'targetCode' ], $language ),
					]
				] );
			}, \SitePress_EditLanguages::get_active_languages() ) );

			$data = Lst::concat( $data, [ 'existingLanguages' => $existingLanguages ] );
		} else {
			$data = Lst::concat( $data, [
				'existingLangs'             => Languages::getSecondaryCodes(),
				'defaultLang'               => Languages::getDefaultCode(),
				'settingsUrl'               => admin_url( UIPage::getSettings() ),
			] );
		}

		return [ 'name' => 'wpmlLanguagesUI', 'data' => $data ];
	}


	private static function isEditLanguagePage() {
		return (int) Obj::prop( 'trop', $_GET ) === 1;
	}
}
