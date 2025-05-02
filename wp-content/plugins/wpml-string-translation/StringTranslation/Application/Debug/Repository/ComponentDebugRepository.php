<?php

namespace WPML\StringTranslation\Application\Debug\Repository;

use WPML\StringTranslation\Infrastructure\StringCore\Repository\ComponentRepository;

class ComponentDebugRepository {
	public static $callsCount     = 0;
	public static $tracesCount    = 0;
	public static $isDebugTraceOn = false;

	public static function writeTraceResultsToFile( $text, $domain, $context, $trace, $index, $filepath, $cmpId, $cmpType, $isAdmin ) {
		$directory = WP_LANG_DIR . '/wpml/queue/debug/';
		if ( ! file_exists( $directory ) ) {
			mkdir( $directory, 0777, true );
		}
//self::$isDebugTraceOn = true;
		if ( ! self::$isDebugTraceOn ) {
			return;
		}

		$res = "";
		foreach ( $trace as $item ) {
			$fn = '';
			if ( array_key_exists( 'line', $item ) ) {
				$fn .= $item['line'] . ': ';
			}
			if ( array_key_exists( 'file', $item ) ) {
				$fn .= $item['file'];
			}

			$res .= $fn . ' ' . $item['function'];
			$res .= ( array_key_exists('class', $item ) ) ? ' Class: ' . $item['class'] : '';
			$res .= "\n";
		}

		$ref = wp_get_referer();
		$res = 'ref: ' . $ref . ' index: ' . $index . ' filepath: ' . $filepath . "\n\n" . $res;

		$type = $isAdmin ? 'backend' : 'frontend';
		$ajax = wpml_is_ajax() ? 'ajax' : 'notajax';

		$filename  = $domain . '_' . $type . $ajax . '_' . $context . '_' . $text;
		$filename .= '--' . $cmpId . '_' . $cmpType;
		$filename  = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
		if ( strlen( $filename ) > 250 ) {
			$filename = substr( $filename, 0, 250 );
		}
		$filename .= '.txt';

		file_put_contents( $directory . $filename, $res );
	}
}