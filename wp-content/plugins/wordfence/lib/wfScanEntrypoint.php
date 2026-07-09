<?php

class wfScanEntrypoint {

	private $file;
	private $included;

	public function __construct($file, $included = false) {
		$this->file = $file;
		$this->included = $included;
	}

	public function getKey() {
		return $this->file->getRealPath();
	}

	public function setIncluded($included = true) {
		$this->included = $included;
		return $this;
	}

	public function isIncluded() {
		return $this->included;
	}

	public function getFile() {
		return $this->file;
	}

	public function addTo(&$entrypoints) {
		$key = $this->getKey();
		if (array_key_exists($key, $entrypoints)) {
			if ($this->isIncluded())
				$entrypoints[$key]->setIncluded();
		}
		else {
			$entrypoints[$key] = $this;
		}
	}

	public static function getScannedSkippedFiles($entrypoints) {
		$scanned = array();
		$skipped = array();
		foreach ($entrypoints as $entrypoint) {
			if ($entrypoint->isIncluded()) {
				$scanned[] = $entrypoint->getFile();
			}
			else {
				$skipped[] = $entrypoint->getFile();
			}
		}
		return array(
			'scanned' => $scanned,
			'skipped' => $skipped
		);
	}

}