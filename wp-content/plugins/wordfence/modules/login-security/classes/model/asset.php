<?php

namespace WordfenceLS;

abstract class Model_Asset {

	protected $handle;
	protected $source;
	protected $dependencies;
	protected $version;
	protected $registered = false;

	public function __construct($handle, $source = '', $dependencies = array(), $version = false) {
		$this->handle = $handle;
		$this->source = $source;
		$this->dependencies = $dependencies;
		$this->version = $version;
	}

	public function getSourceUrl() {
		return $this->buildSourceUrl();
	}

	public abstract function enqueue(); 

	public abstract function isEnqueued();

	public abstract function isDone();

	public abstract function renderInline();

	public function renderInlineIfNotEnqueued() {
		if (!$this->isEnqueued() && !$this->isDone())
			$this->renderInline();
	}

	public function setRegistered() {
		$this->registered = true;
		return $this;
	}

	public function register() {
		return $this->setRegistered();
	}

	protected function buildSourceUrl($source = null, $version = null, $baseURL = null, $contentURL = null, $defaultVersion = null) {
		if ($source === null) {
			$source = $this->source;
		}
		if ($version === null) {
			$version = $this->version;
		}
		if (empty($source)) {
			return null;
		}

		$url = $source;
		if (!empty($baseURL) && !preg_match('|^(https?:)?//|', $url) && !( !empty($contentURL) && strpos($url, $contentURL) === 0)) {
			$url = rtrim($baseURL, '/') . '/' . ltrim($url, '/');
		}

		if ($version === false) {
			$version = $defaultVersion;
		}
		if (is_scalar($version) && strlen((string) $version)) {
			$url = add_query_arg('ver', $version, $url);
		}

		return $url;
	}

	public static function js($file) {
		return self::_pluginBaseURL() . 'js/' . self::_versionedFileName($file);
	}
	
	public static function css($file) {
		return self::_pluginBaseURL() . 'css/' . self::_versionedFileName($file);
	}
	
	public static function img($file) {
		return self::_pluginBaseURL() . 'img/' . $file;
	}
	
	protected static function _pluginBaseURL() {
		return plugins_url('', WORDFENCE_LS_FCPATH) . '/';
	}
	
	protected static function _versionedFileName($subpath) {
		$version = WORDFENCE_LS_BUILD_NUMBER;
		if ($version != 'WORDFENCE_LS_BUILD_NUMBER' && preg_match('/^(.+?)(\.[^\.]+)$/', $subpath, $matches)) {
			$prefix = $matches[1];
			$suffix = $matches[2];
			return $prefix . '.' . $version . $suffix;
		}
		
		return $subpath;
	}

	public static function create($handle, $source = '', $dependencies = array(), $version = false) {
		return new static($handle, $source, $dependencies, $version);
	}

}
