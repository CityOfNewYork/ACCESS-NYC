<?php

namespace WPML\Requirements;

class WordPress {

	public static function checkMinimumRequiredVersion() {
		if ( version_compare( $GLOBALS['wp_version'], '4.4', '<' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'displayMissingVersionRequirementNotice' ] );
			return false;
		}

		return true;
	}

	public static function displayMissingVersionRequirementNotice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'WPML is disabled because it requires WordPress version 4.4 or above.', 'sitepress' ); ?></p>
		</div>
		<?php
	}
}
