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
	 * @throws \WPML\Auryn\InjectionException
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

		/** @var Hooks $st_gettext_hooks */
		$st_gettext_hooks = make( Hooks::class );
		$st_gettext_hooks->clearFilters();

		foreach ( $filters as $filter ) {
			$st_gettext_hooks->addFilter( $filter );
		}

		return $st_gettext_hooks;
	}

	/**
	 * @return Filters\IFilter[]
	 * @throws \WPML\Auryn\InjectionException
	 */
	private function getFilters() {
		$filters = [];

		if ( $this->isHighlightingStrings() ) {
			$filters[] = make( Filters\StringHighlighting::class );
		}

		return $filters;
	}

	/**
	 * @return bool
	 */
	private function isHighlightingStrings() {
		return isset( $_GET[ self::TRACK_PARAM_TEXT ], $_GET[ self::TRACK_PARAM_DOMAIN ] );
	}
}
