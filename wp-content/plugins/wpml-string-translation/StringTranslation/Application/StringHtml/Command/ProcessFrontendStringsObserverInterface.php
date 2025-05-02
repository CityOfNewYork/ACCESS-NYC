<?php

namespace WPML\StringTranslation\Application\StringHtml\Command;

interface ProcessFrontendStringsObserverInterface {

	/**
	 * @param int[] $stringIds
	 *
	 * @return void
	 */
	public function newFrontendStringsRegistered( array $stringIds );

}
