<?php

namespace WPML\Compatibility\WPBakery\Hooks;

use WPML\Compatibility\BaseTranslationGuiLabels;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslationGuiLabels extends BaseTranslationGuiLabels {

	const POST_TYPE_TEMPLATE = 'vc_grid_item';

	public function add_hooks() {
		parent::add_hooks();
		Hooks::onFilter( 'wpml_tm_job_list_element_label_filter', 10, 2 )
			->then( spreadArgs( [ $this, 'adjustJobListElementLabel' ] ) );
	}

	/**
	 *
	 */
	public function adjustJobListElementLabel( $label, $elementType ) {
		if ( $elementType === 'post_' . self::POST_TYPE_TEMPLATE ) {
			return $this->formatLabel( __( 'Grid template', 'js_composer' ), self::POST_TYPE_TEMPLATE, false );
		}
		return $label;
	}

	/**
	 * @return string[]
	 */
	protected function getPostTypes() {
		return [ self::POST_TYPE_TEMPLATE ];
	}

	/**
	 * @return string
	 */
	protected function getFormat() {
		// Translators: %s: Post type label. For example, WPBakery Templates.
		return __( 'WPBakery %s', 'sitepress' );
	}

}
