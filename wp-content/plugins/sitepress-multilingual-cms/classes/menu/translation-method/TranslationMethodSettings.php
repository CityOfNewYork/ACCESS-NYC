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
use function WPML\Container\make;
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
			if ( Obj::prop( 'disable_translate_everything', $_GET ) ) {
				Hooks::onAction( 'wp_loaded' )
				     ->then( Fns::tap( partial( [ Option::class, 'setTranslateEverything' ], false ) ) )
				     ->then( Fns::tap( partial( 'do_action', 'wpml_set_translate_everything', false ) ) );
			}
		}
	}


	/**
	 * @return array
	 */
	public static function getModeSettingsData() {
		$defaultServiceName = self::getDefaultTranslationServiceName();
		Option::setDefaultTranslationMode( ! empty( $defaultServiceName ) );

		return [
			'whoModes'           => Option::getTranslationMode(),
			'defaultServiceName' => $defaultServiceName,
			'reviewMode'         => Option::getReviewMode(),
			'isTMAllowed' => true,
		];
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
