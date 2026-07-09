<?php

class wfInvalidPathException extends RuntimeException {

	private $path;

	public function __construct($message, $path) {
		parent::__construct("{$message} for path {$path}");
		$this->path = $path;
	}

	public function getPath() {
		return $this->path;
	}

}