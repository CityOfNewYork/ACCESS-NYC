<?php

namespace WPML\Language\Detection;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\System\filterVar;
use function \WPML\FP\partialRight;

/**
 * Class WPML_Backend_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class Backend extends \WPML_Request {
	/**
	 * Determines the requested language in the WP Admin backend from URI, $_POST, $_GET and cookies.
	 *
	 * @return string The requested language code.
	 */
	public function get_requested_lang() {
		$findFromSystemVars = Fns::until(
			Logic::isNotNull(),
			[
				$this->getForPage(),
				$this->getFromParam( [ 'get', 'lang' ], true ),
				$this->getFromParam( [ 'post', 'icl_post_language' ], false ),
				$this->getPostElementLanguage(),
			]
		);

		return Maybe::of(
			[
				'get'  => $_GET,
				'post' => $_POST,
			]
		)
					->map( $findFromSystemVars )
					->getOrElse(
						function () {
							return $this->get_cookie_lang();
						}
					);
	}

	private function getForPage() {
		return function ( $system ) {
			return $this->is_string_translation_or_translation_queue_page( $system ) ? $this->default_language : null;
		};
	}

	protected function get_cookie_name() {
		return $this->cookieLanguage->getBackendCookieName();
	}

	private function getFromParam( $path, $allowAllValue ) {
		return function ( $system ) use ( $path, $allowAllValue ) {
			/**  @var \WPML_Language_Resolution $wpml_language_resolution */
			global $wpml_language_resolution;

			return Maybe::of( $system )
						->map( Obj::path( $path ) )
						->map( filterVar( FILTER_SANITIZE_FULL_SPECIAL_CHARS ) )
						->filter( partialRight( [ $wpml_language_resolution, 'is_language_active' ], $allowAllValue ) )
						->getOrElse( null );
		};
	}

	private function getPostElementLanguage() {
		return function ( $system ) {
			/** @var \WPML_Post_Translation $wpml_post_translations */
			global $wpml_post_translations;

			return Maybe::of( $system )
						->map( Obj::path( [ 'get', 'p' ] ) )
						->filter( Relation::gt( Fns::__, 0 ) )
						->map( [ $wpml_post_translations, 'get_element_lang_code' ] )
						->getOrElse( null );
		};
	}

	private function is_string_translation_or_translation_queue_page( $system ) {
		$page = Obj::path( [ 'get', 'page' ], $system );

		return ( defined( 'WPML_ST_FOLDER' ) && $page === WPML_ST_FOLDER . '/menu/string-translation.php' )
			   ||
			   ( defined( 'WPML_TM_FOLDER' ) && $page === WPML_TM_FOLDER . '/menu/translations-queue.php' );
	}
}
