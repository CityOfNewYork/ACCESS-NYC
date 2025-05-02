<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Validator\IsExcludedDomainString;

use WPML\StringTranslation\Application\StringGettext\Validator\IsExcludedDomainStringValidatorInterface;

class FromQueryMonitorDomainValidator implements IsExcludedDomainStringValidatorInterface {

	public function validate( string $text, string $domain ): bool {
		return $domain === 'query-monitor' || substr($text, 0, strlen('number_format')) === 'number_format';
	}
}