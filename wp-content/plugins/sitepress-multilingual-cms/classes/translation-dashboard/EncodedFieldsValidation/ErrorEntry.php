<?php

namespace WPML\TM\TranslationDashboard\EncodedFieldsValidation;

/**
 * @template field of array{title:string, content:string}
 */
class ErrorEntry {
	/** @var int ID of post or package */
	public $elementId;

	/** @var string */
	public $elementTitle;

	/** @var field[] */
	public $fields;

	/**
	 * @param int     $elementId
	 * @param string  $elementTitle
	 * @param field[] $fields
	 */
	public function __construct( $elementId, $elementTitle, $fields ) {
		$this->elementId    = (int) $elementId;
		$this->elementTitle = $elementTitle;
		$this->fields       = $fields;
	}
}
