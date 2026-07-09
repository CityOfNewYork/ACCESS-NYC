<?php

namespace WPML\StringTranslation\Application\StringHtml\Validator;

interface IsExcludedHtmlStringValidatorInterface {

	public function validate( string $text ): bool;
}