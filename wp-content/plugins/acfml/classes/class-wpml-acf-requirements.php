<?php

class WPML_ACF_Requirements {

	public function check_wpml_core() {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_wpml_notice' ) );
		}
	}

	public function missing_wpml_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( 'ACFML is enabled but not effective. It requires WPML in order to work.', 'acfml' ); ?></p>
		</div>
		<?php
	}
}