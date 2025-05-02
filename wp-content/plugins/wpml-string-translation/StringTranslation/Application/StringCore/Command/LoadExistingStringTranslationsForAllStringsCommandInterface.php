<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

interface LoadExistingStringTranslationsForAllStringsCommandInterface {

	public function run( array $criteria = [] );
}