<?php

namespace OTGS\Installer\AdminNotices\Notices;

use OTGS\Installer\Recommendations\Storage;

class Dismissions {
	/**
	 * @param array $dismissed already dismissed notices.
	 * @param array $data dismissed notice parameters.
	 *
	 * @return array
	 */
	public static function dismissAccountNotice( $dismissed, $data ) {
		$dismissed['repo'][ $data['repository'] ][ $data['noticeType'] ] = time();

		return $dismissed;
	}

	/**
	 * @param array $dismissed already dismissed notices.
	 * @param array $data dismissed notice parameters.
	 *
	 * @return array
	 */
	public static function dismissRecommendationNotice( $dismissed, $data ) {
		Storage::delete( $data['noticePluginSlug'], $data['repository'] );

		return $dismissed;
	}
}