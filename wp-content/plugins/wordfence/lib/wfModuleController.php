<?php

class wfModuleController {
	private $_optionIndexes;
	private $_optionBlocks;
	
	public static function shared() {
		static $_shared = false;
		if ($_shared === false) {
			$_shared = new wfModuleController();
		}
		return $_shared;
	}
	
	public function __construct() {
		$this->_optionIndexes = array();
		$this->_optionBlocks = array();
	}
	
	public function __get($key) {
		switch ($key) {
			case 'optionIndexes':
				return $this->_optionIndexes;
			case 'optionBlocks':
				return $this->_optionBlocks;
		}
		
		throw new OutOfBoundsException('Invalid key');
	}
	
	public function addOptionIndex($target, $text) {
		$this->_optionIndexes[$target] = $text;
	}
	
	public function addOptionBlock($html) {
		$this->_optionBlocks[] = $html;
	}
}