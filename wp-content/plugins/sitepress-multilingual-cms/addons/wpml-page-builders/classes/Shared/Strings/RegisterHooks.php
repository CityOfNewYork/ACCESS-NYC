<?php

namespace WPML\PB\Strings;

class RegisterHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	/**
	 * Adds a filter to modify the type of Elementor widget strings
	 */
	public function add_hooks() {
		add_filter( 'wpml_elementor_widgets_to_translate', [ $this, 'changeVisualStringType' ], 1000 );
	}

	/**
	 * Modifies the `editor_type` of Elementor widget fields for translation.
	 *
	 * @param array $nodes An array of Elementor widget nodes to be processed for translation.
	 *
	 * @return array The modified array of Elementor widget nodes.
	 */
	public function changeVisualStringType( array $nodes ) {
		// detect if ATE is enable.
		$translationEditor = apply_filters( 'wpml_sub_setting', false, 'translation-management', 'doc_translation_method' );

		 // If ATE is not enabled, return the nodes unmodified.
		if ( 'ATE' !== $translationEditor ) {
			return $nodes;
		}

		foreach ( $nodes as &$node ) {
			if ( isset( $node['fields'] ) && is_array( $node['fields'] ) ) {
				foreach ( $node['fields'] as &$field ) {
					if ( isset( $field['editor_type'] ) && 'VISUAL' === $field['editor_type'] ) {
						$field['editor_type'] = 'LINE';
					}
				}
			}
		}

		return $nodes;
	}

}
