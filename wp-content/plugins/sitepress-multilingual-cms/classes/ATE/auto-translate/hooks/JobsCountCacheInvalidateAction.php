<?php

namespace WPML\TM\ATE\AutoTranslate\Hooks;

use WPML\LIB\WP\Transient;
use WPML\TM\ATE\AutoTranslate\Repository\CachedJobsCount;

class JobsCountCacheInvalidateAction implements \IWPML_Backend_Action, \IWPML_REST_Action {
	public function add_hooks() {
		$clearCache = function() {
			Transient::delete( CachedJobsCount::CACHE_KEY );
		};

		add_action( 'wpml_tm_ate_jobs_created', $clearCache, 10, 0 );
		add_action( 'wpml_tm_ate_jobs_downloaded', $clearCache, 10, 0 );
	}


}
