<?php

class wfScanFileProperties {

	public $realPath;
	public $wordpressPath;
	public $wordpressPathMd5;
	public $md5;
	public $shac;
	public $content;
	public $known;
	public $handle;
	public $safe;

	public function __construct($realPath, $wordpressPath) {
		$this->realPath = $realPath;
		$this->wordpressPath = $wordpressPath;
		$this->wordpressPathMd5 = md5($wordpressPath);
	}

	public function freeContent() {
		$this->content = null;
	}

	public function resetHandle() {
		if (!$this->handle)
			return false;
		if (fseek($this->handle, 0, SEEK_SET) !== 0)
			return false;
		return true;
	}

	public function releaseHandle() {
		if ($this->handle) {
			fclose($this->handle);
			$this->handle = null;
		}
	}

	public function loadContent() {
		if (!$this->resetHandle())
			return false;
		$content = fread($this->handle, WORDFENCE_MAX_FILE_SIZE_TO_PROCESS);
		if ($content === false)
			return false;
		$this->content = $content;
		return true;
	}

	public function free() {
		$this->freeContent();
		$this->releaseHandle();
	}

	public function __destruct() {
		$this->free();
	}

}