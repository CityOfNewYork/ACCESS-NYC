<?php
/*
 * Plugin Name: ACF Google Maps Radius Search
 * Plugin URI: 
 * Description: Allows ACF address field to act as radius on search results page
 * Version:  1.0
 * Author: RaiserWeb
 * Author URI: http://www.raiserweb.com
 * Developer: RaiserWeb
 * Developer URI: http://www.raiserweb.com
 * Text Domain: raiserweb
 * License: GPLv2
 *
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
 
 
 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// include plugin files
include( 'functions.php' );
include( 'classes/acf_gms.class.php' );




// install table on activation
register_activation_hook( __FILE__, 'acf_google_maps_search_table_install' ); 

// save post
add_action('acf/save_post', 'acf_google_maps_search_save_post', 20);
	


