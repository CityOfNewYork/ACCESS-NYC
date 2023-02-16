<?php

namespace WPML\TM\TranslationProxy\Services\Project;

class Project {
	/** @var int */
	public $id;

	/** @var string */
	public $accessKey;

	/** @var string */
	public $tsId;

	/** @var string */
	public $tsAccessKey;

	/** @var \stdClass */
	public $extraFields;

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id'            => $this->id,
			'access_key'    => $this->accessKey,
			'ts_id'         => $this->tsId,
			'ts_access_key' => $this->tsAccessKey,
			'extra_fields'  => $this->extraFields,
		];
	}

	/**
	 * @param array $data
	 *
	 * @return Project
	 */
	public static function fromArray( array $data ) {
		$project = new Project();

		$project->id          = $data['id'];
		$project->accessKey   = $data['access_key'];
		$project->tsId        = $data['ts_id'];
		$project->tsAccessKey = $data['ts_access_key'];
		$project->extraFields = $data['extra_fields'];

		return $project;
	}

	public static function fromResponse( \stdClass $response ) {
		$project = new Project();

		$project->id          = $response->id;
		$project->accessKey   = $response->accesskey;
		$project->tsId        = $response->ts_id;
		$project->tsAccessKey = $response->ts_accesskey;

		return $project;
	}
}
