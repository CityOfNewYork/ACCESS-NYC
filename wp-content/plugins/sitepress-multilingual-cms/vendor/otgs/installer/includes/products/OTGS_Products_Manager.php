<?php

use OTGS\Installer\Products\ExternalProductsUrls;

class OTGS_Products_Manager {

	/**
	 * @var OTGS_Products_Config_Xml
	 */
	private $products_config_xml;

	/**
	 * @var WP_Installer_Channels
	 */
	private $installer_channels;

	/**
	 * @var OTGS_Installer_Logger_Storage
	 */
	private $logger_storage;

	/**
	 * @var ExternalProductsUrls
	 */
	private $externalProductUrls;

	/**
	 * @param OTGS_Products_Config_Xml $products_config_xml
	 * @param WP_Installer_Channels $installer_channels
	 * @param OTGS_Installer_Logger_Storage $logger_storage
	 */
	public function __construct(
		OTGS_Products_Config_Xml $products_config_xml,
		WP_Installer_Channels $installer_channels,
		OTGS_Installer_Logger_Storage $logger_storage,
		ExternalProductsUrls $externalProductUrls
	) {
		$this->products_config_xml = $products_config_xml;
		$this->installer_channels  = $installer_channels;
		$this->logger_storage      = $logger_storage;
		$this->externalProductUrls = $externalProductUrls;
	}

	/**
	 * @param string       $repository_id
	 * @param string|false $site_key
	 * @param string       $site_url
	 * @param bool         $bypass_buckets
	 *
	 * @return string|null
	 */
	public function get_products_url( $repository_id, $site_key, $site_url, $bypass_buckets ) {
		$repo_id_upper = strtoupper( $repository_id );
		if ( defined( "OTGS_INSTALLER_{$repo_id_upper}_PRODUCTS" ) ) {
			return constant( "OTGS_INSTALLER_{$repo_id_upper}_PRODUCTS" );
		}
		$api_urls = $this->products_config_xml->get_products_api_urls();
		if ( ! $bypass_buckets && $this->is_on_production_channel( $repository_id ) && isset( $api_urls[ $repository_id ] ) ) {

			try {
				$products_url = $this->externalProductUrls->fetchProductUrl( $repository_id, $api_urls[ $repository_id ], $site_key, $site_url );
				if ( $products_url ) {
					return $products_url;
				}
			} catch ( Exception $e ) {
				$this->logger_storage->add( $this->prepare_log( $repository_id, $e->getMessage() ) );
			}
		}

		return $this->products_config_xml->get_repository_products_url( $repository_id );
	}

	/**
	 * @param string $repository_id
	 *
	 * @return bool
	 */
	private function is_on_production_channel( $repository_id ) {
		return $this->installer_channels->get_channel( $repository_id ) === WP_Installer_Channels::CHANNEL_PRODUCTION;
	}

	/**
	 * @param string $repository_id
	 * @param string $message
	 *
	 * @return OTGS_Installer_Log
	 */
	private function prepare_log( $repository_id, $message ) {
		$message = sprintf(
			"Installer cannot contact our updates server to get information about the available products of %s and check for new versions. Error message: %s",
			$repository_id,
			$message
		);

		$log = new OTGS_Installer_Log();
		$log->set_component( OTGS_Installer_Logger_Storage::COMPONENT_PRODUCTS_URL );
		$log->set_response( $message );

		return $log;
	}

}
