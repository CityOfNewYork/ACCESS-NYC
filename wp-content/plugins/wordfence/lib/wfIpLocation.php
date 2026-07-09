<?php

class wfIpLocation {
	const DATABASE_FILE_NAME = 'geoip.mmdb'; //Also defined in bootstrap.php

	const LANGUAGE_DEFAULT = 'en';
	const LANGUAGE_SEPARATOR = '_';

	private $record;

	public function __construct($record) {
		$this->record = is_array($record) ? $record : array();
	}

	public function getCountryRecord() {
		if (array_key_exists('country', $this->record)) {
			$country = $this->record['country'];
			if (is_array($country))
				return $country;
		}
		return array();
	}

	public function getCountryField($field) {
		$country = $this->getCountryRecord();
		if (array_key_exists($field, $country))
			return $country[$field];
		return null;
	}

	public function getCountryCode() {
		$isoCode = $this->getCountryField('iso_code');
		if (is_string($isoCode) && strlen($isoCode) === 2)
			return $isoCode;
		return null;
	}

	private function findBestLanguageMatch($options, $preferredLanguage = self::LANGUAGE_DEFAULT) {
		$languages = array();
		if (is_string($preferredLanguage))
			$languages[] = $preferredLanguage;
		if (strpos($preferredLanguage, self::LANGUAGE_SEPARATOR) !== false) {
			$components = explode(self::LANGUAGE_SEPARATOR, $preferredLanguage);
			$baseLanguage = $components[0];
			if ($baseLanguage !== self::LANGUAGE_DEFAULT)
				$languages[] = $baseLanguage;
		}
		if ($preferredLanguage !== self::LANGUAGE_DEFAULT)
			$languages[] = self::LANGUAGE_DEFAULT;
		foreach ($languages as $language) {
			if (array_key_exists($language, $options))
				return $options[$language];
		}
		if (!empty($options))
			return reset($options);
		return null;
	}

	public function getCountryName($preferredLanguage = self::LANGUAGE_DEFAULT) {
		$names = $this->getCountryField('names');
		if (is_array($names) && !empty($names))
			return $this->findBestLanguageMatch($names, $preferredLanguage);
		return null;
	}

}