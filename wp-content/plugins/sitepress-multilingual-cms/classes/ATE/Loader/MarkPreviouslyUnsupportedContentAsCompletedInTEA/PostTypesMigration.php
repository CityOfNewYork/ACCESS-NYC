<?php

namespace WPML\TM\ATE\Loader\MarkPreviouslyUnsupportedContentAsCompletedInTEA;

use WPML\API\PostTypes;
use WPML\TM\ATE\TranslateEverything\UntranslatedPosts;

class PostTypesMigration {

	/** @var UntranslatedPosts */
	private $untranslatedPosts;

	/** @var ExecutionStatus */
	private $executionStatus;


	public function __construct(
		UntranslatedPosts $untranslatedPosts,
		ExecutionStatus $executionStatus
	) {
		$this->untranslatedPosts = $untranslatedPosts;
		$this->executionStatus   = $executionStatus;
	}

	/**
	 * It marks all `display as translated` post types as completed in TEA.
	 * It is identical action as when a user enables TEA and chooses to translate only new content.
	 * In this case, existing content is not translated.
	 *
	 * At the end, we mark the migration as done.
	 *
	 * @return void
	 */
	public function run() {
		$postTypes = PostTypes::getDisplayAsTranslated();

		foreach ( $postTypes as $postType ) {
			$this->untranslatedPosts->markTypeAsCompleted( $postType );
		}

		$this->executionStatus->markPostTypesAsExecuted();
	}

}
