<?php

namespace WPML\Support\ATE;

class Hooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	/** @var ViewFactory $viewFactory */
	private $viewFactory;

	public function __construct( ViewFactory $viewFactory ) {
		$this->viewFactory = $viewFactory;
	}

	public function add_hooks() {
		add_action( 'wpml_support_page_after', [ $this, 'renderSupportSection' ] );
	}

	public function renderSupportSection() {
		$this->viewFactory->create()->renderSupportSection();
	}
}