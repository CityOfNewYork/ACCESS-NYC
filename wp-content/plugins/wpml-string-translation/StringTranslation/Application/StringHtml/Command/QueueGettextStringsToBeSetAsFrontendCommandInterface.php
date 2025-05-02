<?php

namespace WPML\StringTranslation\Application\StringHtml\Command;

interface QueueGettextStringsToBeSetAsFrontendCommandInterface {
	/**
	 * @param StringItem[] $gettextStrings
	 */
	public function run( array $gettextStrings );
}