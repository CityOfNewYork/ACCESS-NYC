<?php

class WPML_ACF_Location_Rules {
	public function __construct() {
		add_filter( 'acf/location/rule_match', array( $this, 'rule_match_post' ), 11, 3 );
	}

	public function rule_match_post($match, $rule, $options) {

		global $sitepress;
		$custom_posts_sync_option = $sitepress->get_setting( 'custom_posts_sync_option', array() );

		if ( isset( $rule['param'] ) && in_array( $rule['param'], get_post_types( '', 'names' ) ) ) {
			if (!isset($custom_posts_sync_option['acf-field-group']) || 0 == $custom_posts_sync_option['acf-field-group']) {
				if (isset ($options['post_id']) && isset($options['post_type'])) {

					$default_language = apply_filters('wpml_default_language', null);

					$options['post_id'] = apply_filters('wpml_object_id', $options['post_id'], $options['post_type'], true, $default_language);

					if($rule['operator'] == "==")
					{
						$match = ( $options['post_id'] == $rule['value'] );
					}
					elseif($rule['operator'] == "!=")
					{
						$match = ( $options['post_id'] != $rule['value'] );
					}
				}
			}
		}

		return $match;
	}

}