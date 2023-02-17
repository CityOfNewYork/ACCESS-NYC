<?php

namespace WPML\ST\StringsCleanup;

use WPML\FP\Relation;
use WPML\ST\Gettext\AutoRegisterSettings;
use WPML\ST\StringsCleanup\Ajax\InitStringsRemoving;
use WPML\ST\StringsCleanup\Ajax\RemoveStringsFromDomains;
use WPML\ST\WP\App\Resources;
use WPML\LIB\WP\Hooks as WPHooks;

class UI implements \IWPML_Backend_Action_Loader {

	/**
	 * @return callable|null
	 */
	public function create() {
		if ( Relation::propEq( 'page', WPML_ST_FOLDER . '/menu/string-translation.php', $_GET ) ) {

			return function () {
				WPHooks::onAction( 'admin_enqueue_scripts' )
					   ->then( [ self::class, 'localize' ] )
					   ->then( Resources::enqueueApp( 'strings-cleanup' ) );
			};
		} else {
			return null;
		}
	}

	public static function localize() {
		$settings     = \WPML\Container\make( AutoRegisterSettings::class );
		$domains_data = $settings->getDomainsWithStringsTranslationData();

		return [
			'name' => 'wpml_strings_cleanup_ui',
			'data' => [
				'domains'   => $domains_data,
				'endpoints' => [
					'removeStringsFromDomains' => RemoveStringsFromDomains::class,
					'initStringsRemoving'      => InitStringsRemoving::class,
				],
			],
		];
	}
}
