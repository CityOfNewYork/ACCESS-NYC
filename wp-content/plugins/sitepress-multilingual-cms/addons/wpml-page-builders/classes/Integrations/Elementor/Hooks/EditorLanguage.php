<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\LIB\WP\Hooks;

class EditorLanguage implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \SitePress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		Hooks::onAction( 'elementor/editor/init' )
			->then( [ $this, 'set' ] );
	}

	public function set() {
		$currentLanguage = $this->sitepress->get_current_language();
		$adminLanguage   = $this->sitepress->get_admin_language();
		if ( $currentLanguage !== $adminLanguage ) {
			$this->sitepress->switch_lang( $adminLanguage );
		}
	}

}
