<?php

namespace WPML\Core\Twig\Extension;

class ConstantExtension extends \WPML\Core\Twig\Extension\AbstractExtension {
    public function getFunctions() {
		return [
			new \WPML\Core\Twig\TwigFunction( 'constant', [ $this, 'constant' ] ),
		];
    }

	public function constant( $constant ) {
		$protected_constants = [
			'DB_HOST',
			'DB_NAME',
			'DB_USER',
			'DB_PASSWORD',
			'DB_SOCKET',
			'DB_CHARSET',
			'DB_COLLATE',
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_KEY',
			'NONCE_SALT',
		];

		// Use foreach+strpos instead of in_array to not miss patterns like \AUTH_KEY.
		foreach ( $protected_constants as $protected_constant ) {
			if ( strpos( $constant, $protected_constant ) !== false ) {
				return '***';
			}
		}

		return constant( $constant );
	}
}
