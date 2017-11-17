<?php 
/**
 *
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */

class PMAI_Admin_Import extends PMAI_Controller_Admin 
{		
		
	public function index( $post_type = 'post', $post ) 
	{			
		
		$this->data['post_type'] = $post_type;

		$this->data['post'] =& $post;
		
		$this->render();

	}			
}
