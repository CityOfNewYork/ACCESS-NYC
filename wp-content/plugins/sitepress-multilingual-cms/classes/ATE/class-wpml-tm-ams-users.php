<?php

use WPML\User\UsersByCapsRepository;
use WPML\LIB\WP\User;
class WPML_TM_AMS_Users {

	/** @var UsersByCapsRepository */
	private $userByCapsRepository;

	public function __construct( UsersByCapsRepository $userByCapsRepository ) {
		$this->userByCapsRepository = $userByCapsRepository;
	}

	public function get_translators() {
		return $this->userByCapsRepository->get( [ User::CAP_TRANSLATE, User::CAP_ADMINISTRATOR ] );
	}

	public function get_managers() {
		return $this->userByCapsRepository->get( [ User::CAP_MANAGE_TRANSLATIONS, User::CAP_ADMINISTRATOR ] );
	}
}
