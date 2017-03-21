<?php 
/*
Plugin Name: WPML CMS Nav
Plugin URI: https://wpml.org/
Description: Adds CMS navigation elements to sites built with WPML. <a href="https://wpml.org">Documentation</a>.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 1.4.19
Plugin Slug: wpml-cms-nav
*/

if(defined('WPML_CMS_NAV_VERSION')) return;

define('WPML_CMS_NAV_VERSION', '1.4.19');
define('WPML_CMS_NAV_PLUGIN_PATH', dirname(__FILE__));

require WPML_CMS_NAV_PLUGIN_PATH . '/embedded/wpml/commons/src/dependencies/class-wpml-dependencies.php';
require WPML_CMS_NAV_PLUGIN_PATH . '/inc/constants.php';
require WPML_CMS_NAV_PLUGIN_PATH . '/inc/cms-navigation.class.php';
require WPML_CMS_NAV_PLUGIN_PATH . '/inc/cache.class.php';
require WPML_CMS_NAV_PLUGIN_PATH . '/inc/functions.php';
require WPML_CMS_NAV_PLUGIN_PATH . '/inc/upgrade.php';

$iclCMSNavigation = new WPML_CMS_Navigation();
    
// Multisite support
if ( function_exists('is_multisite') && is_multisite() ) {
    $wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
    if(false === get_option('wpml_cms_nav_settings', false) && isset($wpmu_sitewide_plugins[WPML_CMS_NAV_PLUGIN_FOLDER.'/'.basename(__FILE__)])){
        $iclCMSNavigation->plugin_activate();
    }
}

register_activation_hook( WP_PLUGIN_DIR . '/' . WPML_CMS_NAV_PLUGIN_FOLDER . '/plugin.php', array($iclCMSNavigation,'plugin_activate') );
register_deactivation_hook( WP_PLUGIN_DIR . '/' . WPML_CMS_NAV_PLUGIN_FOLDER . '/plugin.php', array($iclCMSNavigation,'plugin_deactivate') );

add_filter('plugin_action_links', array($iclCMSNavigation, 'plugin_action_links'), 10, 2);
