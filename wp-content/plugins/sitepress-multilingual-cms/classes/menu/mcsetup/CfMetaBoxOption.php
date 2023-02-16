<?php

namespace WPML\TM\Menu\McSetup;

use WPML\WP\OptionManager;

class CfMetaBoxOption {
	const GROUP = 'core';
	const CF_META_BOX_OPTION_KEY = 'show_cf_meta_box';

	/**
	 * @return boolean
	 */
	public static function get() {
		return OptionManager::getOr( false, self::GROUP, self::CF_META_BOX_OPTION_KEY );
	}

	/**
	 * @param boolean $value
	 */
	public static function update( $value ) {
		OptionManager::updateWithoutAutoLoad( self::GROUP, self::CF_META_BOX_OPTION_KEY, $value );
	}
}