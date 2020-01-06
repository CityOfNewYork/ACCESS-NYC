<?php

use WPML\Collect\Support\Collection;
use WPML\ST\TranslationFile\Manager;

class WPML_ST_JED_File_Manager extends Manager {

	/**
	 * @return string
	 */
	protected function getFileExtension() {
		return 'json';
	}

	/**
	 * @return bool
	 */
	public function isPartialFile() {
		return false;
	}

	/**
	 * @return Collection
	 */
	protected function getDomains() {
		return $this->domains->getJEDDomains();
	}
}
