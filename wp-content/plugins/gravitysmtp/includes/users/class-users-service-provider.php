<?php

namespace Gravity_Forms\Gravity_SMTP\Users;

use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Users_Service_Provider extends Service_Provider {

	const ROLES = 'roles';

	public function register( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		$container->add( self::ROLES, function() {
			return new Roles();
		});
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		$container->get( self::ROLES )->register();
	}

}