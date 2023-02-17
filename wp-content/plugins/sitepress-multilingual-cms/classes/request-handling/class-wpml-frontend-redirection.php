<?php

use WPML\Language\Detection\Frontend;

class WPML_Frontend_Redirection extends WPML_SP_User {

	/** @var Frontend $request_handler */
	private $request_handler;

	/** @var  WPML_Redirection */
	private $redirect_helper;

	/** @var  WPML_Language_Resolution $lang_resolution */
	private $lang_resolution;

	/**
	 * WPML_Frontend_Redirection constructor.
	 *
	 * @param  SitePress                $sitepress
	 * @param  Frontend                 $request_handler
	 * @param  WPML_Redirection         $redir_helper
	 * @param  WPML_Language_Resolution $lang_resolution
	 */
	public function __construct(
		&$sitepress,
		&$request_handler,
		&$redir_helper,
		&$lang_resolution
	) {
		parent::__construct( $sitepress );
		$this->request_handler = &$request_handler;
		$this->redirect_helper = &$redir_helper;
		$this->lang_resolution = &$lang_resolution;
	}

	/**
	 * Redirects to a URL corrected for the language information in it, in case request URI and $_REQUEST['lang'],
	 * requested domain or $_SERVER['REQUEST_URI'] do not match and gives precedence to the explicit language parameter if
	 * there.
	 *
	 * @return string The language code of the currently requested URL in case no redirection was necessary.
	 */
	public function maybe_redirect() {
		$target = $this->redirect_helper->get_redirect_target();
		if ( false !== $target ) {
			$frontend_redirection_url = new WPML_Frontend_Redirection_Url( $target );
			$target                   = $frontend_redirection_url->encode_apostrophes_in_url();
			$this->sitepress->get_wp_api()->wp_safe_redirect( $target );
		};

		// allow forcing the current language when it can't be decoded from the URL.
		return $this->lang_resolution->current_lang_filter( $this->request_handler->get_requested_lang(), $this->request_handler );
	}

}
