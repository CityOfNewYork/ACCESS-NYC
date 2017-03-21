<?php

//Fix new Language Sidebar Navigation widget implementation
$widget_sidebar_navigation = get_option( 'widget_sidebar-navigation' );
if ( is_admin() && $widget_sidebar_navigation ) {
	$widget_sidebar_navigation[ '_multiwidget' ] = 1;

	update_option( 'widget_sidebar-navigation', $widget_sidebar_navigation );
}


//$sidebars = 'a:5:{s:19:"wp_inactive_widgets";a:0:{}s:9:"sidebar-1";a:8:{i:0;s:21:"icl_lang_sel_widget-2";i:1;s:10:"calendar-2";i:2;s:8:"search-2";i:3;s:14:"recent-posts-2";i:4;s:17:"recent-comments-2";i:5;s:10:"archives-2";i:6;s:12:"categories-2";i:7;s:6:"meta-2";}s:9:"sidebar-2";a:0:{}s:9:"sidebar-3";a:0:{}s:13:"array_version";i:3;}';
//$sidebars = unserialize($sidebars);
$sidebars = get_option( 'sidebars_widgets' );

//Fix widget id from single to multi instance
$changed = false;
foreach ( $sidebars as $sidebar_id => $widgets ) {
	if ( is_array( $widgets ) ) {
		foreach ( $widgets as $index => $widget_id ) {
			if ( $widget_id== 'sidebar-navigation' ) {
				$sidebars[ $sidebar_id ][ $index ] = $widget_id . '-1';
				$changed                           = true;
				break;
			}
		}
	}
	if($changed) {
		break;
	}
}

if ( $changed ) {
	update_option( 'sidebars_widgets', $sidebars );
}