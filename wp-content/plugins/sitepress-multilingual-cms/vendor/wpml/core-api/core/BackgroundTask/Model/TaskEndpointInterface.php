<?php
namespace WPML\Core\BackgroundTask\Model;

use WPML\Collect\Support\Collection;
use WPML\FP\Left;
use WPML\FP\Right;

interface TaskEndpointInterface {

	/** @return bool */
	public function isDisplayed();

	/** @return string */
	public function getType();

	/** @return int */
	public function getMaxRetries();

	/** @return int */
	public function getLockTime();

	/**
	 * @param Collection $data
	 * @return int
	 */
	public function getTotalRecords( Collection $data );

	/**
	 * @param Collection $data
	 * @return int
	 */
	public function getDescription( Collection $data );

	/**
	 * @param Collection $data
	 * @return callable|Right|Left
	 */
	public function run( Collection $data );
}