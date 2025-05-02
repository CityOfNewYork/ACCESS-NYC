<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Validator\IsExcludedDomainString;

use WPML\StringTranslation\Application\StringGettext\Validator\IsExcludedDomainStringValidatorInterface;

class FromWoocommerceDomainValidator implements IsExcludedDomainStringValidatorInterface {

	public function validate( string $text, string $domain ): bool {
		// Woocommerce metadata(no real strings).
		return substr($text, 0, strlen('woocommerce_')) === 'woocommerce_';
	}
}