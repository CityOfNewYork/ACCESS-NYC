<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\Gettext;

use function WPML\Container\make;
use WPML_ST_Upgrade;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	const TRACK_PARAM_TEXT   = 'icl_string_track_value';
	const TRACK_PARAM_DOMAIN = 'icl_string_track_context';

	/**
	 * @return \IWPML_Action|Hooks|null
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		/**
		 * @deprecated this global should not be used anymore.
		 *
		 * @var Hooks $st_gettext_hooks
		 */
		global $st_gettext_hooks;

		$st_gettext_hooks = null;

		$filters = $this->getFilters();

		if ( ! $filters ) {
			return $st_gettext_hooks;
		}

		$st_upgrade = make( WPML_ST_Upgrade::class );
		// todo: Remove this condition because we won't apply this command anymore (was to add string pages and urls tables)
		if ( $st_upgrade->has_command_been_executed( 'WPML_ST_Upgrade_Db_Cache_Command' ) ) {
			/** @var Hooks $st_gettext_hooks */
			$st_gettext_hooks = make( Hooks::class );
			$st_gettext_hooks->clearFilters();

			foreach ( $filters as $filter ) {
				$st_gettext_hooks->addFilter( $filter );
			}
		}

		return $st_gettext_hooks;
	}

	/**
	 * @return Filters\IFilter[]
	 * @throws \Auryn\InjectionException
	 */
	private function getFilters() {
		$filters = [];

		/** @var Settings $settings */
		$settings = make( Settings::class );

		if ( $settings->isAutoRegistrationEnabled() ) {
			$filters[] = make( Filters\StringTranslation::class );
		}

		if ( $this->isTrackingStrings( $settings ) ) {
			$filters[] = make( Filters\StringTracking::class );
		}

		if ( $this->isHighlightingStrings() ) {
			$filters[] = make( Filters\StringHighlighting::class );
		}

		return $filters;
	}

	/**
	 * @param Settings $settings
	 *
	 * @return bool
	 */
	private function isTrackingStrings( Settings $settings ) {
		return $settings->isTrackStringsEnabled()
		       && current_user_can( 'edit_others_posts' )
		       && ! is_admin();
	}

	/**
	 * @return bool
	 */
	private function isHighlightingStrings() {
		return isset( $_GET[ self::TRACK_PARAM_TEXT ], $_GET[ self::TRACK_PARAM_DOMAIN ] );
	}
}
