<?php

class WPML_Custom_Post_Sync_Settings {

	/** @var array $settings */
	private $settings;

	public function __construct( SitePress $sitepress ) {
		$this->settings = $sitepress->get_setting( 'custom_posts_sync_option', array() );
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public function is_sync( $type ) {
		return isset( $this->settings[ $type ] ) &&
		       (
			       $this->settings[ $type ] == WPML_CONTENT_TYPE_TRANSLATE ||
			       $this->settings[ $type ] == WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED
		       );
	}
}