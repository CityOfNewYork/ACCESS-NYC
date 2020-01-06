<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\Gettext\Filters;

interface IFilter {

	/**
	 * @param string       $translation
	 * @param string       $text
	 * @param string|array $domain
	 * @param string|false $name
	 *
	 * @return string
	 */
	public function filter( $translation, $text, $domain, $name = false );
}
