<?php

namespace ACFML\Upgrade\Commands;

use ACFML\FieldGroup\Mode;
use ACFML\Options;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\pipe;

class MigrateToV2 implements Command {

	const KEY = 'migrate-to-v2';

	const STATUS_FRESH            = 'fresh';
	const STATUS_OLD              = 'old';
	const STATUS_OLD_AND_NOTIFIED = 'notified'; // When the tooltip has been shown on a new field group.

	public static function run() {
		Hooks::onAction( 'wp_loaded' )
			->then( function() {
				if ( null === Options::get( self::KEY ) ) {
					$result = self::hasGroupWithFieldPreferenceAndNoMode() ? self::STATUS_OLD : self::STATUS_FRESH;

					if ( self::STATUS_OLD === $result ) {
						$setExpertModeOnGroup  = pipe( Obj::assoc( Mode::KEY, Mode::ADVANCED ), 'acf_update_field_group' );
						$hasGroupWithInvalidId = Relation::propEq( 'ID', 0 );

						wpml_collect( acf_get_field_groups() )->reject( $hasGroupWithInvalidId )->each( $setExpertModeOnGroup );
					}

					Options::set( self::KEY, $result );
				}
			} );
	}

	/**
	 * @return bool
	 */
	private static function hasGroupWithFieldPreferenceAndNoMode() {
		// $hasNoGroupMode :: array -> bool
		$hasNoGroupMode = Logic::complement( Obj::has( Mode::KEY ) );

		// $hasOneFieldPreference :: array -> bool
		$hasOneFieldPreference = function( $group ) {
			return wpml_collect( acf_get_fields( $group ) )->first( Obj::has( 'wpml_cf_preferences' ) );
		};

		// $hasGroupRequirements :: array -> bool
		$hasGroupRequirements = Logic::allPass( [
			$hasNoGroupMode,
			$hasOneFieldPreference,
		] );

		return (bool) wpml_collect( acf_get_field_groups() )->first( $hasGroupRequirements );
	}

	/**
	 * We'll inform the user once about the new mode
	 * when he creates a new field group on an old site.
	 *
	 * @return bool
	 */
	public static function needsNotification() {
		if ( self::STATUS_OLD === Options::get( self::KEY ) ) {
			Options::set( self::KEY, self::STATUS_OLD_AND_NOTIFIED );
			return true;
		}

		return false;
	}
}
