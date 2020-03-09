<?php

namespace WPML\ST\Upgrade\Command;

use WPML\ST\MO\File\Manager;
use WPML\ST\MO\Generate\Process\SingleSiteProcess;
use WPML\ST\MO\Generate\Process\Status;
use WPML\ST\MO\Notice\RegenerationInProgressNotice;
use WPML_Installation;
use function WPML\Container\make;

class RegenerateMoFilesWithStringNames implements \IWPML_St_Upgrade_Command {

	const WPML_VERSION_FOR_THIS_COMMAND = '4.3.4';

	/** @var Status $status */
	private $status;

	/** @var SingleSiteProcess $singleProcess */
	private $singleProcess;

	/**
	 * @param Status            $status
	 * @param SingleSiteProcess $singleProcess We use run the single site process because
	 *                                         the migration command runs once per site.
	 */
	public function __construct( Status $status, SingleSiteProcess $singleProcess ) {
		$this->status        = $status;
		$this->singleProcess = $singleProcess;
	}

	public function run() {
		if ( ! ( $this->hasWpmlStartedBeforeThisCommand() && Manager::hasFiles() ) ) {
			$this->status->markComplete();
			return true;
		}

		$this->singleProcess->runPage();

		if ( $this->singleProcess->isCompleted() ) {
			\wpml_get_admin_notices()->remove_notice(
				RegenerationInProgressNotice::GROUP,
				RegenerationInProgressNotice::ID
			);

			return true;
		}

		\wpml_get_admin_notices()->add_notice( make( RegenerationInProgressNotice::class ) );

		return false;
	}

	/**
	 * @return bool
	 */
	private function hasWpmlStartedBeforeThisCommand() {
		return (bool) version_compare(
			get_option( WPML_Installation::WPML_START_VERSION_KEY, '0.0.0' ),
			self::WPML_VERSION_FOR_THIS_COMMAND,
			'<'
		);
	}

	public function run_ajax() {

	}

	public function run_frontend() {

	}

	public static function get_command_id() {
		return __CLASS__;
	}
}
