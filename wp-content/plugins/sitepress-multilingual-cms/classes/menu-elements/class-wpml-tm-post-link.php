<?php

abstract class WPML_TM_Post_Link {

	/** @var SitePress $sitepress */
	protected $sitepress;

	/** @var int $post */
	protected $post_id;

	/**
	 * WPML_TM_Post_Link constructor.
	 *
	 * @param SitePress $sitepress
	 * @param int       $post_id
	 */
	public function __construct( $sitepress, $post_id ) {
		$this->sitepress = $sitepress;
		$this->post_id   = $post_id;
	}
}