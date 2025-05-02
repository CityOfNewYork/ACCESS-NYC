<?php

namespace WPML\PB\SiteOrigin\Hooks;

use WPML\FP\Str;

use function WPML\FP\invoke;

class WordCount implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		add_filter( 'wpml_words_count_post_content', [ $this, 'getStringPackageContent' ], 10, 2 );
	}

	/**
	 * @param string $postContent
	 * @param int    $postId
	 *
	 * @return string
	 */
	public function getStringPackageContent( $postContent, $postId ) {
		if ( $this->contentHasSiteOriginBlock( $postContent ) ) {
			$packages = apply_filters( 'wpml_st_get_post_string_packages', [], $postId );

			return wpml_collect( $packages )
				->flatMap( invoke( 'get_package_strings' ) )
				->implode( 'value', ' ' );
		}

		return $postContent;
	}

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	private function contentHasSiteOriginBlock( $content ) {
		return Str::includes( '<!-- wp:siteorigin-panels/layout-block', $content );
	}

}
