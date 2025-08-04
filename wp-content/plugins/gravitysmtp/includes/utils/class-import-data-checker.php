<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class Import_Data_Checker {

		public $import_plugins_to_check = array(
			'gravityformspostmark/postmark.php' => 'postmark',
			'gravityformssendgrid/sendgrid.php' => 'sendgrid',
			'gravityformsmailgun/mailgun.php'   => 'mailgun',
			'wp-mail-smtp/wp_mail_smtp.php'     => 'wpmailsmtp',
			'wp-mail-smtp-pro/wp_mail_smtp.php' => 'wpmailsmtp',
		);

	/**
	 * Get a list of connector names that map to activated plugins we import from.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function connectors_to_migrate() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$active_plugins = array_filter( $this->import_plugins_to_check, function ( $key ) {
			return \is_plugin_active( $key );
		}, ARRAY_FILTER_USE_KEY );

		return array_values( $active_plugins );
	}

	/**
	 * Check if any plugins that we import data from are activated.
	 * Used by setup wizard to determine if we should display the import data step.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function import_data_possible() {
		return true;
	}

}
