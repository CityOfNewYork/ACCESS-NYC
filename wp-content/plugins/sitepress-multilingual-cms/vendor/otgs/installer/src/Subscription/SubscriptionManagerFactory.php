<?php

namespace OTGS\Installer\Subscription;

use OTGS\Installer\Api\InstallerApiClientFactory;
use OTGS_Installer_Log_Factory;
use OTGS_Installer_Logger_Storage;
use OTGS_Products_Config_Db_Storage;

class SubscriptionManagerFactory {
	private $installerSettings;

	/**
	 * @var OTGS_Installer_Logger_Storage
	 */
	private $loggerStorage;

	/**
	 * @param $installerSettings
	 */
	public function __construct( $installerSettings ) {
		$this->installerSettings = $installerSettings;
		$this->loggerStorage = new OTGS_Installer_Logger_Storage( new OTGS_Installer_Log_Factory() );
	}

	public function create( $repositoryId, $repositoryApiUrl ) {
		return new SubscriptionManager(
			$repositoryId,
			InstallerApiClientFactory::create(
				$this->loggerStorage,
				$this->installerSettings,
				$repositoryId,
				$repositoryApiUrl
			),
			new OTGS_Products_Config_Db_Storage()
		);
	}
}