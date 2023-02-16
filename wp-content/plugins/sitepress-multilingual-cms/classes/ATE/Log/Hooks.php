<?php

namespace WPML\TM\ATE\Log;

class Hooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const SUBMENU_HANDLE = 'wpml-tm-ate-log';

	/** @var ViewFactory $viewFactory */
	private $viewFactory;

	public function __construct( ViewFactory $viewFactory ) {
		$this->viewFactory = $viewFactory;
	}

	public function add_hooks() {
		add_action( 'wpml_support_page_after', [ $this, 'renderSupportSection' ] );
		add_action( 'admin_menu', [ $this, 'addLogSubmenuPage' ] );
	}

	public function renderSupportSection() {
		$this->viewFactory->create()->renderSupportSection();
	}

	public function addLogSubmenuPage() {
		add_submenu_page(
			WPML_PLUGIN_FOLDER . '/menu/support.php',
			__( 'Advanced Translation Editor Error Logs', 'wpml-translation-management' ),
			'ATE logs',
			'manage_options',
			self::SUBMENU_HANDLE,
			[ $this, 'renderPage' ]
		);
	}

	public function renderPage() {
		$this->viewFactory->create()->renderPage();
	}
}
