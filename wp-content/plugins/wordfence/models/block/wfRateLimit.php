<?php

class wfRateLimit {
	const TYPE_GLOBAL = 'global';
	const TYPE_CRAWLER_VIEWS = 'crawler-views';
	const TYPE_CRAWLER_404S = 'crawler-404s';
	const TYPE_HUMAN_VIEWS = 'human-views';
	const TYPE_HUMAN_404S = 'human-404s';
	
	const HIT_TYPE_404 = '404';
	const HIT_TYPE_NORMAL = 'hit';
	
	const VISITOR_TYPE_HUMAN = 'human';
	const VISITOR_TYPE_CRAWLER = 'crawler';
	
	protected $_type;
	protected static $_hitCount = false;
	
	public static function table() {
		return wfDB::networkTable('wfTrafficRates');
	}
	
	public static function trimData() {
		$wfdb = wfDB::shared();
		$table = self::table();
		$wfdb->queryWrite("DELETE FROM {$table} WHERE eMin < FLOOR((UNIX_TIMESTAMP() - 60) / 60)");
	}
	
	public static function globalRateLimit() {
		static $_cachedGlobal = null;
		if ($_cachedGlobal === null) {
			$_cachedGlobal = new wfRateLimit(self::TYPE_GLOBAL);
		}
		return $_cachedGlobal;
	}
	
	public static function crawlerViewsRateLimit() {
		static $_cachedCrawlerViews = null;
		if ($_cachedCrawlerViews === null) {
			$_cachedCrawlerViews = new wfRateLimit(self::TYPE_CRAWLER_VIEWS);
		}
		return $_cachedCrawlerViews;
	}
	
	public static function crawler404sRateLimit() {
		static $_cachedCrawler404s = null;
		if ($_cachedCrawler404s === null) {
			$_cachedCrawler404s = new wfRateLimit(self::TYPE_CRAWLER_404S);
		}
		return $_cachedCrawler404s;
	}
	
	public static function humanViewsRateLimit() {
		static $_cachedHumanViews = null;
		if ($_cachedHumanViews === null) {
			$_cachedHumanViews = new wfRateLimit(self::TYPE_HUMAN_VIEWS);
		}
		return $_cachedHumanViews;
	}
	
	public static function human404sRateLimit() {
		static $_cachedHuman404s = null;
		if ($_cachedHuman404s === null) {
			$_cachedHuman404s = new wfRateLimit(self::TYPE_HUMAN_404S);
		}
		return $_cachedHuman404s;
	}
	
	/**
	 * Returns whether or not humans and bots have the same rate limits configured.
	 *
	 * @return bool
	 */
	public static function identicalHumanBotRateLimits() {
		$humanViews = self::humanViewsRateLimit();
		$crawlerViews = self::crawlerViewsRateLimit();
		if ($humanViews->isEnabled() != $crawlerViews->isEnabled()) {
			return false;
		}
		if ($humanViews->limit() != $crawlerViews->limit()) {
			return false;
		}
		
		$human404s = self::human404sRateLimit();
		$crawler404s = self::crawler404sRateLimit();
		if ($human404s->isEnabled() != $crawler404s->isEnabled()) {
			return false;
		}
		if ($human404s->limit() != $crawler404s->limit()) {
			return false;
		}
		
		return true;
	}
	
	public static function mightRateLimit($hitType) {
		if (!wfConfig::get('firewallEnabled')) {
			return false;
		}
		
		$IP = wfUtils::getIP();
		if (wfBlock::isWhitelisted($IP)) {
			return false;
		}
		
		if (wfConfig::get('neverBlockBG') == 'neverBlockUA' && wfCrawl::isGoogleCrawler()) {
			return false;
		}
		
		if (wfConfig::get('neverBlockBG') == 'neverBlockVerified' && wfCrawl::isVerifiedGoogleCrawler()) {
			return false;
		}
			
		if ($hitType == '404') {
			$allowed404s = wfConfig::get('allowed404s');
			if (is_string($allowed404s)) {
				$allowed404s = array_filter(preg_split("/[\r\n]+/", $allowed404s));
				$allowed404sPattern = '';
				foreach ($allowed404s as $allowed404) {
					$allowed404sPattern .= preg_replace('/\\\\\*/', '.*?', preg_quote($allowed404, '/')) . '|';
				}
				$uri = $_SERVER['REQUEST_URI'];
				if (($index = strpos($uri, '?')) !== false) {
					$uri = substr($uri, 0, $index);
				}
				if ($allowed404sPattern && preg_match('/^' . substr($allowed404sPattern, 0, -1) . '$/i', $uri)) {
					return false;
				}
			}
		}
		
		if (self::globalRateLimit()->isEnabled()) {
			return true;
		}
		
		$visitorType = self::visitorType();
		
		if ($visitorType == self::VISITOR_TYPE_CRAWLER) {
			if ($hitType == self::HIT_TYPE_NORMAL) {
				if (self::crawlerViewsRateLimit()->isEnabled()) {
					return true;
				}
			}
			else {
				if (self::crawler404sRateLimit()->isEnabled()) {
					return true;
				}
			}
		}
		else {
			if ($hitType == self::HIT_TYPE_NORMAL) {
				if (self::humanViewsRateLimit()->isEnabled()) {
					return true;
				}
			}
			else {
				if (self::human404sRateLimit()->isEnabled()) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	public static function countHit($hitType, $ip) {
		$table  = self::table();
		$ipHex = wfDB::binaryValueToSQLHex(wfUtils::inet_pton($ip));
		wfDB::shared()->queryWrite("INSERT INTO {$table} (eMin, IP, hitType, hits) VALUES (FLOOR(UNIX_TIMESTAMP() / 60), {$ipHex}, %s, @wfcurrenthits := 1) ON DUPLICATE KEY UPDATE hits = IF(@wfcurrenthits := hits + 1, hits + 1, hits + 1)", $hitType);
	}
	
	/**
	 * Returns one of the VISITOR_TYPE_ constants for the purposes of determining which rate limit to apply.
	 * 
	 * @return string
	 */
	public static function visitorType() {
		static $_cachedVisitorType = null;
		if ($_cachedVisitorType === null) {
			$_cachedVisitorType = ((isset($_SERVER['HTTP_USER_AGENT']) && wfCrawl::isCrawler($_SERVER['HTTP_USER_AGENT'])) || empty($_SERVER['HTTP_USER_AGENT']) ? wfRateLimit::VISITOR_TYPE_CRAWLER : wfRateLimit::VISITOR_TYPE_HUMAN);	
		}
		return $_cachedVisitorType;
	}
	
	protected function __construct($type) {
		$this->_type = $type;
	}
	
	/**
	 * Returns whether or not this rate limit is configured in a way where it would run.
	 * 
	 * @return bool
	 */
	public function isEnabled() {
		switch ($this->_type) {
			case self::TYPE_GLOBAL:
				return wfConfig::get('maxGlobalRequests') != 'DISABLED' && wfConfig::getInt('maxGlobalRequests') > 0;
			case self::TYPE_CRAWLER_VIEWS:
				return wfConfig::get('maxRequestsCrawlers') != 'DISABLED' && wfConfig::getInt('maxRequestsCrawlers') > 0;
			case self::TYPE_CRAWLER_404S:
				return wfConfig::get('max404Crawlers') != 'DISABLED' && wfConfig::getInt('max404Crawlers') > 0;
			case self::TYPE_HUMAN_VIEWS:
				return wfConfig::get('maxRequestsHumans') != 'DISABLED' && wfConfig::getInt('maxRequestsHumans') > 0;
			case self::TYPE_HUMAN_404S:
				return wfConfig::get('max404Humans') != 'DISABLED' && wfConfig::getInt('max404Humans') > 0;
		}
		return true;
	}
	
	public function limit() {
		switch ($this->_type) {
			case self::TYPE_GLOBAL:
				return wfConfig::getInt('maxGlobalRequests');
			case self::TYPE_CRAWLER_VIEWS:
				return wfConfig::getInt('maxRequestsCrawlers');
			case self::TYPE_CRAWLER_404S:
				return wfConfig::getInt('max404Crawlers');
			case self::TYPE_HUMAN_VIEWS:
				return wfConfig::getInt('maxRequestsHumans');
			case self::TYPE_HUMAN_404S:
				return wfConfig::getInt('max404Humans');
		}
		return -1;
	}
	
	public function shouldEnforce($hitType) {
		switch ($this->_type) {
			case self::TYPE_GLOBAL:
				return $this->isEnabled() && $this->_hitCount() > max(wfConfig::getInt('maxGlobalRequests'), 1);
			case self::TYPE_CRAWLER_VIEWS:
				return self::visitorType() == self::VISITOR_TYPE_CRAWLER && $hitType == self::HIT_TYPE_NORMAL && $this->isEnabled() && $this->_hitCount() > wfConfig::getInt('maxRequestsCrawlers');
			case self::TYPE_CRAWLER_404S:
				return self::visitorType() == self::VISITOR_TYPE_CRAWLER && $hitType == self::HIT_TYPE_404 && $this->isEnabled() && $this->_hitCount() > wfConfig::getInt('max404Crawlers');
			case self::TYPE_HUMAN_VIEWS:
				return self::visitorType() == self::VISITOR_TYPE_HUMAN && $hitType == self::HIT_TYPE_NORMAL && $this->isEnabled() && $this->_hitCount() > wfConfig::getInt('maxRequestsHumans');
			case self::TYPE_HUMAN_404S:
				return self::visitorType() == self::VISITOR_TYPE_HUMAN && $hitType == self::HIT_TYPE_404 && $this->isEnabled() && $this->_hitCount() > wfConfig::getInt('max404Humans');
		}
		return false;
	}
	
	/**
	 * Returns the hit count corresponding to the current request type.
	 * 
	 * @return int
	 */
	protected function _hitCount() {
		if (self::$_hitCount === false) {
			self::$_hitCount = (int) wfDB::shared()->querySingle("SELECT @wfcurrenthits");
		}
		return self::$_hitCount;
	}
}