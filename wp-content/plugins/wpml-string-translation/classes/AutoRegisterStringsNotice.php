<?php

namespace WPML\ST;

use WPML\ST\Gettext\AutoRegisterSettings;
use WPML_WP_API;
use function WPML\Container\make;

class AutoRegisterStringsNotice {

	public static function init() {
		$wp_api = new WPML_WP_API();
		if ( current_user_can( 'manage_options' ) && $wp_api->is_string_translation_page() ) {
			$autoRegisterDisabled = make( AutoRegisterSettings::class )->getIsTypeDisabled();
			$notices              = wpml_get_admin_notices();
			$noticeId             = 'AutoRegisterStringsNotice';

			if ( $autoRegisterDisabled ) {
				// If Auto Register Strings is disabled, create or update a notice.
				$stPath = $wp_api->constant( 'WPML_ST_FOLDER' ) . '/menu/string-translation';

				// Check if we are on Admin Texts Translation page (query string trop=1 is present), Display the absolute link.
				$linkHref   = ! empty( $_GET['trop'] )
					? admin_url( 'admin.php?page=' . $stPath . '.php#dashboard_wpml_st_autoregister' )
					: '#dashboard_wpml_st_autoregister';
				$linkText   = '<a href="' . $linkHref . '" id="wpml_open_autoregistration_setting">' . __( 'Click here to enable it', 'wpml-string-translation' ) . '</a>';
				$noticeText = __( 'String auto registration is disabled. ', 'wpml-string-translation' );
				$notice     = $notices->get_new_notice(
					$noticeId, $noticeText . $linkText
				)->set_css_class_types( 'warning' );
				$notice->set_dismissible( true );
				$notice->set_restrict_to_screen_ids( [ $stPath ] );
				$notices->add_notice( $notice );
			} elseif ( ! $autoRegisterDisabled && ! is_null( $notices->get_notice( $noticeId ) ) ) {
				// If Auto Register Strings is enabled but notice exist, remove it.
				$notices->remove_notice( $notices::DEFAULT_GROUP, $noticeId );
			}
		}
	}

}
