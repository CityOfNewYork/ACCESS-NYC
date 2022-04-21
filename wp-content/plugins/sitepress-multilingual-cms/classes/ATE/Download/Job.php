<?php

namespace WPML\TM\ATE\Download;

class Job {

	/** @var int $ateJobId */
	public $ateJobId;

	/** @var string $url */
	public $url;

	/** @var int */
	public $ateStatus;

	/**
	 * This property is not part of the database data,
	 * but it can be added when the job is downloaded
	 * to provide more information to the UI.
	 *
	 * @var int $jobId
	 */
	public $jobId;

	/** @var int */
	public $status = ICL_TM_IN_PROGRESS;

	/**
	 * @param \stdClass $item
	 *
	 * @return Job
	 */
	public static function fromAteResponse( \stdClass $item ) {
		$job            = new self();
		$job->ateJobId  = $item->ate_id;
		$job->url       = $item->download_link;
		$job->ateStatus    = (int) $item->status;
		$job->jobId = (int) $item->id;

		return $job;
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return Job
	 */
	public static function fromDb( \stdClass $row ) {
		$job           = new self();
		$job->ateJobId = $row->editor_job_id;
		$job->url      = $row->download_url;

		return $job;
	}
}
