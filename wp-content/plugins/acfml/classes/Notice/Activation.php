<?php

namespace ACFML\Notice;

class Activation {

	public static function activate() {
		if ( function_exists( 'wpml_get_admin_notices' ) && class_exists( 'ACF' ) ) {
			$text  = '<h2><i class="otgs-ico-wpml"></i>&nbsp; ' . esc_html__( 'Finish the ACF Multilingual Setup', 'acfml' ) . '</h2>';
			$text .= '<p>' . esc_html__( 'Before you can start translating, you need to edit each ACF Field Group to set a translation option for the fields inside it.', 'acfml' ) . '</p>';
			$text .= '<p>' . sprintf(
				/* translators: The placeholders are replaced by an HTML link pointing to the documentation. */
				esc_html__( 'Read more about %1$show to translate your ACF custom fields%2$s', 'acfml' ),
				'<a href="' . esc_url( Links::getAcfmlMainDoc() ) . '" class="wpml-external-link" target="_blank">',
				'</a>'
			) . '</p>';

			$text .= sprintf(
				/* translators: The placeholders are replaced by an HTML link pointing to field groups list. */
				esc_html__( '%1$sSet translation options%2$s', 'acfml' ),
				'<a href="' . admin_url( 'edit.php?post_type=acf-field-group' ) . '" class="button">', // phpcs:ignore
				'</a>'
			);

			$notices = wpml_get_admin_notices();
			$notice  = $notices->create_notice( 'acfml-activation-notice', $text, 'acfml' );
			$notice->set_flash();
			$notice->set_css_class_types( [ 'acfml-notice' ] );
			$notices->add_notice( $notice );
		}
	}
}
