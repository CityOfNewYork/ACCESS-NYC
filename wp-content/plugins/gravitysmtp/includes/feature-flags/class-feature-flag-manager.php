<?php

namespace Gravity_Forms\Gravity_SMTP\Feature_Flags;

use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;

class Feature_Flag_Manager {

	protected $repo;

	public function __construct( Feature_Flag_Repository $repo ) {
		$this->repo = $repo;
	}

	public function __call( $name, $arguments ) {
		if ( ! method_exists( $this->repo, $name ) ) {
			return null;
		}

		return call_user_func_array( array( $this->repo, $name ), $arguments );
	}

	public static function __callStatic( $name, $arguments ) {
		$self = Gravity_SMTP::$container->get( Feature_Flags_Service_Provider::FEATURE_FLAG_MANAGER );
		if ( ! method_exists( $self->repo, $name ) ) {
			return null;
		}

		return call_user_func_array( array( $self->repo, $name ), $arguments );
	}

}
