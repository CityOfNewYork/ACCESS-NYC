<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Custom_XML_UI_Hooks {
	/** @var WPML_Custom_XML_UI_Resources  */
	private $resources;

	public function __construct( WPML_Custom_XML_UI_Resources $resources ) {
		$this->resources = $resources;
	}

	public function init() {
		add_filter( 'wpml_tm_tab_items', array( $this, 'add_items' ) );
		add_action( 'admin_enqueue_scripts', array( $this->resources, 'admin_enqueue_scripts' ) );
	}

	public function add_items( $tab_items ) {
		$tab_items['custom-xml-config']['caption']          = __( 'Custom XML Configuration', 'wpml-translation-management' );
		$tab_items['custom-xml-config']['callback']         = array( $this, 'build_content' );
		$tab_items['custom-xml-config']['current_user_can'] = 'manage_options';

		return $tab_items;
	}

	public function build_content() {
		echo '<div id="wpml-tm-custom-xml-content" class="wpml-tm-custom-xml js-wpml-tm-custom-xml"></div>';
	}
}
