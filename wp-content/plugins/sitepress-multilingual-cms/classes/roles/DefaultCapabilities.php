<?php

namespace WPML;

class DefaultCapabilities {

	public static function get() {
		return [
			'wpml_manage_translation_management'        => __( 'Manage Translation Management', 'sitepress' ),
			'wpml_manage_languages'                     => __( 'Manage Languages', 'sitepress' ),
			'wpml_manage_theme_and_plugin_localization' => __( 'Manage Theme and Plugin localization', 'sitepress' ),
			'wpml_manage_support'                       => __( 'Manage Support', 'sitepress' ),
			'wpml_manage_woocommerce_multilingual'      => __( 'Manage WooCommerce Multilingual', 'sitepress' ),
			'wpml_operate_woocommerce_multilingual'     => __( 'Operate WooCommerce Multilingual. Everything on WCML except the settings tab.', 'sitepress' ),
			'wpml_manage_media_translation'             => __( 'Manage Media translation', 'sitepress' ),
			'wpml_manage_navigation'                    => __( 'Manage Navigation', 'sitepress' ),
			'wpml_manage_sticky_links'                  => __( 'Manage Sticky Links', 'sitepress' ),
			'wpml_manage_string_translation'            => __( 'Manage String Translation', 'sitepress' ),
			'wpml_manage_translation_analytics'         => __( 'Manage Translation Analytics', 'sitepress' ),
			'wpml_manage_wp_menus_sync'                 => __( 'Manage WPML Menus Sync', 'sitepress' ),
			'wpml_manage_taxonomy_translation'          => __( 'Manage Taxonomy Translation', 'sitepress' ),
			'wpml_manage_troubleshooting'               => __( 'Manage Troubleshooting', 'sitepress' ),
			'wpml_manage_translation_options'           => __( 'Translation options', 'sitepress' ),
		];
	}
}
