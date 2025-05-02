<?php

namespace WPML\StringTranslation\Application\StringGettext\Validator;

interface IsExcludedDomainStringValidatorInterface {

	public function validate( string $text, string $domain ): bool;
}