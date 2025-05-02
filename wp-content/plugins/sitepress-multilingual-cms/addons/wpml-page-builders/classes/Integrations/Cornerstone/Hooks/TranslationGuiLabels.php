<?php

namespace WPML\PB\Cornerstone\Hooks;

use WPML\Compatibility\BaseTranslationGuiLabels;

class TranslationGuiLabels extends BaseTranslationGuiLabels {

	const POST_TYPE_TEMPLATE          = 'cs_template';
	const POST_TYPE_USER_TEMPLATE     = 'cs_user_templates';
	const POST_TYPE_COMPONENT         = 'cs_global_block';
	const POST_TYPE_HEADER            = 'cs_header';
	const POST_TYPE_FOOTER            = 'cs_footer';
	const POST_TYPE_LAYOUT_SINGLE     = 'cs_layout_single';
	const POST_TYPE_LAYOUT_ARCHIVE    = 'cs_layout_archive';
	const POST_TYPE_LAYOUT_LEGACY     = 'cs_layout';
	const POST_TYPE_LAYOUT_SINGLE_WC  = 'cs_layout_single_wc';
	const POST_TYPE_LAYOUT_ARCHIVE_WC = 'cs_layout_archive_wc';

	/**
	 * @return string[]
	 */
	protected function getPostTypes() {
		return [
			self::POST_TYPE_TEMPLATE,
			self::POST_TYPE_USER_TEMPLATE,
			self::POST_TYPE_COMPONENT,
			self::POST_TYPE_HEADER,
			self::POST_TYPE_FOOTER,
			self::POST_TYPE_LAYOUT_SINGLE,
			self::POST_TYPE_LAYOUT_ARCHIVE,
			self::POST_TYPE_LAYOUT_LEGACY,
			self::POST_TYPE_LAYOUT_SINGLE_WC,
			self::POST_TYPE_LAYOUT_ARCHIVE_WC,
		];
	}

	/**
	 * @return string
	 */
	protected function getFormat() {
		// Translators: %s: Post type label. For example, Cornerstone Templates.
		return __( 'Cornerstone %s', 'sitepress' );
	}

}
