<?php 

class acf_gms {
	
	protected $table;
	
	function __construct() {
		
		global $wpdb;
		
		$this->table = $wpdb->prefix . "acf_google_map_search_geodata"; 
		
	}
	
	
	
	/**
	 * Insert geodata into table
	 */
	function insert( $data ) {
		global $wpdb;
	 
		$wpdb->insert(
			$this->table,
			array(
				'post_id' => $data['post_id'],
				'lat'     => $data['lat'],
				'lng'     => $data['lng'],
			),
			array(
				'%d',
				'%f',
				'%f'
			)
		);
		
		return true;
		
	}	
	
	
	/**
	 * Checks if entry for post_id exists
	 */
	function check_exists($data) {
	 
		global $wpdb;
	 
		//Check data validity
		if( !is_int($data['post_id']) ){
			return false;
		}
	 
		$sql = "SELECT * FROM $this->table WHERE post_id = {$data['post_id']}";
		$geodata = $wpdb->get_row($sql);
	 
		 if($geodata) {
			return true;
		 }
		 
		 return false;
		 
	}	


	/**
	 * Delete entry for post_id
	 */
	function delete($post_id) {
	 
		global $wpdb;

		//Check date validity
		if( !is_int($post_id) ){
			return false;
		}
	 
		$delete = $wpdb->delete( $this->table, array( 'post_id' => $post_id ) );
	 
		return $delete;
	}	
	
	/**
	 * Empty table
	 */
	function empty_table() {
	 
		global $wpdb;

		$empty = $wpdb->query( "TRUNCATE TABLE {$this->table}" );
	 
		return $empty;
	}	
	
	/**
	 * Update existing
	 */
	function update($data) {
	 
		global $wpdb;	 
	 
		$wpdb->update(
			$this->table,
			array(
				'lat'     => $data['lat'],
				'lng'     => $data['lng'],
			),
			array(
				'post_id' => $data['post_id'],
			),
			array(
				'%f',
				'%f'
			)
		);
		
		return true;
		
	}	
	
	
	/**
	 * Insert or update current post geodata
	 */
	function save( $data ) {

	 
		  /**
		   * Check if geodata exists and update if exists else insert
		   */
		  if( $this->check_exists( $data ) ) {
				$return = $this->update( $data );
		  } else {
				$return = $this->insert( $data );
		  }
		  
		  return $return;
		  
	}	
	
	
	function table_name(){
		return $this->table;
	}
	
	
	/*
	* update table with historic data
	*/
	function update_historic_post_gm_data() {
		
		$this->empty_table();
		
		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'any', 
		);
		$posts = get_posts( $args );

		if($posts):
			foreach($posts as $item):
			
				$address = get_field( 'address', $item->ID );
				
				if( $address ) {
				
					$data = [
						'post_id'		=> $item->ID,
						'lng'			=> $address['lng'],
						'lat'			=> $address['lat'],
					];
					
					$this->insert( $data );
					
				}	
				
			endforeach;
		endif;
	   
	}	
	
}