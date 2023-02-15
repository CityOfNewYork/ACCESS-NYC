<?php

abstract class WPML_TM_Post_Link_Anchor extends WPML_TM_Post_Link {

	/** @var string $anchor */
	private $anchor;

	/** @var string $target */
	private $target;

	/**
	 * WPML_TM_Post_Link_Anchor constructor.
	 *
	 * @param SitePress $sitepress
	 * @param int $post_id
	 * @param string $anchor
	 * @param string $target
	 */
	public function __construct( SitePress $sitepress, $post_id, $anchor, $target = '' ) {
		parent::__construct( $sitepress, $post_id );
		$this->anchor = $anchor;
		$this->target = $target;
	}

	public function __toString() {
		$post = $this->sitepress->get_wp_api()->get_post( $this->post_id );

		return ! $post
		       || ( in_array( $post->post_status,
				array( 'draft', 'private', 'trash' ), true )
		            && $post->post_author != $this->sitepress->get_wp_api()
		                                                      ->get_current_user_id() )
			? '' : sprintf( '<a href="%s"%s>%s</a>',
				esc_url( $this->link_target() ),
				$this->target ? ' target="' . $this->target . '"' : '',
				esc_html( $this->anchor ) );
	}

	protected abstract function link_target();
}