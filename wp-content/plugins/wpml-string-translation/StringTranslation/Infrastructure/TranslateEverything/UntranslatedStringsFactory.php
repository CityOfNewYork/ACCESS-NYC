<?php

namespace WPML\StringTranslation\Infrastructure\TranslateEverything;

use WPML\Infrastructure\WordPress\Port\Persistence\DatabaseWrite;
use WPML\Legacy\Component\Translation\Application\String\Repository\StringBatchRepository;

class UntranslatedStringsFactory {

	public function create(): UntranslatedStrings {
		global $wpdb, $sitepress;

		$stringBatchRepository = new StringBatchRepository(
			new DatabaseWrite( $wpdb ),
			$sitepress
		);

		return new UntranslatedStrings( $stringBatchRepository, $wpdb );

	}

}
