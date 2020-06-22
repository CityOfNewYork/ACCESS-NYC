<?php

namespace WPML\Container;

class Config {

	static public function getSharedInstances() {
		global $wpdb;

		return [
			$wpdb,
		];
	}

	static public function getSharedClasses() {
		return [
			'\SitePress',
			'\WPML\WP\OptionManager',
			'\WP_Http',
			'\WPML_WP_User_Query_Factory',
			'\WPML_WP_User_Factory',
			'\WPML_Notices',
			\WPML_Locale::class,
		];
	}

	static public function getAliases() {
		global $wpdb;

		$aliases = [];

		$wpdb_class = get_class( $wpdb );

		if ( 'wpdb' !== $wpdb_class ) {
			$aliases['wpdb'] = $wpdb_class;
		}

		return $aliases;
	}

	static public function getDelegated() {
		return [
			'\WPML_Notices'                   => 'wpml_get_admin_notices',
			\WPML_REST_Request_Analyze::class => [ \WPML_REST_Request_Analyze_Factory::class, 'create' ],
			\WP_Filesystem_Direct::class      => 'wpml_get_filesystem_direct',
			\WPML_Locale::class               => [ \WPML_Locale::class, 'get_instance_from_sitepress' ],
			\WPML_Post_Translation::class     => [ \WPML_Post_Translation::class, 'getGlobalInstance' ],
			\WPML_Term_Translation::class     => [ \WPML_Term_Translation::class, 'getGlobalInstance' ],
			\WPML_URL_Converter::class        => [ \WPML_URL_Converter::class, 'getGlobalInstance' ],
			\WPML_Post_Status::class          => 'wpml_get_post_status_helper',
			'\WPML_Language_Resolution'       => function () {
				global $wpml_language_resolution;

				return $wpml_language_resolution;
			},
		];
	}
}
