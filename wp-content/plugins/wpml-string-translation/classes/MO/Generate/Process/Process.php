<?php

namespace WPML\ST\MO\Generate\Process;


interface Process {

	public function runAll();

	/**
	 * @return int Remaining
	 */
	public function runPage();

	/**
	 * @return int
	 */
	public function getPagesCount();

	/**
	 * @return bool
	 */
	public function isCompleted();
}