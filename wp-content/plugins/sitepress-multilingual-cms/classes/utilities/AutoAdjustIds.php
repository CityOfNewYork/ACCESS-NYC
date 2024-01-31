<?php

namespace WPML\Utils;

use SitePress;
use WPML_WP_API;

class AutoAdjustIds {
	const WITH    = true;
	const WITHOUT = false;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_WP_API $wp */
	private $wp;

	/**
	 * @param SitePress   $sitepress
	 * @param WPML_WP_API $wp
	 */
	public function __construct(
		SitePress $sitepress,
		WPML_WP_API $wp = null
	) {
		$this->sitepress = $sitepress;
		$this->wp        = $wp ?: $sitepress->get_wp_api();
	}

	/**
	 * Enables adjusting ids to retrieve translated post instead of original, runs
	 * the given $function and afterwards restore the original behaviour again.
	 *
	 * @param callable $function
	 */
	public function runWith( callable $function ) {
		return $this->runWithOrWithout( self::WITH, $function );
	}

	/**
	 * Disables adjusting ids to retrieve translated post instead of original, runs
	 * the given $function and afterwards restore the original behaviour again.
	 *
	 * @param callable $function
	 *
	 * @return mixed
	 */
	public function runWithout( callable $function ) {
		return $this->runWithOrWithout( self::WITHOUT, $function );
	}

	private function runWithOrWithout( $withOrWithout, callable $function ) {
		// Enable / Disable adjusting of ids.
		$adjust_id_original_state =
			$this->adjustSettingAutoAdjustId( $withOrWithout );
		$get_term_original_state  =
			$this->adjustGetTermFilter( $withOrWithout );
		$get_page_original_state  =
			$this->adjustGetPagesFilter( $withOrWithout );

		// Run given $function.
		$result = $function();

		// Restore previous behaviour.
		$this->adjustSettingAutoAdjustId( $adjust_id_original_state );
		$this->adjustGetTermFilter( $get_term_original_state );
		$this->adjustGetPagesFilter( $get_page_original_state );

		return $result;
	}

	/**
	 * Adjusts setting 'auto_adjust_ids' to enable or disable.
	 * It will only be switched if the setting differs from the current state
	 * of the setting.
	 *
	 * @param bool $enable
	 *
	 * @return bool The state of the setting before adjusting it.
	 */
	private function adjustSettingAutoAdjustId( $enable = true ) {
		$is_setting_enabled =
			$this->sitepress->get_setting( 'auto_adjust_ids', false );

		if ( $enable !== $is_setting_enabled ) {
			$this->sitepress->set_setting( 'auto_adjust_ids', $enable );
		}

		return $is_setting_enabled;
	}


	/**
	 * Add or remove to filter 'get_term' SitePress::get_term_adjust_id().
	 * It will only be added/removed if the current state differs from
	 * expected.
	 *
	 * @param bool $add_filter
	 *
	 * @return bool The state of callback being added before adjusting it.
	 */
	private function adjustGetTermFilter( $add_filter = true ) {
		$is_filter_added =
			$this->wp->has_filter(
				'get_term',
				[ $this->sitepress, 'get_term_adjust_id' ]
			);

		if ( $add_filter !== $is_filter_added ) {
			// State differs. Add/Remove filter callback.
			$add_filter ? $this->wp->add_filter(
				'get_term',
				[ $this->sitepress, 'get_term_adjust_id' ],
				1
			) : $this->wp->remove_filter(
				'get_term',
				[ $this->sitepress, 'get_term_adjust_id' ],
				1
			);
		}

		return $is_filter_added;
	}

	/**
	 * Add or remove to filter 'get_pages' SitePress::get_pages_adjust_ids().
	 * It will only be added/removed if the current state differs from
	 * expected.
	 *
	 * @param bool $add_filter
	 *
	 * @return bool The state of callback being added before adjusting it.
	 */
	private function adjustGetPagesFilter( $add_filter = true ) {
		$is_filter_added =
			$this->wp->has_filter(
				'get_pages',
				[ $this->sitepress, 'get_pages_adjust_ids' ]
			);

		if ( $add_filter !== $is_filter_added ) {
			// State differs. Add/Remove filter callback.
			$add_filter ? $this->wp->add_filter(
				'get_pages',
				[ $this->sitepress, 'get_pages_adjust_ids' ],
				1,
				2
			) : $this->wp->remove_filter(
				'get_pages',
				[ $this->sitepress, 'get_pages_adjust_ids' ],
				1
			);
		}

		return $is_filter_added;
	}
}

