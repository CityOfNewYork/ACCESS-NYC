<?php

namespace WPML\Language\Detection;

use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\FP\Fns;
use \WPML_Request;

class Ajax extends WPML_Request {

	public function get_requested_lang() {
		return Maybe::of( $_REQUEST )
					->map( Obj::prop( 'lang' ) )
					->filter( Lst::includes( Fns::__, $this->active_languages ) )
					->map( 'sanitize_text_field' )
					->getOrElse(
						function () {
							return $this->get_cookie_lang();
						}
					);
	}

	protected function get_cookie_name() {
		return $this->cookieLanguage->getAjaxCookieName( $this->is_admin_action_from_referer() );
	}

	/**
	 * @return bool
	 */
	private function is_admin_action_from_referer() {
		return (bool) Maybe::of( $_SERVER )
						   ->map( Obj::prop( 'HTTP_REFERER' ) )
						   ->map( Str::pos( '/wp-admin/' ) )
						   ->getOrElse( false );
	}
}
