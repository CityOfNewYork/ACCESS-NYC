<?php


class wfHelperString {

	/**
	 * cycle through arguments
	 *
	 * @return mixed
	 */
	public static function cycle() {
		static $counter = 0;
		$args = func_get_args();
		if (empty($args)) {
			$counter = 0;
			return null;
		}
		$return_val = $args[$counter % count($args)];
		$counter++;
		return $return_val;
	}

	public static function plainTextTable($table, $maxColumnWidth = 60) {
		if (count($table) === 0) {
			return '';
		}
		
		$colLengths = array();
		for ($row = 0; $row < count($table); $row++) {
			if (is_string($table[$row])) { //Special handling to show a sub-header/divider
				continue;
			}
			
			for ($col = 0; $col < count($table[$row]); $col++) {
				$table[$row][$col] = wordwrap(str_replace("\t", "    ", $table[$row][$col]), $maxColumnWidth, "\n", true);
				
				foreach (explode("\n", $table[$row][$col]) as $colText) {
					if (!isset($colLengths[$col])) {
						$colLengths[$col] = strlen($colText);
						continue;
					}
					$len = strlen($colText);
					if ($len > $colLengths[$col]) {
						$colLengths[$col] = $len;
					}
				}
			}
		}
		$totalWidth = array_sum($colLengths) + (count($colLengths) * 3) + 1;
		$hr = str_repeat('-', $totalWidth);
		$output = $hr . "\n";
		for ($row = 0; $row < count($table); $row++) {
			if (is_string($table[$row])) { //Special handling to show a sub-header/divider
				if ($row > 1) { $output .= $hr . "\n"; }
				$output .= '| ' . str_pad($table[$row], $totalWidth - 4, ' ', STR_PAD_BOTH) . ' ' . "|\n";
				$output .= $hr . "\n";
				continue;
			}
			
			$colHeight = 0;
			for ($col = 0; $col < count($table[$row]); $col++) {
				$height = substr_count($table[$row][$col], "\n");
				if ($height > $colHeight) {
					$colHeight = $height;
				}
			}
			for ($colRow = 0; $colRow <= $colHeight; $colRow++) {
				for ($col = 0; $col < count($table[$row]); $col++) {
					$colRows = explode("\n", $table[$row][$col]);
					$output .= '| ' . str_pad(isset($colRows[$colRow]) ? $colRows[$colRow] : '', $colLengths[$col], ' ', STR_PAD_RIGHT) . ' ';
				}
				$output .= "|\n";
			}
			if ($row === 0) {
				$output .= $hr . "\n";
			}
		}
		return trim($output . (count($table) > 1 ? $hr : ''));
	}
}