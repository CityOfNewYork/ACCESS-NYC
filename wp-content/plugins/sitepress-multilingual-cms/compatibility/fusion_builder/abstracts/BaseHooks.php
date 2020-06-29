<?php

namespace WPML\Compatibility\FusionBuilder;

abstract class BaseHooks {

	const HANDLE = 'wpml-compatibility-fusion';

	const SCRIPT_SRC = ICL_PLUGIN_URL . '/dist/js/compatibility/fusion_builder/app.js';

	const STYLE_SRC = ICL_PLUGIN_URL . '/res/css/compatibility/fusion_builder.css';

	const OBJECT_NAME = 'WPML_COMPATIBILITY_FUSION';

	protected function enqueue_style() {
		wp_enqueue_style(
			self::HANDLE,
			self::STYLE_SRC,
			[],
			ICL_SITEPRESS_VERSION
		);
	}

	protected function enqueue_script() {
		wp_enqueue_script(
			self::HANDLE,
			self::SCRIPT_SRC,
			[],
			ICL_SITEPRESS_VERSION,
			true
		);
	}

	protected function localize_script( $data ) {
		wp_localize_script(
			self::HANDLE,
			self::OBJECT_NAME,
			$data
		);
	}
}
