<?php 


function acf_google_maps_search_table_install(){
	
    global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
 
    $charset_collate = $wpdb->get_charset_collate();
 
	$table_name = $wpdb->prefix . 'acf_google_map_search_geodata';
 
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id BIGINT NULL UNIQUE,
        lat DECIMAL(9,6) NULL,
        lng DECIMAL(9,6) NULL,
        UNIQUE KEY id (id)
    ) {$charset_collate};";
 
    dbDelta( $sql );	
	
	
	// now update historic post data 
	$acf_gms = new acf_gms;
	$acf_gms->update_historic_post_gm_data();
	
}



function acf_google_maps_search_save_post( $post_id ) {

   // bail early if no ACF data
   if( empty($_POST['acf']) ) {
	   return;
   }	
   

	$address = get_field( 'address', $post_id );
	
	if( $address ){
			
		$acf_gms = new acf_gms;	
		
		$data = [
			'post_id'	=> $post_id,
			'lng'		=> $address['lng'],
			'lat'		=> $address['lat'],
		];
		
		$acf_gms->save( $data );		
		
	}
   

}




// Join for searching metadata
function acf_google_maps_search_join_to_WPQuery($join) {
	
    global $wpdb;
	
	$acf_gms = new acf_gms;	
	$table_name = $acf_gms->table_name();

    if ( 
		isset($_GET['lat']) && !empty($_GET['lat']) 
    	&& isset( $_GET['lng']) && !empty($_GET['lng']) 
    	 ) {
		
        $join .= " LEFT JOIN {$table_name} AS acf_gms_geo ON {$wpdb->posts}.ID = acf_gms_geo.post_id ";
		
    }

    return $join;
	
}
add_filter('posts_join', 'acf_google_maps_search_join_to_WPQuery');



// ORDER BY DISTANCE
function acf_google_maps_search_orderby_WPQuery($orderby) {

	
	 if ( 
		isset($_GET['lat']) && !empty($_GET['lat']) 
    	&& isset( $_GET['lng']) && !empty($_GET['lng']) 
    	 ) {
		
		$lat = sanitize_text_field( $_GET['lat'] );
		$lng = sanitize_text_field( $_GET['lng'] );
		
		$orderby = " (POW((acf_gms_geo.lng-{$lng}),2) + POW((acf_gms_geo.lat-{$lat}),2)) ASC";
		
	}
	
	return $orderby;
	
}
add_filter('posts_orderby', 'acf_google_maps_search_orderby_WPQuery');




