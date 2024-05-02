<?php

namespace OTGS\Installer\Api;

use OTGS\Installer\Api\Endpoint\Subscription as SubscriptionEndpoint;
use OTGS\Installer\Api\Endpoint\ProductBucketUrl as ProductBucketUrlEndpoint;
use OTGS_Installer_Logger_Storage;
use OTGS_Installer_Plugin_Factory;
use OTGS_Installer_Plugin_Finder;

class InstallerApiClientFactory {
	/**
	 * @param array $installerSettings
	 * @param string $repositoryId
	 * @param string $repositoryApiUrl
	 *
	 * @return InstallerApiClient
	 */
	public static function create( OTGS_Installer_Logger_Storage $loggerStorage, $installerSettings, $repositoryId, $repositoryApiUrl ) {
		$client = new Client\Client( new \WP_Http(), $repositoryApiUrl );

		$siteUrl              = new SiteUrl( $installerSettings['repositories'] );
		$subscriptionEndpoint = new SubscriptionEndpoint(
			$repositoryId,
			$siteUrl,
			new OTGS_Installer_Plugin_Finder( new OTGS_Installer_Plugin_Factory(), $installerSettings['repositories'] )
		);

		$productBucketUrlEndpoint = new ProductBucketUrlEndpoint(
			$repositoryId,
			$siteUrl
		);

		return new InstallerApiClient( $loggerStorage, $client, $subscriptionEndpoint, $productBucketUrlEndpoint );
	}
}