<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Validator;

use WPML\StringTranslation\Application\StringGettext\Validator\IsExcludedDomainStringValidatorInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Validator\IsExcludedDomainString\FromAuthorsDomainValidator;
use WPML\StringTranslation\Infrastructure\StringGettext\Validator\IsExcludedDomainString\FromQueryMonitorDomainValidator;
use WPML\StringTranslation\Infrastructure\StringGettext\Validator\IsExcludedDomainString\FromWoocommerceDomainValidator;

class IsExcludedDomainStringValidator implements IsExcludedDomainStringValidatorInterface {

	/** @var IsExcludedDomainStringValidatorInterface[] */
	private $validators;

	public function __construct(
		FromAuthorsDomainValidator      $fromAuthorsDomainValidator,
		FromQueryMonitorDomainValidator $fromQueryMonitorDomainValidator,
		FromWoocommerceDomainValidator  $fromWoocommerceDomainValidator
	) {
		$this->validators = [
			$fromAuthorsDomainValidator,
			$fromQueryMonitorDomainValidator,
			$fromWoocommerceDomainValidator,
		];
	}

	public function validate( string $text, string $domain ): bool {
		foreach ( $this->validators as $validator ) {
			if ( $validator->validate( $text, $domain ) ) {
				return true;
			}
		}

		return false;
	}
}