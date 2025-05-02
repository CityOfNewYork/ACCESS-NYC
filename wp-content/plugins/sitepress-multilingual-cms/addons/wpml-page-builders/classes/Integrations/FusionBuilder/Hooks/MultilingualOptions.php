<?php

namespace WPML\Compatibility\FusionBuilder\Hooks;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class MultilingualOptions implements \IWPML_Backend_Action {

	const OPTIONS_SCREEN_ID = 'appearance_page_avada_options';
	// See \WPML_Multilingual_Options::addNotice() in WPML core.
	const NOTICE_GROUP      = 'wpml-multilingual-options';

	public function add_hooks() {
		Hooks::onAction( 'current_screen' )->then( spreadArgs( [ $this, 'multilingualOptionsNotice' ] ) );
	}

	public function multilingualOptionsNotice( $screen ) {
		if ( $screen->id !== self::OPTIONS_SCREEN_ID ) {
			return;
		}

		$noticeId     = md5( self::OPTIONS_SCREEN_ID );
		$adminNotices = wpml_get_admin_notices();
		$adminNotices->remove_notice( self::NOTICE_GROUP, $noticeId );

		$text   = '<h4>' . __( 'You can set different options for each language', 'sitepress' ) . '</h4>'
			. '<p>' . __( 'Use the language switcher in the top admin bar to switch languages, then set and save options for each language individually.', 'sitepress' ) . '</p>';
		$notice = new \WPML_Notice( $noticeId, $text, self::NOTICE_GROUP );
		$notice->set_css_class_types( 'info' );
		$notice->set_restrict_to_screen_ids( [ self::OPTIONS_SCREEN_ID ] );
		$notice->set_dismissible( true );
		$adminNotices->add_notice( $notice, true );
	}

}
