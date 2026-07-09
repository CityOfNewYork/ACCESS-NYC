<?php

class wfInaccessibleDirectoryException extends RuntimeException {

	private $directory;

	public function __construct($message, $directory) {
		parent::__construct("{$message}: {$directory}");
		$this->directory = $directory;
	}

	public function getDirectory() {
		return $this->directory;
	}

}