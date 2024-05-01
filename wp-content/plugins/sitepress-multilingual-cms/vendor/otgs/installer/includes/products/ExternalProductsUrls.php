<?php

namespace OTGS\Installer\Products;

use OTGS_Products_Bucket_Repository;
use OTGS_Products_Config_Db_Storage;

class ExternalProductsUrls {
	/**
	 * @var OTGS_Products_Bucket_Repository
	 */
	private $products_bucket_repository;

	/**
	 * @var OTGS_Products_Config_Db_Storage
	 */
	private $products_config_storage;

	public function __construct(
		OTGS_Products_Config_Db_Storage $products_config_storage,
		OTGS_Products_Bucket_Repository $products_bucket_repository
	) {
		$this->products_config_storage    = $products_config_storage;
		$this->products_bucket_repository = $products_bucket_repository;
	}

	/**
	 * @param string $repository_id
	 * @param string $site_key
	 * @param string $site_url
	 *
	 * @return string|null
	 */
	public function fetchProductUrl( $repository_id, $api_url, $site_key, $site_url ) {
		if ( ! $site_key ) {
			$this->products_config_storage->clear_repository_products_url( $repository_id );
			$this->products_config_storage->clear_repository_product_version( $repository_id );

			return null;
		}

		$products_url = $this->products_config_storage->get_repository_products_url( $repository_id );
		if ( $products_url ) {
			return $products_url;
		}

		return $this->get_products_url_from_otgs( $repository_id, $api_url, $site_key, $site_url );
	}

	/**
	 * @param string $repository_id
	 * @param string $site_key
	 * @param string $site_url
	 *
	 * @return string|null
	 */
	private function get_products_url_from_otgs( $repository_id, $api_url, $site_key, $site_url ) {
		// it should get products bucket url using InstallerApiClient instead of OTGS_Products_Bucket_Repository in future
		$products_url = $this->products_bucket_repository->get_products_bucket_url( $api_url, $site_key, $site_url );
		if ( $products_url ) {
			$this->products_config_storage->store_repository_products_url( $repository_id, $products_url );
		}

		return $products_url;
	}

}