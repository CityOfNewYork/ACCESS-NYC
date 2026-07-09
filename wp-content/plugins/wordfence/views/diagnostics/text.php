<?php
if (!defined('WORDFENCE_VERSION')) {
	exit;
}

if (!isset($diagnostic)) {
	$diagnostic = new wfDiagnostic;
}
if (!isset($plugins)) {
	$plugins = get_plugins();
}
if (!isset($activePlugins)) {
	$activePlugins = array_flip(get_option('active_plugins'));
}
if (!isset($activeNetworkPlugins)) {
	$activeNetworkPlugins = is_multisite() ? array_flip(wp_get_active_network_plugins()) : array();
}
if (!isset($muPlugins)) {
	$muPlugins = get_mu_plugins();
}
if (!isset($themes)) {
	$themes = wp_get_themes();
}
if (!isset($currentTheme)) {
	$currentTheme = wp_get_theme();
}

$w = new wfConfig();

?>

<?php
foreach ($diagnostic->getResults() as $title => $tests):
	$table = array(
		array($title, ''),
	);
	foreach ($tests['results'] as $result) {

		$infoOnly = isset($result['infoOnly']) && $result['infoOnly'];
		$message = '';
		if ($infoOnly) {
			$message = '';
		} else if ($result['test'] && $result['message'] !== 'OK') {
			$message = '[OK] ';
		} else if (!$result['test'] && $result['message'] !== 'FAIL') {
			$message = '[FAIL] ';
		}

		if (is_array($result['message'])) {
			$message .= $result['message']['textonly'];
		}
		else {
			$message .= strip_tags($result['message']);
		}
		
		if (isset($result['detail']) && !empty($result['detail'])) {
			$message .= "\nAdditional Detail:\n";
			if (is_array($result['detail'])) {
				$message .= $result['detail']['textonly'];
			}
			else {
				$message .= strip_tags($result['detail']);
			}
		}

		$table[] = array(
			strip_tags((is_array($result['label']) && isset($result['label']['raw']) && $result['label']['raw']) ? $result['label']['value'] : $result['label']),
			$message,
		);
	}
	echo wfHelperString::plainTextTable($table) . "\n\n";
endforeach;
?>

## <?php esc_html_e('IP Detection', 'wordfence') ?>: <?php esc_html_e('Methods of detecting a visitor\'s IP address.', 'wordfence') ?> ##

<?php
$howGet = wfConfig::get('howGetIPs', false);
list($currentIP, $currentServerVarForIP) = wfUtils::getIPAndServerVariable();
$howGetHasErrors = $howGet && (!$currentServerVarForIP || $howGet !== $currentServerVarForIP);

$table = array(
	array(
		__('IPs', 'wordfence'),
		__('Value', 'wordfence'),
		__('Used', 'wordfence'),
	),
);

$serverVariables = array(
	'REMOTE_ADDR'           => 'REMOTE_ADDR',
	'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP',
	'HTTP_X_REAL_IP'        => 'X-Real-IP',
	'HTTP_X_FORWARDED_FOR'  => 'X-Forwarded-For',
);
foreach (wfUtils::getAllServerVariableIPs() as $variable => $ip) {

	$ipValue = '';

	if (!$ip) {
		$ipValue = __('(not set)', 'wordfence');
	} elseif (is_array($ip)) {
		$ipValue = array_map('strip_tags', $ip);
		$ipValue = str_replace($currentIP, "**$currentIP**", implode(', ', $ipValue));
	} else {
		$ipValue = strip_tags($ip);
	}

	$used = '';
	if ($currentServerVarForIP && $currentServerVarForIP === $variable) {
		$used = __('In use', 'wordfence');
	} else if ($howGet === $variable) {
		$used = __('Configured but not valid', 'wordfence');
	}
	$table[] = array(
		isset($serverVariables[$variable]) ? $serverVariables[$variable] : $variable,
		$ipValue,
		$used,
	);
}

$proxies = wfConfig::get('howGetIPs_trusted_proxies', '');
$table[] = array(
	__('Trusted Proxies', 'wordfence'),
	strip_tags(implode(', ', explode("\n", empty($proxies) ? __('(not set)', 'wordfence') : $proxies))),
	'',
);

$preset = wfConfig::get('howGetIPs_trusted_proxy_preset'); 
$presets = wfConfig::getJSON('ipResolutionList', array()); 
$table[] = array(
	__('Trusted Proxy Preset', 'wordfence'),
	strip_tags((is_array($presets) && isset($presets[$preset])) ? $presets[$preset]['name'] : __('(not set)', 'wordfence')),
	'',
);

echo wfHelperString::plainTextTable($table) . "\n\n";

?>

## <?php esc_html_e('WordPress Settings', 'wordfence') ?>: <?php esc_html_e('WordPress version and internal settings/constants.', 'wordfence') ?> ##

<?php
$table = array(
	array(
		__('Setting Name', 'wordfence'),
		__('Description', 'wordfence'),
		__('Value', 'wordfence'),
	),
);

foreach (wfDiagnostic::getWordpressValues() as $settingName => $settingData) {
	$escapedName = strip_tags($settingName);
	$escapedDescription = '';
	$escapedValue = __('(not set)', 'wordfence');
	if (is_array($settingData)) {
		$escapedDescription = strip_tags($settingData['description']);
		if (isset($settingData['value'])) {
			$escapedValue = strip_tags($settingData['value']);
		}
	} else {
		$escapedDescription = strip_tags($settingData);
		if (defined($settingName)) {
			$escapedValue = strip_tags(constant($settingName));
		}
	}

	$table[] = array(
		$escapedName,
		$escapedDescription,
		$escapedValue,
	);
}

echo wfHelperString::plainTextTable($table) . "\n\n";

?>

## <?php esc_html_e('WordPress Plugins', 'wordfence') ?>: <?php esc_html_e('Status of installed plugins.', 'wordfence') ?> ##

<?php

$table = array(
	array(__('Name', 'wordfence'), __('Status', 'wordfence')),
);

foreach ($plugins as $plugin => $pluginData) {
	$slug = $plugin;
	if (preg_match('/^([^\/]+)\//', $plugin, $tableMatches)) {
		$slug = $tableMatches[1];
	} else if (preg_match('/^([^\/.]+)\.php$/', $plugin, $tableMatches)) {
		$slug = $tableMatches[1];
	}

	$name = strip_tags(sprintf('%s (%s)', $pluginData['Name'], $slug));
	if (!empty($pluginData['Version'])) {
		$name .= ' - ' . strip_tags(sprintf(/* translators: version number */ __('Version %s', 'wordfence'), $pluginData['Version']));
	}

	if (array_key_exists(trailingslashit(WP_PLUGIN_DIR) . $plugin, $activeNetworkPlugins)) {
		$status = __('Network Activated', 'wordfence');
	} elseif (array_key_exists($plugin, $activePlugins)) {
		$status = __('Active', 'wordfence');
	} else {
		$status = __('Inactive', 'wordfence');
	}
	$table[] = array(
		$name,
		$status,
	);
}

echo wfHelperString::plainTextTable($table, 100) . "\n\n";

?>

## <?php esc_html_e('Must-Use WordPress Plugins', 'wordfence') ?>: <?php esc_html_e('WordPress "mu-plugins" that are always active, including those provided by hosts.', 'wordfence') ?> ##

<?php

$table = array(
	array(__('Name', 'wordfence'), __('Status', 'wordfence')),
);

if (!empty($muPlugins)) {
	foreach ($muPlugins as $plugin => $pluginData) {
		$slug = $plugin;
		if (preg_match('/^([^\/]+)\//', $plugin, $tableMatches)) {
			$slug = $tableMatches[1];
		} else if (preg_match('/^([^\/.]+)\.php$/', $plugin, $tableMatches)) {
			$slug = $tableMatches[1];
		}

		$name = strip_tags(sprintf('%s (%s)', $pluginData['Name'], $slug));
		if (!empty($pluginData['Version'])) {
			$name .= ' - ' . strip_tags(sprintf(/* translators: version number */ __('Version %s', 'wordfence'), $pluginData['Version']));
		}

		$table[] = array(
			$name,
			__('Active', 'wordfence'),
		);
	}
} else {
	$table[] = array(
		__('No MU-Plugins', 'wordfence'),
		'',
	);
}

echo wfHelperString::plainTextTable($table, 100) . "\n\n";

?>

## <?php esc_html_e('Drop-In WordPress Plugins', 'wordfence') ?>: <?php esc_html_e('WordPress "drop-in" plugins that are active.', 'wordfence') ?> ##

<?php

//Taken from plugin.php and modified to always show multisite drop-ins
$dropins = array(
	'advanced-cache.php'      => array(__('Advanced caching plugin', 'wordfence'), 'WP_CACHE'), // WP_CACHE
	'db.php'                  => array(__('Custom database class', 'wordfence'), true), // auto on load
	'db-error.php'            => array(__('Custom database error message', 'wordfence'), true), // auto on error
	'install.php'             => array(__('Custom installation script', 'wordfence'), true), // auto on installation
	'maintenance.php'         => array(__('Custom maintenance message', 'wordfence'), true), // auto on maintenance
	'object-cache.php'        => array(__('External object cache', 'wordfence'), true), // auto on load
	'php-error.php'           => array(__('Custom PHP error message', 'wordfence'), true), // auto on error
	'fatal-error-handler.php' => array(__('Custom PHP fatal error handler', 'wordfence'), true), // auto on error
);
$dropins['sunrise.php'] = array(__('Executed before Multisite is loaded', 'wordfence'), is_multisite() && 'SUNRISE'); // SUNRISE
$dropins['blog-deleted.php'] = array(__('Custom site deleted message', 'wordfence'), is_multisite()); // auto on deleted blog
$dropins['blog-inactive.php'] = array(__('Custom site inactive message', 'wordfence'), is_multisite()); // auto on inactive blog
$dropins['blog-suspended.php'] = array(__('Custom site suspended message', 'wordfence'), is_multisite()); // auto on archived or spammed blog

$table = array(
	array(__('Name', 'wordfence'), __('Status', 'wordfence')),
);

foreach ($dropins as $file => $data) {
	$active = file_exists(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $file) && is_readable(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $file) && $data[1];
	$table[] = array(
		sprintf('%s (%s)', $data[0], $file),
		$active ? __('Active', 'wordfence') : __('Inactive', 'wordfence'),
	);
}

echo wfHelperString::plainTextTable($table, 100) . "\n\n";

?>

## <?php esc_html_e('Themes', 'wordfence') ?>: <?php esc_html_e('Status of installed themes.', 'wordfence') ?> ##

<?php

$table = array(
	array(__('Name', 'wordfence'), __('Status', 'wordfence')),
);

if (!empty($themes)) {
	foreach ($themes as $theme => $themeData) {
		$slug = $theme;
		if (preg_match('/^([^\/]+)\//', $theme, $tableMatches)) {
			$slug = $tableMatches[1];
		} else if (preg_match('/^([^\/.]+)\.php$/', $theme, $tableMatches)) {
			$slug = $tableMatches[1];
		}

		$name = strip_tags(sprintf('%s (%s)', $themeData['Name'], $slug));
		if (!empty($themeData['Version'])) {
			$name .= ' - ' . strip_tags(sprintf(/* translators: version number */ __('Version %s', 'wordfence'), $themeData['Version']));
		}

		if ($currentTheme instanceof WP_Theme && $theme === $currentTheme->get_stylesheet()) {
			$status = __('Active', 'wordfence');
		} else {
			$status = __('Inactive', 'wordfence');
		}
		$table[] = array(
			$name,
			$status,
		);
	}
} else {
	$table[] = array(
		__('No Themes', 'wordfence'),
		''
	);
}

echo wfHelperString::plainTextTable($table) . "\n\n";

?>

## <?php esc_html_e('Cron Jobs', 'wordfence') ?>: <?php esc_html_e('List of WordPress cron jobs scheduled by WordPress, plugins, or themes.', 'wordfence') ?> ##

<?php
$cron = _get_cron_array();

$table = array(
	array(__('Run Time', 'wordfence'), __('Job', 'wordfence')),
);
foreach ($cron as $timestamp => $values) {
	if (is_array($values)) {
		foreach ($values as $cron_job => $v) {
			if (is_numeric($timestamp)) {
				$overdue = ((time() - 1800) > $timestamp);

				$table[] = array(
					strip_tags(date('r', $timestamp)) . ($overdue ? ' **(' . __('Overdue', 'wordfence') . ')**' : ''),
					strip_tags($cron_job),
				);
			}
		}
	}
}

echo wfHelperString::plainTextTable($table) . "\n\n";

?>

## <?php esc_html_e('Database Tables', 'wordfence') ?>: <?php esc_html_e('Database table names, sizes, timestamps, and other metadata.', 'wordfence') ?> ##

<?php
global $wpdb;
$wfdb = new wfDB();
//This must be done this way because MySQL with InnoDB tables does a full regeneration of all metadata if we don't. That takes a long time with a large table count.
$total = $wfdb->querySingle('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()');

$wordfenceTableNames = wfSchema::tableList();
$optionalWordfenceTableNames = wfSchema::optionalTableList();

if (WFWAF_IS_WINDOWS) {
	$wordfenceTableNames = wfUtils::array_strtolower($wordfenceTableNames);
	$optionalWordfenceTableNames = wfUtils::array_strtolower($optionalWordfenceTableNames);
}

$wordfenceTableNamesQuerySegment = array_map(function($t) { return "'" . esc_sql($t) . "'"; }, array_merge(array_values($wordfenceTableNames), array_values($optionalWordfenceTableNames)));
$existingWordfenceTables = wfUtils::array_column($wfdb->querySelect('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (' . implode(',', $wordfenceTableNamesQuerySegment) . ') ORDER BY TABLE_NAME ASC'), 'TABLE_NAME');
if (WFWAF_IS_WINDOWS) {
	$existingWordfenceTables = wfUtils::array_strtolower($existingWordfenceTables);
}

$otherTables = wfUtils::array_column($wfdb->querySelect('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME NOT IN (' . implode(',', $wordfenceTableNamesQuerySegment) . ') ORDER BY TABLE_NAME ASC LIMIT 250'), 'TABLE_NAME');
$otherTableNamesQuerySegment = array_map(function($t) { return "'" . esc_sql($t) . "'"; }, $otherTables);

$q = $wfdb->querySelect("SHOW TABLE STATUS WHERE Name IN (" . implode(',', array_merge($wordfenceTableNamesQuerySegment, $otherTableNamesQuerySegment)) . ')');

if ($q) {
	$databaseCols = count($q[0]);
	
	$hasAll = true;
	$existingTables = wfUtils::array_column($q, 'Name');
	if (WFWAF_IS_WINDOWS) { $existingTables = wfUtils::array_strtolower($existingTables); } //Windows MySQL installations are case-insensitive
	$missingTables = array();
	foreach ($wordfenceTableNames as $t => $table) {
		if (!in_array($table, $existingTables)) {
			$hasAll = false;
			$missingTables[] = $t;
		}
	}

	if ($hasAll) {
		_e('All Tables Exist', 'wordfence');
	} else {
		printf(/* translators: 1. WordPress table prefix. 2. Wordfence table case. 3. List of database tables. */ __('Tables missing (prefix %1$s, %2$s): %3$s', 'wordfence'), wfDB::networkPrefix(), wfSchema::usingLowercase() ? __('lowercase', 'wordfence') : __('regular case', 'wordfence'), implode(', ', $missingTables));
	}
	
	echo "\n";
	printf(/* translators: 1. Number of tables */ _n('%1$s Table in Database', '%1$s Tables in Database', $total, 'wordfence' ), $total );
	echo "\n";

	$val = wfUtils::array_first($q);
	$actualKeyOrder = array_keys($val);
	$preferredKeyOrder = array('Name', 'Comment', 'Engine', 'Rows', 'Avg_row_length', 'Data_length', 'Index_length', 'Auto_increment', 'Create_time', 'Row_format', 'Collation', 'Version', 'Max_data_length', 'Data_free', 'Update_time', 'Check_time', 'Checksum', 'Create_options');
	$leftoverKeys = array();
	$displayKeyOrder = array();
	foreach ($preferredKeyOrder as $k) {
		if (in_array($k, $actualKeyOrder)) {
			$displayKeyOrder[] = $k;
		}
	}

	$diff = array_diff($actualKeyOrder, $preferredKeyOrder);
	$displayKeyOrder = array_merge($displayKeyOrder, $diff);

	$table = array(
		$displayKeyOrder,
	);
	
	usort($q, function($t1, $t2) use ($existingWordfenceTables) {
		$name1 = $t1['Name'];
		$name2 = $t2['Name'];
		if (WFWAF_IS_WINDOWS) {
			$name1 = strtolower($name1);
			$name2 = strtolower($name2);
		}
		
		$ours1 = in_array($name1, $existingWordfenceTables);
		$ours2 = in_array($name2, $existingWordfenceTables);
		if ($ours1 && !$ours2) { return -1; }
		if (!$ours1 && $ours2) { return 1; }
		
		return strcasecmp($name1, $name2);
	});

	$count = 0;
	foreach ($q as $val) {
		$tableRow = array();
		foreach ($displayKeyOrder as $tkey) {
			$tableRow[] = isset($val[$tkey]) ? $val[$tkey] : '';
		}
		$table[] = $tableRow;

		$count++;
	}
	
	if ($total > $count) {
		$tableRow = array_fill(0, $databaseCols, '');
		$tableRow[0] = sprintf(__('and %d more', 'wordfence'), $total - $count);
		$table[] = $tableRow;
	}
}

echo wfHelperString::plainTextTable($table) . "\n\n";

?>

## <?php esc_html_e('Log Files', 'wordfence') ?>: <?php esc_html_e('PHP error logs generated by your site, if enabled by your host.', 'wordfence') ?> ##

<?php

$table = array(
	array(
		__('File', 'wordfence'),
	),
);

$errorLogs = wfErrorLogHandler::getErrorLogs();
if (count($errorLogs) < 1) {
	$table[] = array(
		__('No log files found.', 'wordfence'),
	);
} else {
	foreach ($errorLogs as $log => $readable) {
		$metadata = array();
		if (wfUtils::funcEnabled('filesize')) {
			$rawSize = @filesize($log);
			if ($rawSize !== false) {
				$metadata[] = wfUtils::formatBytes($rawSize);
			}
		}

		if (wfUtils::funcEnabled('lstat')) {
			$rawStat = @lstat($log);
			if (is_array($rawStat) && isset($rawStat['mtime'])) {
				$ts = $rawStat['mtime'];
				$utc = new DateTimeZone('UTC');
				$dtStr = gmdate("c", (int) $ts); //Have to do it this way because of PHP 5.2
				$dt = new DateTime($dtStr, $utc);
				$metadata[] = $dt->format('M j, Y G:i:s') . ' ' . __('UTC', 'wordfence');
			}
		}

		$shortLog = $log;
		if (strpos($shortLog, ABSPATH) === 0) {
			$shortLog = '~/' . substr($shortLog, strlen(ABSPATH));
		}

		$logData = strip_tags($shortLog);
		if (!empty($metadata)) {
			$logData .= ' (' . implode(', ', $metadata) . ')';
		}
		$table[] = array($logData);
	}
}

echo wfHelperString::plainTextTable($table) . "\n\n";

?>

## <?php esc_html_e('Scan Issues', 'wordfence') ?> ##

<?php

$issues = wfIssues::shared()->getIssues(0, 50, 0, 50);
$issueCounts = array_merge(array('new' => 0, 'ignoreP' => 0, 'ignoreC' => 0), wfIssues::shared()->getIssueCounts());
$issueTypes = wfIssues::validIssueTypes();

printf(__('New Issues (%d total)', 'wordfence'), $issueCounts['new']);
echo "\n\n";

if (isset($issues['new']) && count($issues['new'])) {
	foreach ($issues['new'] as $i) {
		if (!in_array($i['type'], $issueTypes)) {
			continue;
		}

		$viewContent = '';
		try {
			$viewContent = wfView::create('scanner/text/issue-' . $i['type'], array('textOutput' => $i))->render();
		}
		catch (wfViewNotFoundException $e) {
			//Ignore -- should never happen since we validate the type
		}

		if (!empty($viewContent)) {
			echo strip_tags($viewContent) . "\n\n";
		}
	}
}
else {
	_e('No New Issues', 'wordfence');
	echo "\n";
}

?>

## <?php esc_html_e('Wordfence Settings', 'wordfence') ?>: <?php esc_html_e('Diagnostic Wordfence settings/constants.', 'wordfence') ?> ##

<?php
$table = array(
	array(
		__('Setting', 'wordfence'),
		__('Value', 'wordfence'),
	),
);

foreach (wfDiagnostic::getWordfenceValues() as $settingData) {
	if (isset($settingData['subheader'])) {
		$table[] = strip_tags($settingData['subheader']);
		continue;
	}
	
	$escapedDescription = strip_tags($settingData['description']);
	$escapedValue = __('(not set)', 'wordfence');
	if (isset($settingData['value'])) {
		$escapedValue = strip_tags($settingData['value']);
	}

	$table[] = array(
		$escapedDescription,
		$escapedValue,
	);
}

echo wfHelperString::plainTextTable($table) . "\n\n";
?>

## <?php esc_html_e('Wordfence Central', 'wordfence') ?>: <?php esc_html_e('Diagnostic connection information for Wordfence Central.', 'wordfence') ?> ##

<?php
$table = array(
	array(
		__('Name', 'wordfence'),
		__('Value', 'wordfence'),
	),
);

foreach (wfDiagnostic::getWordfenceCentralValues() as $settingData) {
	if (isset($settingData['subheader'])) {
		$table[] = strip_tags($settingData['subheader']);
		continue;
	}
	
	$escapedDescription = strip_tags($settingData['description']);
	$escapedValue = __('(not set)', 'wordfence');
	if (isset($settingData['value'])) {
		$escapedValue = strip_tags($settingData['value']);
	}
	
	$table[] = array(
		$escapedDescription,
		$escapedValue,
	);
}

echo wfHelperString::plainTextTable($table) . "\n\n";
?>

## PHPInfo ##

<?php
ob_start();
if (wfUtils::funcEnabled('phpinfo')) { phpinfo(); } else { echo "\n\n" . __('Unable to output phpinfo content because it is disabled', 'wordfence') . "\n\n"; }
$phpinfo = ob_get_clean();

if (preg_match_all('#(?:<h2>(.*?)</h2>\s*)?<table[^>]*>(.*?)</table>#is', $phpinfo, $tableMatches)) {
	foreach ($tableMatches[2] as $countIndex => $tableContents) {
		$table = array();
		if (preg_match_all('#<tr[^>]*>(.*?)</tr>#is', $tableContents, $rowMatches)) {
			foreach ($rowMatches[1] as $rowContents) {
				if (preg_match_all('#<t[hd][^>]*>(.*?)</t[hd]>#is', $rowContents, $colMatches)) {
					$row = array();
					foreach ($colMatches[1] as $colContents) {
						$row[] = trim(strip_tags(html_entity_decode($colContents)));
					}
					$table[] = $row;
				}
			}
		}
		if (array_key_exists($countIndex, $tableMatches[1]) && $tableMatches[1][$countIndex]) {
			echo "## " . strip_tags($tableMatches[1][$countIndex]) . " ##\n\n";
		}

		$tableString = wfHelperString::plainTextTable($table);
		if ($tableString) {
			echo $tableString . "\n\n";
		}
	}
}


?>