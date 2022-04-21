<?php

namespace WPML\TM\ATE;

use stdClass;

class JobRecord {

	/** @var int $wpmlJobId */
	public $wpmlJobId;

	/** @var int $ateJobId */
	public $ateJobId;

	/**
	 * @todo: Remove this property.
	 *
	 * @var int $editTimestamp
	 */
	public $editTimestamp = 0;

	public function __construct( stdClass $dbRow = null ) {
		if ( $dbRow ) {
			$this->wpmlJobId = (int) $dbRow->job_id;
			$this->ateJobId  = (int) $dbRow->editor_job_id;
		}
	}

	/**
	 * @todo: Remove the "$editTimestamp" and "is_editing", not handled on WPML side anymore.
	 *
	 * The job is considered as being edited if
	 * the timestamp is not greater than 1 day.
	 *
	 * @return bool
	 */
	public function isEditing() {
		$elapsedTime = time() - $this->editTimestamp;
		return $elapsedTime < DAY_IN_SECONDS;
	}
}
