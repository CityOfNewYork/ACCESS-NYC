<?php

namespace WPML\TM\ATE\Log;

class EventsTypes {

	/** Communication errors */
	const SERVER_ATE = 1;
	const SERVER_AMS = 2;
	const SERVER_XLIFF = 3;

	/** Internal errors */
	const JOB_DOWNLOAD = 10;

	/** Retry  */
	const JOB_RETRY = 20;
	const SITE_REGISTRATION_RETRY = 21;

	/** Sync */
	const JOBS_SYNC = 30;

	public static function getLabel( $eventType ) {
		return wpml_collect(
			[
				EventsTypes::SERVER_ATE   => 'ATE Server Communication',
				EventsTypes::SERVER_AMS   => 'AMS Server Communication',
				EventsTypes::SERVER_XLIFF => 'XLIFF Server Communication',
				EventsTypes::JOB_DOWNLOAD => 'Job Download',
				EventsTypes::JOB_RETRY => 'Job resent to ATE',
				EventsTypes::SITE_REGISTRATION_RETRY => 'Site registration request resent to ATE',
				EventsTypes::JOBS_SYNC => 'Jobs sync request sent to ATE failed',
			]
		)->get( $eventType, '' );
	}
}
