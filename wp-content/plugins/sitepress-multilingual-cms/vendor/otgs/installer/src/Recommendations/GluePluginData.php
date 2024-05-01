<?php

namespace OTGS\Installer\Recommendations;

class GluePluginData {

	/** @var string */
	private $pluginSlug;

	/** @var array */
	private $gluePluginData;

	/**
	 * @param string $pluginSlug
	 * @param array $gluePluginData
	 */
	public function __construct( $pluginSlug, $gluePluginData ) {
		$this->pluginSlug = $pluginSlug;
		$this->gluePluginData = $gluePluginData;
	}

	/**
	 * @return string
	 */
	public function getPluginSlug() {
		return $this->pluginSlug;
	}

	/**
	 * @return array
	 */
	public function getGluePluginData() {
		return $this->gluePluginData;
	}
}
