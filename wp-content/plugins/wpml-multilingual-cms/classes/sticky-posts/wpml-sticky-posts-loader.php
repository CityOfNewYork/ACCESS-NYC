<?php

class WPML_Sticky_Posts_Loader {
	/** @var SitePress */
	private $sitepress;
	
	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}


	public function add_hooks() {
		if ( $this->sitepress->get_setting( 'sync_sticky_flag' ) ) {
			$sticky_post_sync = wpml_sticky_post_sync( $this->sitepress );

			add_filter(
				'pre_option_sticky_posts',
				array( $sticky_post_sync, 'pre_option_sticky_posts_filter' ),
				10,
				0
			);

			add_action( 'post_stuck', array( $sticky_post_sync, 'on_post_stuck' ) );
			add_action( 'post_unstuck', array( $sticky_post_sync, 'on_post_unstuck' ) );

			add_filter(
				'pre_update_option_sticky_posts',
				array( $sticky_post_sync, 'pre_update_option_sticky_posts' ),
				10,
				1
			);
		}
	}
}