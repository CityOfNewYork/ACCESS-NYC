<?php

class WPML_TM_ATE_Models_Job_Sender {
	public $id;
	public $email;
	public $username;
	public $displayName;


	/**
	 * @param int $id
	 * @param string $email
	 * @param string $username
	 * @param string $displayName
	 */
	public function __construct( $id, $email, $username, $displayName ) {
		$this->id          = $id;
		$this->email       = $email;
		$this->username    = $username;
		$this->displayName = $displayName;
	}


}
