<?php

namespace WPML\TM\ATE\Log;

class Entry {

	/**
	 * @var int $timestamp The log's creation timestamp.
	 */
	public $timestamp = 0;

	/**
	 * @see EventsTypes
	 *
	 * @var int $eventType The event code that triggered the log.
	 */
	public $eventType = 0;

	/**
	 * @var string $description The details of the log (e.g. exception message).
	 */
	public $description = '';

	/**
	 * @var int $wpmlJobId [Optional] The WPML Job ID (when applies).
	 */
	public $wpmlJobId = 0;

	/**
	 * @var int $ateJobId [Optional] The ATE Job ID (when applies).
	 */
	public $ateJobId = 0;

	/**
	 * @var array $extraData [Optional] Complementary serialized data (e.g. API request/response data).
	 */
	public $extraData = [];

	/**
	 * @param array $item
	 *
	 * @return Entry
	 */
	public function __construct( array $item = null ) {
		if ( $item ) {
			$this->timestamp   = (int) $item['timestamp'];
			$this->eventType   = (int) ( isset( $item['eventType'] ) ? $item['eventType'] : $item['event'] );
			$this->description = $item['description'];
			$this->wpmlJobId   = (int) $item['wpmlJobId'];
			$this->ateJobId    = (int) $item['ateJobId'];
			$this->extraData   = (array) $item['extraData'];
		}
	}

	public static function createForType($eventType, $extraData) {
		$entry = new self();
		$entry->eventType = $eventType;
		$entry->extraData = $extraData;

		return $entry;
	}

	public static function retryJob( $wpmlJobId, $extraData ) {
		$entry = self::createForType(EventsTypes::JOB_RETRY, $extraData);
		$entry->wpmlJobId = $wpmlJobId;

		return $entry;
	}

	/**
	 * @return string
	 */
	public function getFormattedDate() {
		return date_i18n( 'Y/m/d g:i:s A', $this->timestamp );
	}

	/**
	 * @return string
	 */
	public function getExtraDataToString() {
		return json_encode( $this->extraData );
	}
}
