<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;

class LandingPages implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	const POST_TYPE = 'e-landing-page';

	/** @var \SitePress $sitepress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		if ( get_option( 'permalink_structure' ) ) {
			add_filter( 'post_type_link', [ $this, 'adjustLink' ], PHP_INT_MAX, 2 );
		}
	}

	/**
	 * @see \Elementor\Modules\LandingPages\Module::remove_post_type_slug
	 *
	 * @param string   $postUrl
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public function adjustLink( $postUrl, $post ) {
		if ( self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status) {
			return $postUrl;
		}

		$homeUrl          = get_home_url();
		$urlParts         = wp_parse_url( $homeUrl );
		$urlParts['path'] = trailingslashit( Obj::prop( 'path', $urlParts ) ) . $post->post_name . '/';
		$newPostUrl       = http_build_url( null, $urlParts );
		$postLangCode     = $this->sitepress->get_language_for_element( $post->ID, 'post_' . self::POST_TYPE );

		return $this->sitepress->convert_url( $newPostUrl, $postLangCode );
	}
}
