<?php

use WPML\FP\Lst;

/**
 * Created by PhpStorm.
 * User: bruce
 * Date: 4/10/17
 * Time: 10:15 AM
 */

class WPML_Translation_Modes {

	public function is_translatable_mode( $mode ) {
		return Lst::includes(
			(int) $mode,
			[ WPML_CONTENT_TYPE_TRANSLATE, WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED ]
		);
	}

	public function get_options_for_post_type( $post_type_label ) {
		return [
			WPML_CONTENT_TYPE_DONT_TRANSLATE           => sprintf( __( "Do not make '%s' translatable", 'sitepress' ), $post_type_label ),
			WPML_CONTENT_TYPE_TRANSLATE                => sprintf( __( "Make '%s' translatable", 'sitepress' ), $post_type_label ),
			WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED => sprintf( __( "Make '%s' appear as translated", 'sitepress' ), $post_type_label ),
		];
	}

	public function get_options() {
		$formatHeading = function ( $a, $b ) {
			return $a . "<br/><span class='explanation-text'>" . $b . '</span>';
		};

		return [
			WPML_CONTENT_TYPE_TRANSLATE                => $formatHeading(
				esc_html__( 'Translatable', 'sitepress' ),
				esc_html__( 'only show translated items', 'sitepress' )
			),
			WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED => $formatHeading(
				esc_html__( 'Translatable', 'sitepress' ),
				esc_html__( 'use translation if available or fallback to default language', 'sitepress' )
			),
			WPML_CONTENT_TYPE_DONT_TRANSLATE           => $formatHeading(
				esc_html__( 'Not translatable', 'sitepress' ),
				'&nbsp;'
			)
		];
	}
}
