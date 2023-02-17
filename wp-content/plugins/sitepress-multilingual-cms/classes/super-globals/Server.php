<?php

namespace WPML\SuperGlobals;

use WPML\FP\Obj;

class Server {

	public static function getServerName() {
		return filter_var( Obj::prop( 'HTTP_HOST', $_SERVER ), FILTER_SANITIZE_URL )
			?: filter_var( Obj::prop( 'SERVER_NAME', $_SERVER ), FILTER_SANITIZE_URL );
	}
}
