<?php

namespace Gravity_Forms\Gravity_SMTP\Feature_Flags;

use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;

class Feature_Flag_Repository {

	const FILTER_ALL_FEATURE_FLAGS   = 'gravitysmtp_feature_flags';
	const FILTER_SINGLE_FEATURE_FLAG = 'gravitysmtp_feature_flag';

	public function flags() {
		return apply_filters( self::FILTER_ALL_FEATURE_FLAGS, array() );
	}

	public function add( $flag_slug, $flag_label ) {
		add_filter( self::FILTER_ALL_FEATURE_FLAGS, function( $flags ) use ( $flag_slug, $flag_label ) {
			$flags[ $flag_slug ] = $flag_label;

			return $flags;
		} );
	}

	public function is_enabled( $flag_slug ) {
		return apply_filters( self::FILTER_SINGLE_FEATURE_FLAG, false, $flag_slug );
	}

	public function enable_flag( $flag_slug, $priority = PHP_INT_MAX ) {
		add_filter( self::FILTER_SINGLE_FEATURE_FLAG, function ( $is_enabled, $checked_flag ) use ( $flag_slug ) {
			if ( $checked_flag === $flag_slug ) {
				return true;
			}

			return $is_enabled;
		}, $priority, 2 );
	}

	public function disable_flag( $flag_slug, $priority = PHP_INT_MAX ) {
		add_filter( self::FILTER_SINGLE_FEATURE_FLAG, function ( $is_enabled, $checked_flag ) use ( $flag_slug ) {
			if ( $checked_flag === $flag_slug ) {
				return false;
			}

			return $is_enabled;
		}, $priority, 2 );
	}

	public function enabled_flags() {
		$all_flags = $this->flags();
		$self      = $this;

		return array_filter( $all_flags, function ( $flag_slug ) use ( $self ) {
			return $this->is_enabled( $flag_slug );
		}, ARRAY_FILTER_USE_KEY );
	}

	public function flags_by_status() {
		$all_flags = $this->flags();
		$response  = array();

		foreach ( $all_flags as $flag_slug => $label ) {
			$response[ $flag_slug ] = $this->is_enabled( $flag_slug );
		}

		return $response;
	}
}