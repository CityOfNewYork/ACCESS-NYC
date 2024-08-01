<?php

namespace WordfenceLS;

class Utility_URL {
	
	/**
	 * Similar to WordPress' `admin_url`, this returns a host-relative URL for the given path. It may be used to avoid
	 * canonicalization issues with CORS (e.g., the site is configured for the www. variant of the URL but doesn't forward
	 * the other).
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function relative_admin_url($path = '') {
		$url = admin_url($path);
		$components = parse_url($url);
		$s = $components['path'];
		if (!empty($components['query'])) {
			$s .= '?' . $components['query'];
		}
		if (!empty($components['fragment'])) {
			$s .= '#' . $components['fragment'];
		}
		return $s;
	}
}