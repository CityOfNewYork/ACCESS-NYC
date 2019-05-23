<?php

class WPML_TM_Rest_Jobs_Language_Names {
	/** @var SitePress */
	private $sitepress;

	/** @var array */
	private $active_languages;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public function get( $code ) {
		$languages = $this->get_active_languages();

		return isset( $languages[ $code ] ) ? $languages[ $code ] : $code;
	}

	/**
	 * @return array
	 */
	public function get_active_languages() {
		if ( ! $this->active_languages ) {
			foreach ( $this->sitepress->get_active_languages() as $code => $data ) {
				$this->active_languages[ $code ] = $data['display_name'];
			}
		}

		return $this->active_languages;
	}
}