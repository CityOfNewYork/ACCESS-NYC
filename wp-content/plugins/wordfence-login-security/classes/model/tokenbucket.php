<?php

namespace WordfenceLS;

class Model_TokenBucket {
	/* Constants to map from tokens per unit to tokens per second */
	const MICROSECOND =        0.000001;
	const MILLISECOND =        0.001;
	const SECOND      =        1;
	const MINUTE      =       60;
	const HOUR        =     3600;
	const DAY         =    86400;
	const WEEK        =   604800;
	const MONTH       =  2629743.83;
	const YEAR        = 31556926;
	
	const BACKING_REDIS = 'redis';
	const BACKING_WP_OPTIONS = 'wpoptions';
	
	private $_identifier;
	private $_bucketSize;
	private $_tokensPerSecond;
	
	private $_backing;
	private $_redis;
	
	/**
	 * Model_TokenBucket constructor.
	 *
	 * @param string $identifier The identifier for the bucket record in the database
	 * @param int $bucketSize The maximum capacity of the bucket.
	 * @param double $tokensPerSecond The number of tokens per second added to the bucket.
	 * @param string $backing The backing storage to use.
	 */
	public function __construct($identifier, $bucketSize, $tokensPerSecond, $backing = self::BACKING_WP_OPTIONS) {
		$this->_identifier = $identifier;
		$this->_bucketSize = $bucketSize;
		$this->_tokensPerSecond = $tokensPerSecond;
		$this->_backing = $backing;
		
		if ($backing == self::BACKING_REDIS) {
			$this->_redis = new \Redis();
			$this->_redis->pconnect('127.0.0.1');
		}
	}
	
	/**
	 * Attempts to acquire a lock for the bucket.
	 *
	 * @param int $timeout
	 * @return bool Whether or not the lock was acquired.
	 */
	private function _lock($timeout = 30) {
		if ($this->_backing == self::BACKING_WP_OPTIONS) {
			$start = microtime(true);
			while (!$this->_wp_options_create_lock($this->_identifier)) {
				if (microtime(true) - $start > $timeout) {
					return false;
				}
				usleep(5000); // 5 ms
			}
			return true;
		}
		else if ($this->_backing == self::BACKING_REDIS) {
			if ($this->_redis === false) {
				return false;
			}
			
			$start = microtime(true);
			while (!$this->_redis->setnx('lock:' . $this->_identifier, '1')) {
				if (microtime(true) - $start > $timeout) {
					return false;
				}
				usleep(5000); // 5 ms
			}
			$this->_redis->expire('lock:' . $this->_identifier, 30);
			return true;
		}
		return false;
	}
	
	private function _unlock() {
		if ($this->_backing == self::BACKING_WP_OPTIONS) {
			$this->_wp_options_release_lock($this->_identifier);
		}
		else if ($this->_backing == self::BACKING_REDIS) {
			if ($this->_redis === false) {
				return;
			}
			
			$this->_redis->del('lock:' . $this->_identifier);
		}
	}
	
	private function _wp_options_create_lock($name, $timeout = null) { //Our own version of WP_Upgrader::create_lock
		global $wpdb;
		
		if (!$timeout) {
			$timeout = 3600;
		}
		
		$lock_option = 'wfls_' . $name . '.lock';
		$lock_result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `{$wpdb->options}` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'no')", $lock_option, time()));
		
		if (!$lock_result) {
			$lock_result = get_option($lock_option);
			if (!$lock_result) {
				return false;
			}
			
			if ($lock_result > (time() - $timeout)) {
				return false;
			}
			
			$this->_wp_options_release_lock($name);
			return $this->_wp_options_create_lock($name, $timeout);
		}
		
		return true;
	}
	
	private function _wp_options_release_lock($name) {
		return delete_option('wfls_' . $name . '.lock');
	}
	
	/**
	 * Atomically checks the available token count, creating the initial record if needed, and updates the available token count if the requested number of tokens is available.
	 *
	 * @param int $tokenCount
	 * @return bool Whether or not there were enough tokens to satisfy the request.
	 */
	public function consume($tokenCount = 1) {
		if (!$this->_lock()) { return false; }
		
		if ($this->_backing == self::BACKING_WP_OPTIONS) {
			$record = get_transient('wflsbucket:' . $this->_identifier);
		}
		else if ($this->_backing == self::BACKING_REDIS) {
			$record = $this->_redis->get('bucket:' . $this->_identifier);
		}
		else {
			$this->_unlock();
			return false;
		}
		
		if ($record === false) {
			if ($tokenCount > $this->_bucketSize) {
				$this->_unlock();
				return false;
			}
			
			$this->_bootstrap($this->_bucketSize - $tokenCount);
			$this->_unlock();
			return true;
		}
		
		$tokens = min($this->_secondsToTokens(microtime(true) - (float) $record), $this->_bucketSize);
		if ($tokenCount > $tokens) {
			$this->_unlock();
			return false;
		}
		
		if ($this->_backing == self::BACKING_WP_OPTIONS) {
			set_transient('wflsbucket:' . $this->_identifier, (string) (microtime(true) - $this->_tokensToSeconds($tokens - $tokenCount)), ceil($this->_tokensToSeconds($this->_bucketSize)));
		}
		else if ($this->_backing == self::BACKING_REDIS) {
			$this->_redis->set('bucket:' . $this->_identifier, (string) (microtime(true) - $this->_tokensToSeconds($tokens - $tokenCount)));
		}
		
		$this->_unlock();
		return true;
	}
	
	public function reset() {
		if (!$this->_lock()) { return false; }
		
		if ($this->_backing == self::BACKING_WP_OPTIONS) {
			delete_transient('wflsbucket:' . $this->_identifier);
		}
		else if ($this->_backing == self::BACKING_REDIS) {
			$this->_redis->del('bucket:' . $this->_identifier);
		}
		
		$this->_unlock();
	}
	
	/**
	 * Creates an initial record with the given number of tokens.
	 *
	 * @param int $initialTokens
	 */
	protected function _bootstrap($initialTokens) {
		$microtime = microtime(true) - $this->_tokensToSeconds($initialTokens);
		if ($this->_backing == self::BACKING_WP_OPTIONS) {
			set_transient('wflsbucket:' . $this->_identifier, (string) $microtime, ceil($this->_tokensToSeconds($this->_bucketSize)));
		}
		else if ($this->_backing == self::BACKING_REDIS) {
			$this->_redis->set('bucket:' . $this->_identifier, (string) $microtime);
		}
	}
	
	protected function _tokensToSeconds($tokens) {
		return $tokens / $this->_tokensPerSecond;
	}
	
	protected function _secondsToTokens($seconds) {
		return (int) $seconds * $this->_tokensPerSecond;
	}
}
