<?php

namespace WPML\TM\Menu\TranslationMethod;

use WPML\API\PostTypes;
use WPML\API\Settings;
use WPML\DocPage;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\FP\partial;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Nonce;
use WPML\LIB\WP\PostType;
use WPML\Posts\UntranslatedCount;
use WPML\TranslationMode\Endpoint\SetTranslateEverything;
use WPML\Setup\Option;
use WPML\TM\API\ATE\Account;
use WPML\TM\ATE\Jobs;
use WPML\TM\Menu\TranslationServices\ActiveServiceRepository;
use WPML\TM\WP\App\Resources;
use WPML\UIPage;

class TranslationMethodSettings {

	public static function addHooks() {
		if ( UIPage::isMainSettingsTab( $_GET ) ) {
			Hooks::onAction( 'admin_enqueue_scripts' )
			     ->then( [ self::class, 'localize' ] )
			     ->then( Resources::enqueueApp( 'translation-method' ) );

			if ( Obj::prop( 'disable_translate_everything', $_GET ) ) {
				Hooks::onAction( 'wp_loaded' )
				     ->then( Fns::tap( partial( [ Option::class, 'setTranslateEverything' ], false ) ) )
				     ->then( Fns::tap( partial( 'do_action', 'wpml_set_translate_everything', false ) ) );
			}
		}
	}

	public static function localize() {
		$getPostTypeName = function ( $postType ) {
			return PostType::getPluralName( $postType )->getOrElse( $postType );
		};

		$editor = (string) Settings::pathOr( ICL_TM_TMETHOD_MANUAL, [ 'translation-management', 'doc_translation_method' ] );

		return [
			'name' => 'wpml_translation_method',
			'data' => [
				'translateEverything'        => Option::shouldTranslateEverything(),
				'reviewMode'                 => Option::getReviewMode(),
				'endpoints'                  => [
					'setTranslateEverything' => SetTranslateEverything::class,
					'untranslatedCount'      => UntranslatedCount::class,
				],
				'urls'                       => [
					'tmDashboard' => UIPage::getTMDashboard(),
				],
				'disableTranslateEverything' => (bool) Obj::prop( 'disable_translate_everything', $_GET ),
				'hasSubscription'            => Account::isAbleToTranslateAutomatically(),
				'createAccountLink'          => UIPage::getTMATE() . '&widget_action=wpml_signup',
				'translateAutomaticallyDoc'  => DocPage::getTranslateAutomatically(),
				'postTypes'                  => Fns::map( $getPostTypeName, PostTypes::getAutomaticTranslatable() ),
				'hasTranslationService'      => ActiveServiceRepository::get() !== null,
				'translatorsTabLink'         => UIPage::getTMTranslators(),
				'hasJobsInProgress'          => count( Jobs::getJobsToSync() ),
				'isClassicEditor'            => Lst::includes( $editor, [
					(string) ICL_TM_TMETHOD_EDITOR,
					(string) ICL_TM_TMETHOD_MANUAL
				] ),
			],
		];
	}

	public static function render() {
		echo '<div id="translation-method-settings"></div>';
	}
}
