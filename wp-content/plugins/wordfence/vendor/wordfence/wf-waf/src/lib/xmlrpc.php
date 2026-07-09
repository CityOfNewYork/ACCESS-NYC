<?php
if (defined('WFWAF_VERSION') && !defined('WFWAF_RUN_COMPLETE')) {

/**
 * Adaptation of WordPress's XML-RPC message parser so we can use it without loading the full environment
 *
 */
class wfXMLRPCBody
{
	var $header;
	var $doctype;
	var $message;
	var $messageType;  // methodCall / methodResponse / fault
	var $faultCode;
	var $faultString;
	var $methodName;
	var $params;
	
	// Current variable stacks
	var $_arraystructs = array();   // The stack used to keep track of the current array/struct
	var $_arraystructstypes = array(); // Stack keeping track of if things are structs or array
	var $_currentStructName = array();  // A stack as well
	var $_param;
	var $_value;
	var $_currentTag;
	var $_currentTagContents;
	// The XML parser
	var $_parser;
	
	static function canParse() {
		return function_exists('xml_parser_create');
	}
	
	/**
	 * PHP5 constructor.
	 */
	function __construct( $message )
	{
		$this->message =& $message;
	}
	
	function __toString() {
		$output = '';
		if (isset($this->header)) {
			$output .= $this->header . "\n";
		}
		
		if (isset($this->doctype)) {
			$output .= $this->doctype . "\n";
		}
		
		$output .= '<methodCall><methodName>' . htmlentities($this->methodName, ENT_XML1) . '</methodName><params>' . $this->_paramsToString($this->params) . '</params></methodCall>';
		return $output;
	}
	
	function _paramsToString($params, $parentType = false) {
		$output = '';
		if (is_array($params)) {
			foreach ($params as $key => $p) {
				if (!$parentType) { //Top level
					$output .= '<param><value>';
				}
				else if ($parentType == 'array') {
					$output .= '<value>';
				}
				else if ($parentType == 'struct') {
					$output .= '<member><name>' . htmlentities($key, ENT_XML1) . '</name><value>';
				}
				
				if ($p['tag'] == 'data') {
					$output .= '<array><data>' . $this->_paramsToString($p['value'], 'array') . '</data></array>';
				}
				else if ($p['tag'] == 'struct') {
					$output .= '<struct>' . $this->_paramsToString($p['value'], 'struct') . '</struct>';
				}
				else if ($p['tag'] == 'base64') {
					$output .= '<base64>' . base64_encode($p['value']) . '</base64>';
				}
				else if ($p['tag'] == 'value') {
					$output .= htmlentities($p['value'], ENT_XML1);
				}
				else if ($p['tag'] == 'dateTime.iso8601') {
					$output .= $p['value']->getXml();
				}
				else {
					$output .= '<' . $p['tag'] . '>' . htmlentities($p['value'], ENT_XML1) . '</' . $p['tag'] . '>';
				}
				
				if (!$parentType) { //Top level
					$output .= '</value></param>';
				}
				else if ($parentType == 'array') {
					$output .= '</value>';
				}
				else if ($parentType == 'struct') {
					$output .= '</value></member>';
				}
			}
		}
		return $output;
	}
	
	function parse()
	{
		if (!function_exists( 'xml_parser_create')) {
			return false;
		}
		
		// first remove the XML declaration
		if (preg_match('/<\?xml.*?\?'.'>/s', substr( $this->message, 0, 100 ), $matches)) {
			$this->header = $matches[0];
		}
		$replacement = preg_replace( '/<\?xml.*?\?'.'>/s', '', substr( $this->message, 0, 100 ), 1 );
		$this->message = trim( substr_replace( $this->message, $replacement, 0, 100 ) );
		if ( '' == $this->message ) {
			return false;
		}
		
		// Then remove the DOCTYPE
		if (preg_match('/^<!DOCTYPE[^>]*+>/i', substr( $this->message, 0, 100 ), $matches)) {
			$this->doctype = $matches[0];
		}
		$replacement = preg_replace( '/^<!DOCTYPE[^>]*+>/i', '', substr( $this->message, 0, 200 ), 1 );
		$this->message = trim( substr_replace( $this->message, $replacement, 0, 200 ) );
		if ( '' == $this->message ) {
			return false;
		}
		
		// Check that the root tag is valid
		$root_tag = substr( $this->message, 0, strcspn( substr( $this->message, 0, 20 ), "> \t\r\n" ) );
		if ( '<!DOCTYPE' === strtoupper( $root_tag ) ) {
			return false;
		}
		if ( ! in_array( $root_tag, array( '<methodCall', '<methodResponse', '<fault' ) ) ) {
			return false;
		}
		
		// Bail if there are too many elements to parse
		$element_limit = 30000;
		if ( $element_limit && 2 * $element_limit < substr_count( $this->message, '<' ) ) {
			return false;
		}
		
		$this->_parser = xml_parser_create();
		// Set XML parser to take the case of tags in to account
		xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		// Set XML parser callback functions
		xml_set_object($this->_parser, $this);
		xml_set_element_handler($this->_parser, 'tag_open', 'tag_close');
		xml_set_character_data_handler($this->_parser, 'cdata');
		
		// 256Kb, parse in chunks to avoid the RAM usage on very large messages
		$chunk_size = 262144;
		
		$final = false;
		do {
			if (strlen($this->message) <= $chunk_size) {
				$final = true;
			}
			$part = substr($this->message, 0, $chunk_size);
			$this->message = substr($this->message, $chunk_size);
			if (!xml_parse($this->_parser, $part, $final)) {
				return false;
			}
			if ($final) {
				break;
			}
		} while (true);
		xml_parser_free($this->_parser);
		
		// Grab the error messages, if any
		if ($this->messageType == 'fault') {
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}
	
	function tag_open($parser, $tag, $attr)
	{
		$this->_currentTagContents = '';
		$this->_currentTag = $tag;
		switch($tag) {
			case 'methodCall':
			case 'methodResponse':
			case 'fault':
				$this->messageType = $tag;
				break;
			/* Deal with stacks of arrays and structs */
			case 'data':    // data is to all intents and puposes more interesting than array
				$this->_arraystructstypes[] = 'array';
				$this->_arraystructs[] = array();
				break;
			case 'struct':
				$this->_arraystructstypes[] = 'struct';
				$this->_arraystructs[] = array();
				break;
		}
	}
	
	function cdata($parser, $cdata)
	{
		$this->_currentTagContents .= $cdata;
	}
	
	function tag_close($parser, $tag)
	{
		$valueFlag = false;
		switch($tag) {
			case 'int':
			case 'i4':
				$value = (int)trim($this->_currentTagContents);
				$valueFlag = true;
				break;
			case 'double':
				$value = (double)trim($this->_currentTagContents);
				$valueFlag = true;
				break;
			case 'string':
				$value = (string)trim($this->_currentTagContents);
				$valueFlag = true;
				break;
			case 'dateTime.iso8601':
				$value = new wfXMLRPCDate(trim($this->_currentTagContents));
				$valueFlag = true;
				break;
			case 'value':
				// "If no type is indicated, the type is string."
				if (trim($this->_currentTagContents) != '') {
					$value = (string)$this->_currentTagContents;
					$valueFlag = true;
				}
				break;
			case 'boolean':
				$value = (boolean)trim($this->_currentTagContents);
				$valueFlag = true;
				break;
			case 'base64':
				$value = base64_decode($this->_currentTagContents);
				$valueFlag = true;
				break;
			/* Deal with stacks of arrays and structs */
			case 'data':
			case 'struct':
				$value = array_pop($this->_arraystructs);
				array_pop($this->_arraystructstypes);
				$valueFlag = true;
				break;
			case 'member':
				array_pop($this->_currentStructName);
				break;
			case 'name':
				$this->_currentStructName[] = trim($this->_currentTagContents);
				break;
			case 'methodName':
				$this->methodName = trim($this->_currentTagContents);
				break;
		}
		
		if ($valueFlag) {
			if (count($this->_arraystructs) > 0) {
				// Add value to struct or array
				if ($this->_arraystructstypes[count($this->_arraystructstypes)-1] == 'struct') {
					// Add to struct
					$this->_arraystructs[count($this->_arraystructs)-1][$this->_currentStructName[count($this->_currentStructName)-1]] = array('tag' => $tag, 'value' => $value);
				} else {
					// Add to array
					$this->_arraystructs[count($this->_arraystructs)-1][] = array('tag' => $tag, 'value' => $value);
				}
			} else {
				// Just add as a parameter
				$this->params[] = array('tag' => $tag, 'value' => $value);
			}
		}
		$this->_currentTagContents = '';
	}
}

class wfXMLRPCDate {
	var $year;
	var $month;
	var $day;
	var $hour;
	var $minute;
	var $second;
	var $timezone;
	
	function __construct( $time )
	{
		// $time can be a PHP timestamp or an ISO one
		if (is_numeric($time)) {
			$this->parseTimestamp($time);
		} else {
			$this->parseIso($time);
		}
	}
	
	function parseTimestamp($timestamp)
	{
		$this->year = date('Y', $timestamp);
		$this->month = date('m', $timestamp);
		$this->day = date('d', $timestamp);
		$this->hour = date('H', $timestamp);
		$this->minute = date('i', $timestamp);
		$this->second = date('s', $timestamp);
		$this->timezone = '';
	}
	
	function parseIso($iso)
	{
		$this->year = substr($iso, 0, 4);
		$this->month = substr($iso, 4, 2);
		$this->day = substr($iso, 6, 2);
		$this->hour = substr($iso, 9, 2);
		$this->minute = substr($iso, 12, 2);
		$this->second = substr($iso, 15, 2);
		$this->timezone = substr($iso, 17);
	}
	
	function getIso()
	{
		return $this->year.$this->month.$this->day.'T'.$this->hour.':'.$this->minute.':'.$this->second.$this->timezone;
	}
	
	function getXml()
	{
		return '<dateTime.iso8601>'.$this->getIso().'</dateTime.iso8601>';
	}
	
	function getTimestamp()
	{
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
	}
}
}
