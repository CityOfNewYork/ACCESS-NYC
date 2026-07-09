<?php

require_once __DIR__ . '/wfScanFile.php';

class wfScanFileListItem extends wfScanFile {

	private $id;
	private $next;

	public function __construct($id, $realPath, $wordpressPath, $next = null) {
		parent::__construct($realPath, $wordpressPath);
		$this->id = $id;
		$this->next = $next;
	}

	public function getId() {
		return $this->id;
	}

	public function getNext() {
		return $this->next;
	}

}