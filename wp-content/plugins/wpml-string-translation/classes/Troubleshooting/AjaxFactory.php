<?php

namespace WPML\ST\Troubleshooting;

use WPML\ST\MO\Generate\MultiSite\Executor;
use WPML\ST\MO\Scan\UI\Factory;
use function WPML\Container\make;
use WPML\ST\MO\Generate\Process\Status;
use WPML\ST\Troubleshooting\Cleanup\Database;

class AjaxFactory implements \IWPML_AJAX_Action_Loader {

	const ACTION_SHOW_GENERATE_DIALOG = 'wpml_st_mo_generate_show_dialog';
	const ACTION_CLEANUP              = 'wpml_st_troubleshooting_cleanup';

	public function create() {
		return self::getActions()->map( self::buildHandler() )->toArray();
	}

	/**
	 * @return \WPML\Collect\Support\Collection
	 */
	public static function getActions() {
		return wpml_collect(
			[
				[ self::ACTION_SHOW_GENERATE_DIALOG, [ self::class, 'showGenerateDialog' ] ],
				[ self::ACTION_CLEANUP, [ self::class, 'cleanup' ] ],
			]
		);
	}

	/**
	 * @return \Closure
	 */
	public static function buildHandler() {
		return function( array $action ) {
			return new RequestHandle( ...$action );
		};
	}

	/**
	 * @throws \WPML\Auryn\InjectionException
	 */
	public static function showGenerateDialog() {
		if ( is_super_admin() && is_multisite() ) {
			( new Executor() )->executeWith(
				Executor::MAIN_SITE_ID,
				function () {
					make( Status::class )->markIncompleteForAll();
				}
			);
		} else {
			make( Status::class )->markIncomplete();
		}

		Factory::ignoreWpmlVersion();
	}

	/**
	 * @throws \WPML\Auryn\InjectionException
	 */
	public static function cleanup() {
		/** @var Database $database */
		$database = make( Database::class );
		$database->deleteStringsFromImportedMoFiles();
		$database->truncatePagesAndUrls();
	}
}
