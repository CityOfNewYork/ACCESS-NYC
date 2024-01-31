<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use function WPML\FP\pipe;

class LandingPages implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	const POST_TYPE            = 'e-landing-page';
	const BACKUP_HOOK_PRIORITY = 11;

	/** @var \SitePress $sitepress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		if ( get_option( 'permalink_structure' ) ) {
			add_filter( 'post_type_link', [ $this, 'adjustLink' ], PHP_INT_MAX, 2 );
			Hooks::onFilter( 'pre_handle_404', self::BACKUP_HOOK_PRIORITY, 2 )
				->then( spreadArgs( [ $this, 'backupQuery' ] ) );
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
		if ( self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return $postUrl;
		}

		$homeUrl          = get_home_url();
		$urlParts         = wp_parse_url( $homeUrl );
		$urlParts['path'] = trailingslashit( Obj::prop( 'path', $urlParts ) ) . $post->post_name . '/';
		$newPostUrl       = http_build_url( null, $urlParts );
		$postLangCode     = $this->sitepress->get_language_for_element( $post->ID, 'post_' . self::POST_TYPE );

		return $this->sitepress->convert_url( $newPostUrl, $postLangCode );
	}

	/**
	 * Backup and restore the global $wp_query that was overwritten by Elementor.
	 *
	 * In order for Elementor to treat landing pages(post_type: e-landing-page) as pages, it overwrites the global
	 * $wp_query when using the /%category%/%postname%/ permalink structure.
	 * WPML then resets the global $wp_query in SitePress::get_ls_languages() and this causes 404 errors on landing pages
	 * and prevents editing them in the Elementor editor.
	 *
	 * In this case, we backup the global $wp_query when Elementor overwrites it on 'pre_handle_404' hook, and we restore
	 * it after WPML resets it on 'wp_head' hook.
	 *
	 * @see \WPML_SEO_HeadLangs::head_langs()
	 * @see \SitePress::get_ls_languages()
	 * @see \Elementor\Modules\LandingPages\Module::handle_404
	 *
	 * @param bool      $value
	 * @param \WP_Query $query
	 *
	 * @return bool
	 */
	public function backupQuery( $value, $query ) {
		global $wp_query;

		if ( $value ) {
			return $value;
		}

		// $wasModifiedByElementor :: \WP_Query -> bool
		$wasModifiedByElementor = pipe(
			Obj::prop( 'query' ),
			Relation::propEq( 'post_type', self::POST_TYPE )
		);

		// $hasPosts :: \WP_Query -> array
		$hasPosts = Obj::prop( 'posts' );

		if (
			$query->is_main_query()
			&& empty( $query->posts )
			&& ! empty( $query->query['category_name'] )
			&& empty( $query->tax_query->table_aliases )
			&& $wasModifiedByElementor( $wp_query )
			&& $hasPosts( $wp_query )
		) {
			if ( apply_filters( 'wpml_sub_setting', 1, 'seo', 'head_langs' ) ) {
				$wpQueryBackup = clone $wp_query;
				$priority      = (int) apply_filters( 'wpml_sub_setting', 1, 'seo', 'head_langs_priority' );
				Hooks::onAction( 'wp_head', $priority + 1 )
					->then( function() use ( $wpQueryBackup ) {
						 global $wp_query;

						 $wp_query = $wpQueryBackup; //phpcs:ignore WordPress.Variables.GlobalVariables.OverrideProhibited
					} );
			}
		}

		return false;
	}
}
