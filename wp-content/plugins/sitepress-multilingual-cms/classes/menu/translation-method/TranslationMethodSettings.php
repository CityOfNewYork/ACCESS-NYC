<?php

namespace WPML\TM\Menu\TranslationMethod;

use WPML\API\PostTypes;
use WPML\API\Settings;
use WPML\DocPage;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use WPML\TranslationRoles\UI\Initializer as TranslationRolesInitializer;
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
use WPML\Core\WP\App\Resources;
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

	public static function localize()
	{
		$getPostTypeName = function ($postType) {
			return PostType::getPluralName($postType)->getOrElse($postType);
		};

		$editor = (string)Settings::pathOr(ICL_TM_TMETHOD_MANUAL, ['translation-management', 'doc_translation_method']);

		return [
			'name' => 'wpml_translation_method',
			'data' => [
				'mode' => self::getModeSettingsData(),
				'languages' => TranslationRolesInitializer::getLanguagesData(),
				'translateEverything' => Option::shouldTranslateEverything(),
				'reviewMode' => Option::getReviewMode(),
				'endpoints' => Lst::concat(
					[
						'setTranslateEverything'    => SetTranslateEverything::class,
						'untranslatedCount'         => UntranslatedCount::class,
					],
					TranslationRolesInitializer::getEndPoints()
				),
				'urls' => [
					'tmDashboard' => UIPage::getTMDashboard(),
					'translateAutomaticallyDoc'  => DocPage::getTranslateAutomatically(),
					'translatorsTabLink'         => UIPage::getTMTranslators(),
				],
				'disableTranslateEverything' => (bool)Obj::prop('disable_translate_everything', $_GET),
				'hasSubscription' => Account::isAbleToTranslateAutomatically(),
				'createAccountLink' => UIPage::getTMATE() . '&widget_action=wpml_signup',
				'translateAutomaticallyDoc' => DocPage::getTranslateAutomatically(),
				'postTypes' => Fns::map($getPostTypeName, PostTypes::getAutomaticTranslatable()),
				'hasTranslationService' => ActiveServiceRepository::get() !== null,
				'translatorsTabLink' => UIPage::getTMTranslators(),
				'hasJobsInProgress' => count(Jobs::getJobsToSync()),
				'isTMAllowed' => \WPML\Setup\Option::isTMAllowed(),
				'isClassicEditor' => Lst::includes($editor, [
					(string)ICL_TM_TMETHOD_EDITOR,
					(string)ICL_TM_TMETHOD_MANUAL
				]),
				'translationRoles' => TranslationRolesInitializer::getTranslationData( null, false ),
			],
		];
	}

	/**
	 * @return array
	 */
	public static function getModeSettingsData() {
		$defaultServiceName = self::getDefaultTranslationServiceName();
		Option::setDefaultTranslationMode( ! empty( $defaultServiceName ) );
		$translationMethod = null;

		// User selected translation method.
		$userSelectedTranslationMethod = Option::shouldTranslateEverything( 'unknown' );

		if ( true === $userSelectedTranslationMethod ) {
			// User selected Translate Everything.
			$translationMethod = 'automatic';
		} elseif ( false === $userSelectedTranslationMethod ) {
			// User selected Translate Some.
			$translationMethod = 'manual';
		} else {
			// No user selection.
			if ( ! empty( $defaultServiceName ) ) {
				// Pre-select "Translate Some" if a Translation Service is defined.
				$translationMethod = 'manual';
			}
		}

		return [
			'whoModes'           => Option::getTranslationMode(),
			'defaultServiceName' => $defaultServiceName,
			'method'             => $translationMethod,
			'reviewMode'         => Option::getReviewMode(),
			'isTMAllowed' => true,
		];
	}

	public static function render() {
		echo '<div id="translation-method-settings"></div>';
	}

	/**
	 * Get the actual service name, or empty string if there's no default service.
	 *
	 * @return string
	 */
	private static function getDefaultTranslationServiceName() {
		return Maybe::fromNullable( \TranslationProxy::get_tp_default_suid() )
			->map( [ \TranslationProxy_Service::class, 'get_service_by_suid'] )
			->map( Obj::prop('name') )
			->getOrElse('');
	}
}
