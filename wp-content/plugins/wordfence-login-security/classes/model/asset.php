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
		if (empty($this->source))
			return null;
		$url = $this->source;
		if (is_string($this->version))
			$url = add_query_arg('ver', $this->version, $this->source);
		return $url;
	}

	public abstract function enqueue(); 

	public abstract function isEnqueued();

	public abstract function renderInline();

	public function renderInlineIfNotEnqueued() {
		if (!$this->isEnqueued())
			$this->renderInline();
	}

	public function setRegistered() {
		$this->registered = true;
		return $this;
	}

	public function register() {
		return $this->setRegistered();
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