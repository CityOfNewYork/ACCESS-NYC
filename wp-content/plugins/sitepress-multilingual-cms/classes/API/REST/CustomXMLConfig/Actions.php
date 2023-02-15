<?php

namespace WPML\REST\XMLConfig\Custom;

use WP_REST_Request;

class Actions extends \WPML_REST_Base {
	/** @var array<string> */
	private $capabilities = [ 'manage_options' ];

	/**
	 * @var \WPML_Custom_XML
	 */
	private $custom_xml;
	/**
	 * @var \WPML_XML_Config_Validate
	 */
	private $validate;

	public function __construct( \WPML_Custom_XML $custom_xml, \WPML_XML_Config_Validate $validate ) {
		parent::__construct();
		$this->custom_xml = $custom_xml;
		$this->validate   = $validate;
	}


	function add_hooks() {
		$this->register_routes();
	}

	function register_routes() {
		parent::register_route(
			'/custom-xml-config',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'read_content' ],
			]
		);
		parent::register_route(
			'/custom-xml-config',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'update_content' ],
			]
		);
		parent::register_route(
			'/custom-xml-config/validate',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'validate_content' ],
			]
		);
	}

	/**
	 * REST
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return string
	 */
	public function update_content( WP_REST_Request $request ) {
		$content = $request->get_param( 'content' );

		$this->custom_xml->set( $content, false );
		\WPML_Config::load_config_run();

		return $this->custom_xml->get();
	}

	/**
	 * REST
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \LibXMLError[]
	 */
	public function validate_content( WP_REST_Request $request ) {
		$content = $request->get_param( 'content' );

		if ( ! $this->validate->from_string( $content ) ) {
			return $this->validate->get_errors();
		}

		return [];
	}

	/**
	 * REST
	 */
	public function read_content() {
		return $this->custom_xml->get();
	}

	function get_allowed_capabilities( WP_REST_Request $request ) {
		return $this->capabilities;
	}
}
