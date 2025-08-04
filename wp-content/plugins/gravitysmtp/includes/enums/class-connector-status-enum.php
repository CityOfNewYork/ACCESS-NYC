<?php

namespace Gravity_Forms\Gravity_SMTP\Enums;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;

class Connector_Status_Enum {

	const ENABLED = 'enabled';
	const PRIMARY = 'primary';
	const BACKUP  = 'backup';

	public static function setting_for_status( $status_type ) {
		switch( $status_type ) {
			case self::ENABLED:
			default:
				$setting = Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR;
				break;
			case self::PRIMARY:
				$setting = Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR;
				break;
			case self::BACKUP:
				$setting = Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR;
		}

		return $setting;
	}

}