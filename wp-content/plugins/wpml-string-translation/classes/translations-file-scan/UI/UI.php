<?php

namespace WPML\ST\MO\Scan\UI;

class UI implements \IWPML_Action {

	/** @var callable $model */
	private $modelProvider;

	/** @var bool $isSTPage */
	private $isSTPage;

	/**
	 * UI constructor.
	 *
	 * @param callable $modelProvider
	 * @param bool $isSTPage
	 */
	public function __construct( callable $modelProvider, $isSTPage ) {
		$this->modelProvider = $modelProvider;
		$this->isSTPage      = $isSTPage;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_resources' ] );
		if ( ! $this->isSTPage ) {
			add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
			add_action( 'network_admin_notices', [ $this, 'add_admin_notice' ] );
		}
	}

	public function add_admin_notice() {
		?>
		<div id="wpml-mo-scan-st-page"></div>
		<?php
	}

	public function enqueue_resources() {
		$this->enqueue_app_resources(
			'wpml-mo-scan-ui',
			'mo-scan',
			[
				'name'             => 'wpml_mo_scan_ui_files',
				'data'             => call_user_func( $this->modelProvider ),
			]
		);
	}

	public function enqueue_app_resources( $handle, $distSubDirectory, $localize = null ) {
		wp_register_script(
			$handle,
			WPML_ST_URL . "/dist/js/{$distSubDirectory}/app.js",
			[],
			WPML_ST_VERSION
		);

		if ( $localize ) {
			wp_localize_script( $handle, $localize['name'], $localize['data'] );
		}

		wp_enqueue_script( $handle );

		wp_enqueue_style(
			$handle,
			WPML_ST_URL . "/dist/css/{$distSubDirectory}/styles.css",
			array(),
			WPML_ST_VERSION
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'wpml-string-translation', WPML_ST_PATH . "/locale/jed/$handle" );
		}
	}
}
