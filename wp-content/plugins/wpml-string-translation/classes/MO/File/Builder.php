<?php


namespace WPML\ST\MO\File;

use WPML\ST\TranslationFile\StringEntity;

class Builder extends \WPML\ST\TranslationFile\Builder {

	/** @var Generator */
	private $generator;

	public function __construct( Generator $generator ) {
		$this->generator = $generator;
	}
	
	/**
	 * @param StringEntity[] $strings
	 * @return string
	 */
	public function get_content( array $strings ) {
		return $this->generator->getContent( $strings );
	}
}