<?php

namespace Gravity_Forms\Gravity_SMTP\Models\Hydrators;

class Hydrator_Factory {

	/**
	 * @param $type
	 *
	 * @return Hydrator
	 */
	public function create( $type ) {
		$class_postfix = $type === 'wp_mail' ? 'WP_Mail' : ucfirst( $type );
		$classname     = sprintf( '%s\Hydrator_%s', __NAMESPACE__, $class_postfix );

		if ( ! class_exists( $classname ) ) {
			throw new \InvalidArgumentException( 'Hydrator type for type ' . $type . ' with class ' . $classname . ' does not exist.' );
		}

		return new $classname();
	}

}