<?php

namespace OTGS\Installer\Recommendations;
class RecommendationsForInstallerPlugins {

	/** @var array */
	private $pluginsRecommendations;

	/** @var array */
	private $pluginsData;

	/**
	 * @param array $pluginsRecommendations
	 * @param array $pluginsData
	 */
	public function __construct( $pluginsRecommendations, $pluginsData ) {
		$this->pluginsRecommendations = $pluginsRecommendations;
		$this->pluginsData = $pluginsData;
	}

	/**
	 * @return array
	 */
	public function getRecommendations() {
		return $this->pluginsRecommendations;
	}

	/**
	 * @return array
	 */
	public function getPluginsData() {
		return $this->pluginsData;
	}
}
