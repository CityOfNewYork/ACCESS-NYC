<?php

namespace WPML\PB;

/**
 * We had a first project of WPML Page Builders abandoned
 * some years before (last version v1.1.3), when we included the code inside ST,
 * and then in Core as composer package.
 *
 * This old plugin contains outdated code and we cannot afford
 * to have it running in parallel while the new version is also
 * embedded in Core.
 */
class OldPlugin {

	/**
	 * @return bool
	 */
	public static function handle() {
		if (
			defined( 'WPML_PAGE_BUILDERS_VERSION' )
			&& version_compare( constant( 'WPML_PAGE_BUILDERS_VERSION' ), '2', '<' )
		) {
			deactivate_plugins( 'wpml-page-builders/plugin.php' );
			self::addNotice();
			if ( ! wpml_is_cli() && wp_safe_redirect( $_SERVER['REQUEST_URI'], 302, 'wpml' ) ) {
				die;
			}

			return true;
		}

		return false;
	}

	private static function addNotice() {
		$text = '<h2>' . __( 'Update needed for WPML Page Builders plugin', 'wpml-page-builders' ) . '</h2>';
		$text .= '<p>' . __( 'To prevent conflicts with WPML, we have deactivated your outdated WPML Page Builders plugin. Please update and reactivate it to continue receiving compatibility updates for your page builders as they become available.', 'wpml-page-builders' ) . '</p>';
		$text .= '<p>' . __( 'You can still receive compatibility updates without WPML Page Builders as part of the WPML core plugin. However, keeping the standalone plugin allows you to receive these updates sooner and more often.', 'wpml-page-builders' ) . '</p>';

		$notices = wpml_get_admin_notices();
		$notice  = $notices->create_notice( 'deactivated-notice', $text, __CLASS__ );
		$notice->set_flash();
		$notice->set_hideable( true );
		$notice->set_css_class_types( [ 'notice-info' ] );
		$notices->add_notice( $notice );
	}
}
