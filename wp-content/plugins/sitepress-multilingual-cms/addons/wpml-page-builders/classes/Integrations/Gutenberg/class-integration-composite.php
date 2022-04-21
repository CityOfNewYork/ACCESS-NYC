<?php

namespace WPML\PB\Gutenberg;

class Integration_Composite implements Integration {

	/**
	 * @var Integration[] $integrations
	 */
	private $integrations;

	public function add( Integration $integration ) {
		$this->integrations[] = $integration;
	}
	
	public function add_hooks() {
		foreach ( $this->integrations as $integration ) {
			$integration->add_hooks();
		}
	}

}
