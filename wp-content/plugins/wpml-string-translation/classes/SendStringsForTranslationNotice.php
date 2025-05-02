<?php

namespace WPML\ST\AdminTexts;

use WPML_WP_API;

class SendStringsForTranslationNotice {

	public static function init() {

		$wp_api = new WPML_WP_API();

		if ( current_user_can( 'manage_options' ) && $wp_api->is_string_translation_page() ) {
			$notices  = wpml_get_admin_notices();
			$noticeId = 'SendStringsForTranslationNotice';
			$stPath   = $wp_api->constant( 'WPML_ST_FOLDER' ) . '/menu/string-translation';

			$linkHref   = admin_url( 'admin.php?page=tm%2Fmenu%2Fmain.php' );
			$noticeText = sprintf(
			/* translators: %s: translation dashboard link */
				__( 'To translate strings automatically, by your translators or a translation service, use the %sTranslation Dashboard%s.', 'wpml-string-translation' ),
				'<a href="' . $linkHref . '" target="_blank">',
				'</a>'
			);
			$notice = $notices->get_new_notice( $noticeId, $noticeText )->set_css_class_types( 'info' );
			$notice->set_css_classes(['send-strings-for-translation-notice']);
			$notice->set_dismissible( true );
			$notice->set_restrict_to_screen_ids( [ $stPath ] );
			$notices->add_notice( $notice );
		}

	}

}
