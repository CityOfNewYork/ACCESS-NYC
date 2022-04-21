<?php

namespace WPML\Element\API\Entity;

use WPML\FP\Lst;

class LanguageMapping {
	/** @var string */
	private $sourceCode;
	/** @var string */
	private $sourceName;
	/** @var int */
	private $targetId;
	/** @var string */
	private $targetCode;

	/**
	 * @param string $sourceCode
	 * @param string $sourceName
	 * @param int $targetId
	 * @param string $targetCode
	 */
	public function __construct( $sourceCode = null, $sourceName = null, $targetId = null, $targetCode = null ) {
		$this->sourceCode = $sourceCode;
		$this->sourceName = $sourceName;
		$this->targetId   = (int) $targetId;
		$this->targetCode = $targetCode;
	}

	/**
	 * @return array
	 */
	public function toATEFormat () {
		return [
			'source_language' => [ 'code' => $this->sourceCode, 'name' => $this->sourceName ],
			'target_language' => [ 'id' => $this->targetId, 'code' => $this->targetCode ],
		];
	}

	public function __get( $name ) {
		return isset( $this->$name ) ? $this->$name : null;
	}

	public function __isset( $name ) {
		return Lst::includes( $name, array_keys( get_object_vars( $this ) ) );
	}

	public function matches( $languageCode ) {
		return strtolower( $this->sourceCode ) === strtolower( $languageCode );
	}
}