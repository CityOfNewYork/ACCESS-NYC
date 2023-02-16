<?php

namespace WPML\TM\ATE\API;


class ErrorMessages {


	public static function serverUnavailable( $uuid ) {
		return [
			'header'      => self::serverUnavailableHeader(),
			'description' => self::invalidResponseDescription( $uuid ),
		];
	}

	public static function offline( $uuid ) {
		$description = _x( 'WPML needs an Internet connection to translate your site’s content. It seems that your server is not allowing external connections, or your network is temporarily down.', 'part1', 'wpml-translation-management' );
		$description .= _x( 'If this is the first time you’re seeing this message, please wait a minute and reload the page. If the problem persists, contact %1$s for help and mention that your website ID is %2$s.', 'part2', 'wpml-translation-management' );

		return [
			'header'      => __( 'Cannot Connect to the Internet', 'wpml-translation-management' ),
			'description' => sprintf( $description, self::getSupportLink(), $uuid ),
		];
	}

	public static function invalidResponse( $uuid ) {
		return [
			'header'      => __( 'WPML’s Advanced Translation Editor is not working', 'wpml-translation-management' ),
			'description' => self::invalidResponseDescription( $uuid ),
		];
	}

	public static function respondedWithError() {
		return __( "WPML's Advanced Translation Editor responded with an error", 'wpml-translation-management' );
	}

	public static function serverUnavailableHeader() {
		return __( 'WPML’s Advanced Translation Editor is not responding', 'wpml-translation-management' );
	}

	public static function invalidResponseDescription( $uuid ) {
		$description = _x( 'WPML cannot connect to the translation editor. If this is the first time you’re seeing this message, please wait a minute and reload the page.', 'part1', 'wpml-translation-management' );
		$description .= _x( 'If the problem persists, contact %1$s for help and mention that your website ID is %2$s.', 'part2', 'wpml-translation-management' );

		return sprintf( $description, self::getSupportLink(), $uuid );
	}

	public static function getSupportLink() {
		return '<a href="https://wpml.org/forums/forum/english-support/" target="_blank" rel="noreferrer">'
		       . __( 'WPML support', 'wpml-translation-management' ) . '</a>';
	}

	public static function bodyWithoutRequiredFields() {
		return __( 'The body does not contain the required fields', 'wpml-translation-management' );
	}

	public static function uuidAlreadyExists() {
		return __( 'UUID already exists', 'wpml-translation-management' );
	}
}