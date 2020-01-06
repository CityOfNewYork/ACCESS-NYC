<?php

namespace WPML\ST\MO\File;


use SitePress;
use WPML\ST\MO\Generate\Process\ProcessFactory;
use function WPML\Container\make;
use WPML\ST\MO\Scan\UI\Factory as UiFactory;

class FailureHooksFactory implements \IWPML_Backend_Action_Loader {
	/**
	 * @return FailureHooks|null
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		/** @var SitePress $sitepress */
		global $sitepress;

		if ( $sitepress->is_setup_complete() && $this->hasRanPreGenerateViaUi() ) {
			$inBackground = true;

			return make( FailureHooks::class, [
				':status'        => ProcessFactory::createStatus( $inBackground ),
				':singleProcess' => ProcessFactory::createSingle( $inBackground ),
			] );
		}

		return null;
	}

	/**
	 * @return bool
	 * @throws \Auryn\InjectionException
	 */
	private function hasRanPreGenerateViaUi() {
		$uiPreGenerateStatus = ProcessFactory::createStatus( false );

		return $uiPreGenerateStatus->isComplete()
		       || UiFactory::isDismissed()
		       || ! ProcessFactory::createSingle()->getPagesCount();
	}
}