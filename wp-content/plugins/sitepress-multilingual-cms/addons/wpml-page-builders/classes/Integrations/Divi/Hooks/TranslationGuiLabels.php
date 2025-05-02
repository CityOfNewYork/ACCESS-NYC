<?php

namespace WPML\Compatibility\Divi\Hooks;

use WPML\Compatibility\BaseTranslationGuiLabels;

class TranslationGuiLabels extends BaseTranslationGuiLabels {

	const POST_TYPE_TEMPLATE      = 'et_template';
	const POST_TYPE_THEME_BUILDER = 'et_theme_builder';
	const POST_TYPE_PROJECT       = 'project';
	const POST_TYPE_LAYOUT        = 'et_pb_layout';
	const POST_TYPE_LAYOUT_HEADER = 'et_header_layout';
	const POST_TYPE_LAYOUT_BODY   = 'et_body_layout';
	const POST_TYPE_LAYOUT_FOOTER = 'et_footer_layout';

	/**
	 * @return string[]
	 */
	protected function getPostTypes() {
		return [
			self::POST_TYPE_TEMPLATE,
			self::POST_TYPE_THEME_BUILDER,
			self::POST_TYPE_PROJECT,
			self::POST_TYPE_LAYOUT,
			self::POST_TYPE_LAYOUT_HEADER,
			self::POST_TYPE_LAYOUT_BODY,
			self::POST_TYPE_LAYOUT_FOOTER,
		];
	}

	/**
	 * @return string
	 */
	protected function getFormat() {
		// Translators: %s: Post type label. For example, Divi Templates.
		return __( 'Divi %s', 'sitepress' );
	}

}
