<?php

namespace ACFML\Upgrade;

use ACFML\Options;
use WPML\Utilities\Lock;

/**
 * Commands will run in admin only,
 * and below a lock to prevent concurrent upgrades.
 *
 * Changing the class name for a command or
 * adding a new command will re-trigger the whole
 * upgrade process.
 *
 * Each upgrade command is responsible for holding
 * its own status. If a command should not be re-triggered,
 * it should be defined inside the command class.
 */
class Upgrade {

	const LOCK_NAME = 'acfml-upgrade';

	const KEY_LAST_MIGRATION_HASH = 'last-migration-hash';

	/**
	 * @return void
	 */
	public static function init() {
		if ( self::canUpgrade() && self::needsUpgrade() ) {
			Lock::whileLocked( self::LOCK_NAME, 2 * MINUTE_IN_SECONDS, [ __CLASS__, 'run' ] );
		}
	}

	/**
	 * @return bool
	 */
	private static function canUpgrade() {
		if ( wp_doing_ajax() ) {
			return false;
		}

		if ( wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}

		return is_admin();
	}

	/**
	 * @return bool
	 */
	private static function needsUpgrade() {
		return Options::get( self::KEY_LAST_MIGRATION_HASH ) !== CommandsProvider::getHash();
	}

	/**
	 * @return void
	 */
	public static function run() {
		CommandsProvider::get()
			->each( function( $commandClass ) {
				call_user_func( [ $commandClass, 'run' ] );
			} );

		Options::set( self::KEY_LAST_MIGRATION_HASH, CommandsProvider::getHash() );
	}
}
