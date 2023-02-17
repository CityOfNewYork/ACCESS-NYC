<?php

namespace WPML\ST\MO\Scan\UI;

use WPML\Collect\Support\Collection;
use WPML\ST\MO\Generate\MultiSite\Condition;
use WPML\ST\TranslationFile\EntryQueries;

class Model {

	/**
	 * @param Collection $files_to_scan
	 * @param int        $domains_to_pre_generate_count
	 * @param bool       $is_st_page
	 * @param bool       $is_network_admin
	 *
	 * @return \Closure
	 */
	public static function provider(
		Collection $files_to_scan,
		$domains_to_pre_generate_count,
		$is_st_page,
		$is_network_admin
	) {
		return function () use ( $files_to_scan, $domains_to_pre_generate_count, $is_st_page ) {
			return [
				'files_to_scan'                 => [
					'count'   => $files_to_scan->count(),
					'plugins' => self::filterFilesByType( $files_to_scan, 'plugin' ),
					'themes'  => self::filterFilesByType( $files_to_scan, 'theme' ),
					'other'   => self::filterFilesByType( $files_to_scan, 'other' ),
				],
				'domains_to_pre_generate_count' => $domains_to_pre_generate_count,
				'file_path'                     => WP_LANG_DIR . '/wpml',
				'is_st_page'                    => $is_st_page,
				'admin_texts_url'               => 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&trop=1',
				'is_search'                     => isset( $_GET['search'] ),
				'run_ror_all_sites'             => ( new Condition() )->shouldRunWithAllSites(),
			];
		};
	}

	/**
	 * @param Collection $files_to_scan
	 * @param string     $type
	 *
	 * @return array
	 */
	private static function filterFilesByType( Collection $files_to_scan, $type ) {
		return $files_to_scan->filter( EntryQueries::isType( $type ) )
		                     ->map( EntryQueries::getResourceName() )
		                     ->unique()
		                     ->values()
		                     ->toArray();
	}

}
