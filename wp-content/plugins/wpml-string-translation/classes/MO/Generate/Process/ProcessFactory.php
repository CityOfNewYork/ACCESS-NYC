<?php

namespace WPML\ST\MO\Generate\Process;

use WPML\ST\MO\File\ManagerFactory;
use WPML\ST\MO\Generate\MultiSite\Condition;
use WPML\Utils\Pager;
use function WPML\Container\make;

class ProcessFactory {
	const FILES_PAGER     = 'wpml-st-mo-generate-files-pager';
	const FILES_PAGE_SIZE = 20;
	const SITES_PAGER     = 'wpml-st-mo-generate-sites-pager';

	/** @var Condition */
	private $multiSiteCondition;

	/**
	 * @param Condition $multiSiteCondition
	 */
	public function __construct( Condition $multiSiteCondition = null ) {
		$this->multiSiteCondition = $multiSiteCondition ?: new Condition();
	}

	/**
	 * @return Process
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function create() {
		$singleSiteProcess = self::createSingle();

		if ( $this->multiSiteCondition->shouldRunWithAllSites() ) {
			return make( MultiSiteProcess::class,
				[ ':singleSiteProcess' => $singleSiteProcess, ':pager' => new Pager( self::SITES_PAGER, 1 ) ]
			);
		} else {
			return $singleSiteProcess;
		}
	}

	/**
	 * @param bool $isBackgroundProcess
	 *
	 * @return SingleSiteProcess
	 * @throws \WPML\Auryn\InjectionException
	 */
	public static function createSingle( $isBackgroundProcess = false ) {
		return make(
			SingleSiteProcess::class,
			[
				':pager'             => new Pager( self::FILES_PAGER, self::FILES_PAGE_SIZE ),
				':manager'           => ManagerFactory::create(),
				':migrateAdminTexts' => \WPML_Admin_Texts::get_migrator(),
				':status'            => self::createStatus( $isBackgroundProcess ),
			]
		);
	}

	/**
	 * @param bool $isBackgroundProcess
	 *
	 * @return mixed|\Mockery\MockInterface|Status
	 * @throws \WPML\Auryn\InjectionException
	 */
	public static function createStatus( $isBackgroundProcess = false ) {
		return make( Status::class, [
			':optionPrefix' => $isBackgroundProcess ? Status::class . '_background' : null
		] );
	}
}
