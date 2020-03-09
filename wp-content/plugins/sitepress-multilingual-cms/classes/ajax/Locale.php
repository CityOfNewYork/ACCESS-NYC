<?php

namespace WPML\Ajax;

class Locale implements \IWPML_AJAX_Action, \IWPML_DIC_Action {

	/** @var \SitePress */
	private $sitePress;

	public function __construct( \SitePress $sitePress ) {
		$this->sitePress = $sitePress;
	}

	public function add_hooks() {
		if ( is_admin() && ! $this->sitePress->check_if_admin_action_from_referer() ) {
			add_filter( 'determine_locale', [ $this->sitePress, 'locale_filter' ], 10, 1 );
		}
	}
}
