<?php

require_once __DIR__ . '/wfWebsite.php';

class wfLicense {

	const TYPE_FREE = 'free';
	const TYPE_PREMIUM = 'premium';
	const TYPE_CARE = 'care';
	const TYPE_RESPONSE = 'response';

	const KEY_TYPE_FREE = 'free';
	const KEY_TYPE_PAID_CURRENT = 'paid-current';
	const KEY_TYPE_PAID_EXPIRED = 'paid-expired';
	const KEY_TYPE_PAID_DELETED = 'paid-deleted';

	const CONFIG_API_KEY = 'apiKey';
	const CONFIG_REMAINING_DAYS = 'keyExpDays';
	const CONFIG_PAID = 'isPaid';
	const CONFIG_KEY_TYPE = 'keyType';
	const CONFIG_HAS_KEY_CONFLICT = 'hasKeyConflict';
	const CONFIG_TYPE = 'licenseType';

	const REGISTRATION_PAYLOAD_VERSION = 1;

	private static $TYPES = array(
		self::TYPE_FREE,
		self::TYPE_PREMIUM,
		self::TYPE_CARE,
		self::TYPE_RESPONSE
	);

	private static $reflectionClass = null;
	private static $current = null;

	private $apiKey;
	private $paid;
	private $type;
	private $remainingDays;
	private $conflicting;
	private $deleted;
	private $keyType;

	/**
	 * @param string $apiKey
	 * @param bool $paid whether or not this is a paid license
	 * @param ?string $type the license type (@see self::$TYPES)
	 * @param int $remainingDays the number of days remaining before the license expires
	 *	(may be negative if already expired)
	 * @param bool $conflicting whether or not there is a conflict with this license
	 * @param bool $deleted whether or not the key was deleted
	 */
	private function __construct($apiKey = null, $paid = null, $type = null, $remainingDays = null, $conflicting = false, $deleted = false, $keyType = null) {
		$this->apiKey = $apiKey;
		$this->paid = $paid;
		$this->setType($type);
		$this->remainingDays = $remainingDays;
		$this->conflicting = $conflicting;
		$this->deleted = $deleted;
		$this->keyType = $keyType;
	}

	public function setApiKey($apiKey) {
		$this->apiKey = $apiKey;
		return $this;
	}

	public function getApiKey() {
		return $this->apiKey;
	}

	public function setPaid($paid) {
		$this->paid = $paid;
		return $this;
	}

	public function isPaid() {
		return $this->paid;
	}

	public function setType($type) {
		$this->type = $type !== null && self::isValidType($type) ? (string) $type : ($this->isPaid() ? self::TYPE_PREMIUM : self::TYPE_FREE);
		return $this;
	}

	public function getType() {
		return $this->type === null ? self::TYPE_FREE : $this->type;
	}

	public function is($type, $orGreater = false) {
		return $this->type === $type || ($orGreater && $this->isAtLeast($type));
	}

	public function setRemainingDays($days) {
		$this->remainingDays = (int) $days;
		return $this;
	}

	public function getRemainingDays() {
		return $this->remainingDays;
	}

	public function setConflicting($conflicting = true) {
		$this->conflicting = $conflicting;
		return $this;
	}

	public function hasConflict() {
		return $this->conflicting;
	}

	public function setDeleted($deleted = true) {
		$this->deleted = $deleted;
		return $this;
	}

	public function isExpired() {
		return $this->getKeyType() === self::KEY_TYPE_PAID_EXPIRED;
	}

	public function isValid() {
		return !$this->isExpired();
	}

	public function isPaidAndCurrent() {
		return $this->getKeyType() === self::KEY_TYPE_PAID_CURRENT;
	}

	private function resolveKeyType() {
		if ($this->deleted)
			return self::KEY_TYPE_PAID_DELETED;
		if ($this->paid) {
			if ($this->remainingDays >= 0)
				return self::KEY_TYPE_PAID_CURRENT;
			else 
				return self::KEY_TYPE_PAID_EXPIRED;
		}
		return self::KEY_TYPE_FREE;
	}

	public function getKeyType() {
		if (!$this->keyType)
			$this->keyType = $this->resolveKeyType();
		return $this->keyType;
	}

	private function clearCache() {
		$this->keyType = null;
	}

	private function compareTiers($a, $b, $inclusive = true) {
		if ($a === $b)
			return $inclusive;
		foreach (self::$TYPES as $tier) {
			if ($tier === $a)
				return true;
			if ($tier === $b)
				return false;
		}
		return false;
	}

	/**
	 * Check if the license type is at or above the given tier
	 */
	public function isAtLeast($type) {
		if ($type !== self::TYPE_FREE && !$this->isValid())
			return false;
		return $this->compareTiers($type, $this->getType());
	}

	public function isBelow($type) {
		if ($type !== self::TYPE_FREE && !$this->isValid())
			return true;
		return $this->compareTiers($this->getType(), $type, false);
	}

	public function isPremium($orGreater = false) {
		return $this->is(self::TYPE_PREMIUM, $orGreater);
	}

	public function isAtLeastPremium() {
		return $this->isPremium(true);
	}

	public function isBelowPremium() {
		return $this->isBelow(self::TYPE_PREMIUM);
	}

	public function isCare($orGreater = false) {
		return $this->is(self::TYPE_CARE, $orGreater);
	}

	public function isAtLeastCare() {
		return $this->isCare(true);
	}

	public function isBelowCare() {
		return $this->isBelow(self::TYPE_CARE);
	}

	public function isResponse($orGreater = false) {
		return $this->is(self::TYPE_RESPONSE, $orGreater);
	}

	public function isAtLeastResponse() {
		return $this->isResponse(true);
	}

	public function isBelowResponse() {
		return $this->isBelow(self::TYPE_RESPONSE);
	}

	public function getShieldLogo() {
		$type = $this->getType();
		return wfUtils::getBaseURL() . "images/logos/shield-{$type}.svg";
	}

	public function getStylesheet($global = false) {
		$type = $this->getType();
		$suffix = $global ? '-global' : '';
		return wfUtils::getBaseURL() . wfUtils::versionedAsset("css/license/{$type}{$suffix}.css", '', WORDFENCE_VERSION);
	}

	public function getGlobalStylesheet() {
		return $this->getStylesheet(true);
	}

	public function getTypeLabel($requireCurrent = true, $includePrefix = null) {
		$paidKeyTypes = array(self::KEY_TYPE_PAID_CURRENT);
		if (!$requireCurrent) {
			$paidKeyTypes[] = self::KEY_TYPE_PAID_EXPIRED;
			$paidKeyTypes[] = self::KEY_TYPE_PAID_DELETED;
		}
		if (in_array($this->getKeyType(), $paidKeyTypes)) {
			switch ($this->type) {
			case self::TYPE_CARE:
				return $includePrefix || $includePrefix === null ? __('Wordfence Care', 'wordfence') : __('Care', 'wordfence');
			case self::TYPE_RESPONSE:
				return $includePrefix || $includePrefix === null ? __('Wordfence Response', 'wordfence') : __('Response', 'wordfence');
			case self::TYPE_PREMIUM:
			default:
				return $includePrefix ? __('Wordfence Premium', 'wordfence') : __('Premium', 'wordfence');
			}
		}
		return $includePrefix ? __('Wordfence Free', 'wordfence') : __('Free', 'wordfence');
	}

	public function getBaseTypeLabel($requireCurrent = true) {
		return $this->getTypeLabel($requireCurrent, false);
	}

	public function getPrefixedTypeLabel($requireCurrent = true) {
		return $this->getTypeLabel($requireCurrent, true);
	}

	private function generateLicenseUrl($path, $query = array(), $campaign = null) {
		if ($campaign !== null)
			$campaign = "gnl1{$campaign}";
		$url = implode(
			'/',
			array_filter(array(
				'https://www.wordfence.com',
				$campaign,
				$path
			))
		);
		return $url . (empty($query) ? '' : ('?' . http_build_query($query)));
	}

	public function getSupportUrl($campaign = null) {
		return $this->generateLicenseUrl(
			'get-help',
			array(
				'license' => $this->apiKey
			),
			$campaign
		);
	}

	public function getUpgradeUrl($campaign = null) {
		if ($this->isAtLeastPremium()) {
			return $this->generateLicenseUrl(
				'licenses',
				array(
					'upgrade' => $this->apiKey
				),
				$campaign
			);
		}
		else {
			return $this->generateLicenseUrl(
				'products/pricing/',
				array(),
				$campaign
			);
		}
	}

	private function writeConfig($hasError = false) {
		$this->clearCache();
		$keyType = $this->getKeyType();
		wfConfig::set(self::CONFIG_API_KEY, $this->apiKey);
		wfConfig::set(self::CONFIG_TYPE, $this->type);
		wfConfig::set(self::CONFIG_REMAINING_DAYS, $this->remainingDays);
		wfConfig::set(self::CONFIG_PAID, $keyType === self::KEY_TYPE_PAID_CURRENT);
		wfConfig::setOrRemove(self::CONFIG_HAS_KEY_CONFLICT, $this->conflicting ? 1 : null);
		if (!$hasError) { //Only save a limited subset of the config if an API error occurred
			wfConfig::set(self::CONFIG_KEY_TYPE, $keyType);
		}
	}

	/**
	 * @param bool $hasError whether or not an error occurred while retrieving the current license data
	 */
	public function save($hasError = false) {
		$this->writeConfig($hasError);
	}

	public function downgradeToFree($apiKey) {
		$this->apiKey = $apiKey;
		$this->type = self::TYPE_FREE;
		$this->paid = false;
		$this->keyType = self::KEY_TYPE_FREE;
		$this->conflicting = false;
		$this->deleted = false;
		$this->remainingDays = -1;
		return $this;
	}

	public static function isValidType($type) {
		return in_array($type, self::$TYPES);
	}

	private static function fromConfig() {
		$values = wfConfig::getMultiple(array(
			self::CONFIG_API_KEY => null,
			self::CONFIG_PAID => false,
			self::CONFIG_KEY_TYPE => null,
			self::CONFIG_TYPE => self::TYPE_FREE,
		));
		
		$keyType = $values[self::CONFIG_KEY_TYPE];
		$remainingDays = null;
		$conflicting = false;
		if ($keyType != self::KEY_TYPE_FREE) {
			$premiumValues = wfConfig::getMultiple(array(
				self::CONFIG_REMAINING_DAYS => null,
				self::CONFIG_HAS_KEY_CONFLICT => false,
			));
			
			if ($premiumValues[self::CONFIG_REMAINING_DAYS] !== null) {
				$remainingDays = (int) $premiumValues[self::CONFIG_REMAINING_DAYS];
			}
			
			$conflicting = (bool) $premiumValues[self::CONFIG_HAS_KEY_CONFLICT];
		}
		
		return new self(
			(string) $values[self::CONFIG_API_KEY],
			(bool) $values[self::CONFIG_PAID],
			(string) $values[self::CONFIG_TYPE],
			$remainingDays,
			$conflicting,
			$keyType === self::KEY_TYPE_PAID_DELETED,
			$keyType
		);
	}

	public static function current() {
		if (self::$current === null) {
			self::$current = self::fromConfig();
		}
		return self::$current;
	}

	const REGISTRATION_TOKEN_TTL = 86400; //24 hours
	const REGISTRATION_TOKEN_KEY = 'wfRegistrationToken';
	const REGISTRATION_TOKEN_LENGTH = 32;

	public static function getRegistrationToken($refreshTtl = false) {
		$token = get_transient(self::REGISTRATION_TOKEN_KEY);
		if ($token === false) {
			$token = openssl_random_pseudo_bytes(self::REGISTRATION_TOKEN_LENGTH);
			if ($token === false)
				throw new Exception('Unable to generate registration token');
			$token = wfUtils::base64url_encode($token);
			$refreshTtl = true;
		}
		if ($refreshTtl)
			set_transient(self::REGISTRATION_TOKEN_KEY, $token, self::REGISTRATION_TOKEN_TTL);
		return $token;
	}

	public static function validateRegistrationToken($token) {
		$expected = self::getRegistrationToken();
		//Note that the length of $expected is publicly known since it's in the plugin source, so differening lengths immediately triggering a false return is not a cause for concern
		return hash_equals($expected, $token);
	}

	public static function generateRegistrationLink() {
		$wfWebsite = wfWebsite::getInstance();
		$stats = wfAPI::generateSiteStats();
		$token = self::getRegistrationToken(true);
		$returnUrl = network_admin_url('admin.php?page=WordfenceInstall');
		$payload = array(
			self::REGISTRATION_PAYLOAD_VERSION,
			$stats,
			$token,
			$returnUrl,
		);
		$payload = implode(';', $payload);
		$payload = wfUtils::base64url_encode($payload);
		return $wfWebsite->getUrl("plugin/registration/{$payload}");
	}

}