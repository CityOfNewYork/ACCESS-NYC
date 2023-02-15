<?php

namespace WPML\TranslationRoles\UI;

use WPML\Core\WP\App\Resources;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Wrapper;
use WPML\LIB\WP\User;
use WPML\Setup\Option;
use WPML\TranslationRoles\FindAvailableByRole;
use WPML\TranslationRoles\GetManagerRecords;
use WPML\TranslationRoles\GetTranslatorRecords;
use WPML\TranslationRoles\RemoveManager;
use WPML\TranslationRoles\RemoveTranslator;
use WPML\TranslationRoles\SaveManager;
use WPML\TranslationRoles\SaveTranslator;
use function WPML\Container\make;
use function WPML\FP\pipe;

class Initializer {

	public static function loadJS() {
		if ( \WPML_TM_Admin_Sections::is_translation_roles_section() ) {
			Wrapper::of( self::getData() )->map( Resources::enqueueApp( 'translation-roles-ui' ) );
		}
	}

	public static function getData() {

		$originalLang = Obj::prop( 'code', Languages::getDefault() );
		$userLang     = Languages::getUserLanguageCode()->getOrElse( $originalLang );

		$secondaryCodes = pipe( Obj::without( $originalLang ), Obj::keys() );

		return [
			'name' => 'wpml_translation_roles_ui',
			'data' => [
				'endpoints'   => self::getEndPoints(),
				'languages'   => [
					'list'        => Obj::values( Languages::withFlags( Languages::getAll( $userLang ) ) ),
					'secondaries' => $secondaryCodes( Languages::getActive() ),
					'original'    => $originalLang,
				],
				'translation' => self::getTranslationData( User::withEditLink() ),
			]
		];
	}

	public static function getEndPoints() {
		return [
			'findAvailableByRole'  => FindAvailableByRole::class,
			'saveTranslator'       => SaveTranslator::class,
			'removeTranslator'     => RemoveTranslator::class,
			'getTranslatorRecords' => GetTranslatorRecords::class,
			'getManagerRecords'    => GetManagerRecords::class,
			'saveManager'          => SaveManager::class,
			'removeManager'        => RemoveManager::class,
		];
	}

	public static function getTranslationData( callable $userExtra = null ) {
		$currentUser = User::getCurrent();
		$service     = Option::isTMAllowed() ? \TranslationProxy::get_current_service() : null;

		return [
			'canManageOptions' => $currentUser->has_cap( 'manage_options' ),
			'adminUserName'    => $currentUser->display_name,
			'translators'      => Fns::map(
				User::withAvatar(),
				make( \WPML_Translator_Records::class )->get_users_with_capability()
			),
			'managers'         => self::getManagers( $userExtra ),
			'wpRoles'          => \WPML_WP_Roles::get_roles_up_to_user_level( $currentUser ),
			'managerRoles'     => self::getTranslationManagerRoles( $currentUser ),
			'service'          => ! is_wp_error( $service ) ? $service : null,
		];
	}

	/**
	 * @param \WP_User|null $currentUser
	 *
	 * @return array
	 */
	private static function getTranslationManagerRoles( $currentUser ) {
		$editorRoles        = wpml_collect( \WPML_WP_Roles::get_editor_roles() )
			->pluck( 'id' )
			->reject( Relation::equals( 'administrator' ) )
			->all();
		$filterManagerRoles = pipe( Obj::prop( 'id' ), Lst::includes( Fns::__, $editorRoles ) );

		return Fns::filter( $filterManagerRoles, \WPML_WP_Roles::get_roles_up_to_user_level( $currentUser ) );
	}

	/**
	 * @param callable $userExtra
	 *
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	private static function getManagers( callable $userExtra = null ) {
		$isAdministrator = pipe( Obj::prop( 'roles' ), Lst::includes( 'administrator' ) );

		return wpml_collect( make( \WPML_Translation_Manager_Records::class )->get_users_with_capability() )
			->reject( $isAdministrator )
			->map( pipe( User::withAvatar(), $userExtra ?: Fns::identity() ) )
			->values()
			->all();
	}
}
