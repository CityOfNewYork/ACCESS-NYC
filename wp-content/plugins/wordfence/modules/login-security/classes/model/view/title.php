<?php

namespace WordfenceLS\View;

/**
 * Class Model_Title
 * @package Wordfence2FA\Page
 * @var string $id A valid DOM ID for the title.
 * @var string|Model_HTML $title The title text or HTML.
 * @var string $helpURL The help URL.
 * @var string|Model_HTML $helpLink The text/HTML of the help link.
 */
class Model_Title {
	private $_id;
	private $_title;
	private $_helpURL;
	private $_helpLink;
	
	public function __construct($id, $title, $helpURL = null, $helpLink = null) {
		$this->_id = $id;
		$this->_title = $title;
		$this->_helpURL = $helpURL;
		$this->_helpLink = $helpLink;
	}
	
	public function __get($name) {
		switch ($name) {
			case 'id':
				return $this->_id;
			case 'title':
				return $this->_title;
			case 'helpURL':
				return $this->_helpURL;
			case 'helpLink':
				return $this->_helpLink;
		}
		
		throw new \OutOfBoundsException('Invalid key: ' . $name);
	}
}