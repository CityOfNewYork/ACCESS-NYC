<?php

/**
 * Class wfBinaryList implements an interface to interact with binary lists. These are internally a sorted list of 
 * values of a specific size. The sorted aspect allows for very quick searching.
 */
class wfBinaryList {
	private $size = 0;
	private $list = '';
	
	public function __construct($binary) {
		$this->size = ord(wfWAFUtils::substr($binary, 0, 1));
		$this->list = wfWAFUtils::substr($binary, 1);
	}
	
	public function contains($value) {
		if ($this->size == 0) { return false; }
		$length = wfWAFUtils::strlen($this->list);
		if ($length == 0) { return false; }
		
		$p = wfWAFUtils::substr($value, 0, $this->size);
		
		$count = ceil($length / $this->size);
		$low = 0;
		$high = $count - 1;
		
		while ($low <= $high) {
			$mid = (int) (($high + $low) / 2);
			$val = wfWAFUtils::substr($this->list, $mid * $this->size, $this->size);
			$cmp = strcmp($val, $p);
			if ($cmp < 0) {
				$low = $mid + 1;
			}
			else if ($cmp > 0) {
				$high = $mid - 1;
			}
			else {
				return $mid;
			}
		}
		
		return false;
	}
}