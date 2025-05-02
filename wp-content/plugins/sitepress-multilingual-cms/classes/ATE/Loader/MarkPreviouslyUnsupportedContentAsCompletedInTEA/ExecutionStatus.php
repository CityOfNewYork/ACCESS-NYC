<?php

namespace WPML\TM\ATE\Loader\MarkPreviouslyUnsupportedContentAsCompletedInTEA;

use WPML\WP\OptionManager;

class ExecutionStatus {

	const GROUP = 'tea_previously_unsupported_content';

	const POST_TYPES_EXECUTED = 'post_types_executed';

	const PACKAGES_EXECUTED = 'packages_executed';

	/** @var OptionManager */
	private $optionManager;

	public function __construct( OptionManager $optionManager ) {
		$this->optionManager = $optionManager;
	}


	public function isFullyExecuted(): bool {
		return $this->arePostTypesExecuted() && $this->arePackagesExecuted();
	}

	public function arePostTypesExecuted(): bool {
		return (bool) $this->optionManager->get( self::GROUP, self::POST_TYPES_EXECUTED, false );
	}

	public function arePackagesExecuted(): bool {
		return (bool) $this->optionManager->get( self::GROUP, self::PACKAGES_EXECUTED, false );
	}

	public function markPostTypesAsExecuted() {
		$this->optionManager->set( self::GROUP, self::POST_TYPES_EXECUTED, true );
	}

	public function markPackagesAsExecuted() {
		$this->optionManager->set( self::GROUP, self::PACKAGES_EXECUTED, true );
	}

}
