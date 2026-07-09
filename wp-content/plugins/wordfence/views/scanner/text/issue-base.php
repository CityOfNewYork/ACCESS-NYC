<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Shared parent view of all scan issues' text output views.
 * 
 * @var string $internalType The internal issue type used to select the correct template.
 * @var string $displayType A human-readable string for displaying the issue type.
 * @var array $textOutput If provided, used the content of the array to output plain text rather than the HTML template.
 * @var array $textOutputDetailPairs An array of label/value pairs for the issue's detail data if outputting via text. If the entry should only be conditionally shown, the value may be an array of the format array(conditional, displayValue) where conditional is one or more keypaths that must all be truthy. It is preprocessed lightly for output: all values prefixed with $ will be treated as keypaths in the $textOutput array. If that is prefixed with ! for the conditional, its value will be inverted.
 */


echo '[' . $displayType . ($textOutput['status'] == 'ignoreP' || $textOutput['status'] == 'ignoreP' ? ', ' . __('Ignored', 'wordfence') : '') . ']' . "\n";
echo $textOutput['shortMsg'] . "\n";
echo sprintf(/* translators: Localized date. */ __('Issue Found: %s', 'wordfence'), $textOutput['displayTime']) . "\n";
$severity = null;
switch ($textOutput['severity']) {
	case wfIssues::SEVERITY_CRITICAL:
		$severity = __('Critical', 'wordfence');
		break;
	case wfIssues::SEVERITY_HIGH:
		$severity = __('High', 'wordfence');
		break;
	case wfIssues::SEVERITY_MEDIUM:
		$severity = __('Medium', 'wordfence');
		break;
	case wfIssues::SEVERITY_LOW:
		$severity = __('Low', 'wordfence');
		break;
	default:
		$severity = __('None', 'wordfence');
		break;
}
if ($severity) {
	echo sprintf(/* translators: Severity level. */ __('Severity: %s', 'wordfence'), $severity) . "\n";
}

foreach ($textOutputDetailPairs as $label => $value) {
	if ($value === null) {
		echo "\n";
		continue;
	}
	
	unset($conditional);
	if (is_array($value)) {
		$conditional = $value[0];
		if (!is_array($conditional)) {
			$conditional = array($conditional);
		}
		$value = $value[1];
	}
	
	$allow = true;
	if (isset($conditional)) {
		foreach ($conditional as $test) {
			if (!$allow) {
				break;
			}
			
			if (preg_match('/^!?\$(\S+)/', $test, $matches)) {
				$invert = (strpos($test, '!') === 0);
				$components = explode('.', $matches[1]);
				$tier = $textOutput;
				foreach ($components as $index => $c) {
					if (is_array($tier) && !isset($tier[$c])) {
						if (!$invert) {
							$allow = false;
						}
						break;
					}
					
					if ($index == count($components) - 1 && is_array($tier)) {
						if ((!$tier[$c] && !$invert) || ($tier[$c] && $invert)) {
							$allow = false;
						}
						break;
					}
					else if (!is_array($tier)) {
						$allow = false;
						break;
					}
					
					$tier = $tier[$c];
				}
			}
		}
	}
	
	if (!$allow) {
		continue;
	}
	
	if (preg_match_all('/(?<=^|\s)\$(\S+)(?=$|\s)/', $value, $matches, PREG_OFFSET_CAPTURE)) {
		array_shift($matches);
		$matches = $matches[0];
		$matches = array_reverse($matches);
		foreach ($matches as $m) {
			$resolvedKeyPath = '';
			$components = explode('.', $m[0]);
			$tier = $textOutput;
			foreach ($components as $index => $c) {
				if (is_array($tier) && !isset($tier[$c])) {
					$allow = false;
					break 2;
				}
				
				if ($index == count($components) - 1 && is_array($tier)) {
					$resolvedKeyPath = (string) $tier[$c];
					break;
				}
				else if (!is_array($tier)) {
					$allow = false;
					break 2;
				}
				
				$tier = $tier[$c];
			}
			
			$value = substr($value, 0, $m[1] - 1) . strip_tags($resolvedKeyPath) . substr($value, $m[1] + strlen($m[0]));
		}
	}
	
	if (!$allow) {
		continue;
	}
	
	echo $label . ': ' . $value . "\n";
}
