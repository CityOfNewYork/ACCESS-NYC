<?php

namespace WPML\ST\TranslationFile;

abstract class Builder {
	/** @var string $plural_form */
	protected $plural_form = 'nplurals=2; plural=n != 1;';
	/** @var string $language */
	protected $language;

	/**
	 * @param string $language
	 *
	 * @return Builder
	 */
	public function set_language( $language ) {
		$this->language = $language;

		return $this;
	}

	/**
	 * @param string $plural_form
	 *
	 * @return Builder
	 */
	public function set_plural_form( $plural_form ) {
		$this->plural_form = $plural_form;

		return $this;
	}

	/**
	 * @param StringEntity[] $strings
	 * @return string
	 */
	abstract public function get_content( array $strings);

}
