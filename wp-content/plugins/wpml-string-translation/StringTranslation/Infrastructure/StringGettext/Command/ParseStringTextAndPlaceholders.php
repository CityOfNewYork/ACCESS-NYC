<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

class ParseStringTextAndPlaceholders {

	/** @var ParsePlaceholders */
	private $parsePlaceholders;

	public function __construct(
		ParsePlaceholders $parsePlaceholders
	) {
		$this->parsePlaceholders = $parsePlaceholders;
	}

	public function run( string $string ): array {
		$placeholders = $this->parsePlaceholders->run( $string );
		$nodes        = [];

		$currIndex = 0;
		$lastIndex = strlen( $string ) - 1;

		foreach ( $placeholders as $placeholder ) {
			if ( $placeholder['offset'] > $currIndex ) {
				$nodes[] = [
					'type'      => 'text',
					'text'      => substr( $string, $currIndex, $placeholder['offset'] - $currIndex ),
					'offset'    => $currIndex,
					'offsetEnd' => $placeholder['offset'] - 1,
				];
			}

			$nodes[]   = $placeholder;
			$currIndex = $placeholder['offsetEnd'] + 1;
		}

		if ( $currIndex <= $lastIndex ) {
			$nodes[] = [
				'type'      => 'text',
				'text'      => substr( $string, $currIndex ),
				'offset'    => $currIndex,
				'offsetEnd' => $lastIndex,
			];
		}

		return $nodes;
	}
}