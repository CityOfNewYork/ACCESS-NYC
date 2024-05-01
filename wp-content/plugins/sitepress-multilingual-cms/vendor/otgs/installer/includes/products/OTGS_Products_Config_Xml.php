<?php

class OTGS_Products_Config_Xml {

	/**
	 * @var SimpleXMLElement
	 */
	private $repositories_config;

	/**
	 * @param string $xml_file
	 */
	public function __construct( $xml_file ) {
		$this->repositories_config = $this->load_configuration( $xml_file );
	}

	/**
	 * @param $xml_file
	 *
	 * @return SimpleXMLElement|null
	 */
	private function load_configuration( $xml_file ) {
		if( ! file_exists( $xml_file )) {
			return null;
		}
		return simplexml_load_file( $xml_file );
	}

	/**
	 * @param $repository_id
	 *
	 * @return string|null
	 */
	public function get_repository_products_url( $repository_id ) {
		foreach ( $this->repositories_config as $repository_config ) {
			if ( isset( $repository_config->id ) && strval( $repository_config->id ) == $repository_id ) {
				return isset( $repository_config->products) ? strval( $repository_config->products ) : null;
			}
		}

		return null;
	}

	public function get_repository_products_default_data() {
		$productDefaults = [];
		foreach ( $this->repositories_config as $repository_config ) {
			$productDefaults[ strval( $repository_config->id ) ] = isset( $repository_config->default_products )
				? json_decode( strval( $repository_config->default_products ), true ) : null;
		}

		return $productDefaults;
	}

	/**
	 * @return array
	 */
	public function get_products_api_urls() {
		$urls = [];

		foreach ( $this->repositories_config as $repository_config ) {
			if ( isset( $repository_config->apiurl ) ) {
				$urls[strval( $repository_config->id )] = strval( $repository_config->apiurl );
			}

			$repo_upper = strtoupper( $repository_config->id );
			if ( defined( "OTGS_INSTALLER_{$repo_upper}_API_URL" ) ) {
				$urls[strval( $repository_config->id )] = constant( "OTGS_INSTALLER_{$repo_upper}_API_URL" );
			}
		}

		return $urls;
	}
}
