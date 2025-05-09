<?php

class OTGS_Installer_Subscription {

	const WPML_SUBSCRIPTION_TYPE_BLOG = 6718;

	const SUBSCRIPTION_STATUS_INACTIVE = 0;
	const SUBSCRIPTION_STATUS_ACTIVE = 1;

	const SUBSCRIPTION_STATUS_EXPIRED = 2;
	const SUBSCRIPTION_STATUS_INACTIVE_UPGRADED = 3;
	const SUBSCRIPTION_STATUS_ACTIVE_NO_EXPIRATION = 4;

	const SITE_KEY_TYPE_PRODUCTION = 0;
	const SITE_KEY_TYPE_DEVELOPMENT = 1;

	const SUBSCRIPTION_STATUS_TEXT_EXPIRED = 'expired';
	const SUBSCRIPTION_STATUS_TEXT_VALID = 'valid';
	const SUBSCRIPTION_STATUS_TEXT_REFUNDED = 'refunded';
	const SUBSCRIPTION_STATUS_TEXT_MISSING = 'missing';

	private $status;
	private $expires;
	private $site_key;
	private $site_key_type;
	private $site_url;
	private $type;
	private $registered_by;
	private $data;
	private $notes;

	/**
	 * WPML_Installer_Subscription constructor.
	 *
	 * @param array|null $subscription
	 */
	public function __construct( $subscription = array() ) {
		if ( $subscription ) {

			if ( isset( $subscription['data'] ) ) {
				$this->data = $subscription['data'];
			}

			if ( isset( $subscription['data']->status ) ) {
				$this->status = (int) $subscription['data']->status;
			}

			if ( isset( $subscription['data']->expires ) ) {
				$this->expires = $subscription['data']->expires;
			}

			if ( isset( $subscription['data']->notes ) ) {
				$this->notes = $subscription['data']->notes;
			}

			if ( isset( $subscription['key'] ) ) {
				$this->site_key = $subscription['key'];
			}

			if ( isset( $subscription['site_url'] ) ) {
				$this->site_url = $subscription['site_url'];
			}

			if ( isset( $subscription['registered_by'] ) ) {
				$this->registered_by = $subscription['registered_by'];
			}

			if ( isset( $subscription['data']->subscription_type ) ) {
				$this->type = $subscription['data']->subscription_type;
			}

			$this->site_key_type = isset($subscription['key_type'])
				? $subscription['key_type'] : self::SITE_KEY_TYPE_PRODUCTION;
		}
	}

	public function get_subscription_status_text() {
		if ( $this->is_expired() ) {
			return self::SUBSCRIPTION_STATUS_TEXT_EXPIRED;
		}

		if ( $this->is_valid() ) {
			return self::SUBSCRIPTION_STATUS_TEXT_VALID;
		}

		if ( $this->is_refunded() ) {
			return self::SUBSCRIPTION_STATUS_TEXT_REFUNDED;
		}

		return self::SUBSCRIPTION_STATUS_TEXT_MISSING;
	}

	/**
	 * @param int $expiredForPeriod
	 * @return bool
	 */
	private function is_expired( $expiredForPeriod = 0 ) {
		return ! $this->is_lifetime()
		       && (
			       self::SUBSCRIPTION_STATUS_EXPIRED === $this->get_status()
			       || ( $this->get_expiration() && strtotime( $this->get_expiration() ) <= time() - $expiredForPeriod )
		       );
	}

	/**
	 * Check if the subscription is a WPML blog subscription.
	 * @return bool
	 */
	public function is_wpml_blog_subscription() {
		return $this->type === self::WPML_SUBSCRIPTION_TYPE_BLOG;
	}

	/**
	 * @return bool
	 */
	private function is_lifetime() {
		return $this->get_status() === self::SUBSCRIPTION_STATUS_ACTIVE_NO_EXPIRATION;
	}

	private function get_status() {
		return $this->status;
	}

	private function get_expiration() {
		return $this->expires;
	}

	public function get_site_key() {
		return $this->site_key;
	}

	public function get_site_url() {
		return $this->site_url;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_site_key_type() {
		return $this->site_key_type;
	}

	public function get_registered_by() {
		return $this->registered_by;
	}

	public function get_data() {
		return $this->data;
	}

	/**
	 * @param int $expiredForPeriod
	 * @return bool
	 */
	public function is_valid( $expiredForPeriod = 0 ) {
		return ( $this->is_lifetime()
		         || ( $this->get_status() === self::SUBSCRIPTION_STATUS_ACTIVE && ! $this->is_expired( $expiredForPeriod ) ) );
	}

	/**
	 * @param int $expiredForPeriod
	 * @return bool
	 */
	public function is_in_grace( $expiredForPeriod = 0 ) {
		return ! $this->is_lifetime()
			&& (
				self::SUBSCRIPTION_STATUS_ACTIVE === $this->get_status()
				&& ( $this->get_expiration() &&
					( strtotime( $this->get_expiration() ) >= time() - $expiredForPeriod &&
						strtotime( $this->get_expiration() ) <= time() ) )
			);
	}

	public function is_refunded() {
		return ! $this->is_lifetime() &&
		       $this->get_status() === self::SUBSCRIPTION_STATUS_INACTIVE &&
		       $this->notes === 'Payment refunded to user';
	}
}
