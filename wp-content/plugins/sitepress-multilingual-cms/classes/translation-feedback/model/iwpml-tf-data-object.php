<?php

/**
 * Interface IWPML_TF_Data_Object
 *
 * @author OnTheGoSystems
 */
interface IWPML_TF_Data_Object {

	/**
	 * @return int
	 */
	public function get_id();

	/**
	 * @return int|null
	 */
	public function get_feedback_id();

	/**
	 * @param \WPML_TF_Message $message
	 */
	public function add_message( WPML_TF_Message $message );
}