<?php

namespace OTGS\Installer\Subscription;

use OTGS\Installer\Api\Exception\InvalidProductBucketUrl;
use OTGS\Installer\Api\InstallerApiClient;
use OTGS_Products_Config_Db_Storage;

class SubscriptionManager {

	/**
	 * @var InstallerApiClient
	 */
	private $apiClient;

	/**
	 * @var OTGS_Products_Config_Db_Storage
	 */
	private $productsConfigStorage;

	/**
	 * @var string
	 */
	private $repositoryId;

	/**
	 * @param string $repositoryId
	 * @param InstallerApiClient $apiClient
	 * @param OTGS_Products_Config_Db_Storage $productsConfigStorage
	 */
	public function __construct( $repositoryId, InstallerApiClient $apiClient, OTGS_Products_Config_Db_Storage $productsConfigStorage ) {
		$this->repositoryId          = $repositoryId;
		$this->apiClient             = $apiClient;
		$this->productsConfigStorage = $productsConfigStorage;
	}

	/**
	 * @param string $siteKey
	 * @param int $source
	 *
	 * @throws \OTGS_Installer_Fetch_Subscription_Exception
	 * @throws InvalidProductBucketUrl
	 */
	public function fetch( $siteKey, $source ) {
		$fetchSubscriptionResult = $this->apiClient->fetchSubscription( $siteKey, $source );
		$this->maybeUpdateBuckets( $fetchSubscriptionResult, $siteKey );

		do_action( 'installer_fetched_subscription_data', $fetchSubscriptionResult, $this->repositoryId );

		return [ $fetchSubscriptionResult->subscription_data, $fetchSubscriptionResult->site_key ];
	}

	/**
	 * @param array $fetchSubscriptionResult
	 * @param string $siteKey
	 *
	 * @throws InvalidProductBucketUrl
	 */
	private function maybeUpdateBuckets( $fetchSubscriptionResult, $siteKey ) {
		if ( isset ( $fetchSubscriptionResult->bucket_version )
		     && $this->shouldRefetchProductUrl( $this->repositoryId, $fetchSubscriptionResult->bucket_version ) ) {
			$productUrl = $this->apiClient->fetchProductUrl( $siteKey );
			if ( $productUrl ) {
				$this->productsConfigStorage->store_repository_products_url( $this->repositoryId, $productUrl );
				$this->productsConfigStorage->update_repository_product_version( $this->repositoryId, $fetchSubscriptionResult->bucket_version );
			}

		}
	}

	/**
	 * @param string $repository_id
	 * @param int $fetchVersion
	 *
	 * @return bool
	 */
	public function shouldRefetchProductUrl( $repository_id, $fetchVersion ) {
		$currentVersion = $this->productsConfigStorage->get_repository_product_version( $repository_id );

		return ! $currentVersion || $currentVersion < $fetchVersion;
	}
}