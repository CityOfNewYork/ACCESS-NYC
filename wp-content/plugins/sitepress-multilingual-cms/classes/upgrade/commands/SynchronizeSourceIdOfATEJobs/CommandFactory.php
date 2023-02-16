<?php

namespace WPML\TM\Upgrade\Commands\SynchronizeSourceIdOfATEJobs;

use WPML\Utils\Pager;
use function WPML\Container\make;

class CommandFactory {

	const PAGER_OPTION_NAME = 'sync-source-id-ate-jobs-pager';

	/**
	 * @return Command
	 */
	public function create() {
		return make( Command::class, [ ':pager' => new Pager( self::PAGER_OPTION_NAME, 1 ) ] );
	}
}
