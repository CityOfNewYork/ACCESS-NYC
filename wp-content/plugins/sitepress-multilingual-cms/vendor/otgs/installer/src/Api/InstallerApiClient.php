<?php

namespace OTGS\Installer\Api;

use OTGS\Installer\Api\Client\Client;
use OTGS\Installer\Api\Endpoint\Subscription as SubscriptionEndpoint;
use OTGS\Installer\Api\Endpoint\ProductBucketUrl as ProductBucketUrlEndpoint;
use OTGS_Installer_Log;
use OTGS_Installer_Logger_Storage;

class InstallerApiClient {
	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var SubscriptionEndpoint
	 */
	private $subscription;

	/**
	 * @var ProductBucketUrlEndpoint
	 */
	private $productBucketUrl;

	/**
	 * @var OTGS_Installer_Logger_Storage
	 */
	private $loggerStorage;

	/**
	 * @param OTGS_Installer_Logger_Storage $loggerStorage
	 * @param Client $client
	 * @param SubscriptionEndpoint $subscription
	 * @param ProductBucketUrlEndpoint $productBucketUrl
	 */
	public function __construct( OTGS_Installer_Logger_Storage $loggerStorage, Client $client, SubscriptionEndpoint $subscription, ProductBucketUrlEndpoint $productBucketUrl ) {
		$this->loggerStorage           = $loggerStorage;
		$this->client           = $client;
		$this->subscription     = $subscription;
		$this->productBucketUrl = $productBucketUrl;
	}

	/**
	 * @param string $siteKey
	 * @param string $source
	 *
	 * @throws \OTGS_Installer_Fetch_Subscription_Exception
	 */
	public function fetchSubscription( $siteKey, $source ) {
		$requestParams = $this->subscription->prepareRequest( $siteKey, $source );
		try {
			$response = $this->client->post( $requestParams );

			return $this->subscription->parseResponse( $response );
		} catch ( \Exception $exception ) {
			throw new \OTGS_Installer_Fetch_Subscription_Exception( $exception->getMessage() );
		}
	}

	/**
	 * @param string $siteKey
	 *
	 */
	public function fetchProductUrl( $siteKey ) {
		$requestParams = $this->productBucketUrl->prepareRequest( $siteKey );
		try {
			$response = $this->client->post( $requestParams );

			return $this->productBucketUrl->parseResponse( $response );
		} catch ( \Exception $exception ) {
			$this->log( $requestParams, $exception->getMessage() );
		}

		return false;
	}

	private function log( $args, $response ) {
		$log = new OTGS_Installer_Log();
		$log->set_request_args( $args )
		    ->set_response( $response )
		    ->set_component( OTGS_Installer_Logger_Storage::COMPONENT_SUBSCRIPTION );

		$this->loggerStorage->add( $log );
	}
}