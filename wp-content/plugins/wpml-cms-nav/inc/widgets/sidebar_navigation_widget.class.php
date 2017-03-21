<?php

class WPML_Navigation_Widget extends WP_Widget {
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		parent::__construct( 'sidebar-navigation', // Base ID
		                     __( 'Sidebar Navigation', 'wpml-cms-nav' ), // Name
		                     array(
			                     'description' => __( 'Sidebar Navigation', 'wpml-cms-nav' ),
			                     'classname'   => 'icl_sidebar_navigation',
		                     ) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		extract($args);

		echo $before_widget;
		global $iclCMSNavigation;
		$iclCMSNavigation->cms_navigation_page_navigation($instance);
		echo $after_widget;

	}

}