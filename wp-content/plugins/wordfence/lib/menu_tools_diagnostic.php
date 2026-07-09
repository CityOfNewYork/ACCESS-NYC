<?php
if (!defined('WORDFENCE_VERSION')) { exit; }

/** @var boolean $inEmail */

$diagnostic = new wfDiagnostic;
$plugins = get_plugins();
$activePlugins = array_flip(get_option('active_plugins'));
$activeNetworkPlugins = is_multisite() ? array_flip(wp_get_active_network_plugins()) : array();
$muPlugins = get_mu_plugins();
$themes = wp_get_themes();
$currentTheme = wp_get_theme();
$cols = 3;

$w = new wfConfig();
if (!isset($sendingDiagnosticEmail)) {
	$sendingDiagnosticEmail = false;
}
?>
<?php if (!$sendingDiagnosticEmail): ?>
<script type="application/javascript">
	(function($) {
		$(function() {
			document.title = "<?php esc_attr_e('Diagnostics', 'wordfence'); ?>" + " \u2039 " + WFAD.basePageName;
		});
	})(jQuery);
</script>
<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
<?php endif; ?>
<div id="wf-diagnostics">
	<?php if (!$sendingDiagnosticEmail): ?>
		<div class="wordfence-vue-wrapper" data-base-component="DiagnosticsHeader"></div>
	<?php endif; ?>
	<div class="wf-diagnostics-wrapper">
		<?php foreach ($diagnostic->getResults() as $title => $tests):
			$key = sanitize_key('wf-diagnostics-' . $title);
			$hasFailingTest = false;
			foreach ($tests['results'] as $result) {
				$infoOnly = isset($result['infoOnly']) && $result['infoOnly'];
				if (!$result['test'] && !$infoOnly) {
					$hasFailingTest = true;
					break;
				}
			}

			if ($inEmail): ?>
				<table>
					<thead>
					<tr>
						<th colspan="2"><?php echo esc_html($title) ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($tests['results'] as $result): ?>
						<?php
						$infoOnly = isset($result['infoOnly']) && $result['infoOnly'];
						?>
						<tr>
							<td class="wf-diagnostics-item-label"><?php echo (is_array($result['label']) && isset($result['label']['raw']) && $result['label']['raw'] ? $result['label']['value'] : wp_kses($result['label'], array(
									'code'   => array(),
									'strong' => array(),
									'em'     => array(),
									'a'      => array('href' => true),
									'span'	 => array('class' => true)
								))) ?></td>
							<td>
								<?php if ($infoOnly): ?>
									<div class="wf-result-info"><?php echo (is_array($result['message']) && isset($result['message']['escaped']) ? $result['message']['escaped'] : nl2br(esc_html($result['message']))); ?></div>
								<?php elseif ($result['test']): ?>
									<div class="wf-result-success"><?php echo (is_array($result['message']) && isset($result['message']['escaped']) ? $result['message']['escaped'] : nl2br(esc_html($result['message']))); ?></div>
								<?php elseif (isset($result['warn']) && $result['warn']): ?>
									<div class="wf-result-warn"><?php echo (is_array($result['message']) && isset($result['message']['escaped']) ? $result['message']['escaped'] : nl2br(esc_html($result['message']))); ?></div>
								<?php else: ?>
									<div class="wf-result-error"><?php echo (is_array($result['message']) && isset($result['message']['escaped']) ? $result['message']['escaped'] : nl2br(esc_html($result['message']))); ?></div>
								<?php endif ?>
								<?php if (isset($result['detail']) && !empty($result['detail'])): ?>
									<p><strong><?php esc_html_e('Additional Detail', 'wordfence'); ?></strong><br><?php echo (is_array($result['detail']) && isset($result['detail']['escaped']) ? $result['detail']['escaped'] : nl2br(esc_html($result['detail']))); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php else: ?>
			<div class="wordfence-vue-wrapper"
					 data-base-component="DiagnosticsBlock"
					 data-prop-state-key="<?php echo esc_attr($key) ?>"
					 data-prop-title="<?php echo esc_attr($title) ?>"
					 data-prop-subtitle="<?php echo esc_attr($tests['description']) ?>"
					 data-prop-has-failing-test="<?php echo $hasFailingTest ? 'true' : 'false' ?>"
					 data-prop-results="<?php echo wfUtils::esc_attr(json_encode($tests['results']), ENT_QUOTES, 'UTF-8',true) ?>"
			></div>
			<?php endif ?>

		<?php endforeach ?>
		<?php
		$howGet = wfConfig::get('howGetIPs', false);
		list($currentIP, $currentServerVarForIP) = wfUtils::getIPAndServerVariable();
		$howGetHasErrors = $howGet && (! $currentServerVarForIP || $howGet !== $currentServerVarForIP);
		?>
		<div class="wf-block wf-legacy<?php echo ($howGetHasErrors ? ' wf-diagnostic-fail' : '') . (wfPersistenceController::shared()->isActive('wf-diagnostics-client-ip') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-client-ip') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('IP Detection', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('Methods of detecting a visitor\'s IP address.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-client-ip') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">

				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody class="thead">
					<tr>
						<th><?php esc_html_e('IPs', 'wordfence'); ?></th>
						<th><?php esc_html_e('Value', 'wordfence'); ?></th>
						<th><?php esc_html_e('Used', 'wordfence'); ?></th>
					</tr>
					</tbody>
					<tbody>
					<?php
					$serverVariables = array(
						'REMOTE_ADDR'           => 'REMOTE_ADDR',
						'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP',
						'HTTP_X_REAL_IP'        => 'X-Real-IP',
						'HTTP_X_FORWARDED_FOR'  => 'X-Forwarded-For',
					);
					foreach (wfUtils::getAllServerVariableIPs() as $variable => $ip): ?>
						<tr>
							<td><?php echo isset($serverVariables[$variable]) ? $serverVariables[$variable] : $variable ?></td>
							<td><?php
								if (! $ip) {
									_e('(not set)', 'wordfence');
								} elseif (is_array($ip)) {
									$output = array_map('esc_html', $ip);
									echo str_replace($currentIP, "<strong>{$currentIP}</strong>", implode(', ', $output));
								} else {
									echo esc_html($ip);
								}
							?></td>
							<?php if ($currentServerVarForIP && $currentServerVarForIP === $variable): ?>
								<td class="wf-result-success"><?php esc_html_e('In use', 'wordfence'); ?></td>
							<?php elseif ($howGet === $variable): ?>
								<td class="wf-result-error"><?php esc_html_e('Configured but not valid', 'wordfence'); ?></td>
							<?php else: ?>
								<td></td>
							<?php endif ?>
						</tr>
					<?php endforeach ?>
					<tr>
						<td><?php esc_html_e('Trusted Proxies', 'wordfence'); ?></td>
						<td><?php $proxies = wfConfig::get('howGetIPs_trusted_proxies', ''); echo esc_html(implode(', ', explode("\n", empty($proxies) ? __('(not set)', 'wordfence') : $proxies))); ?></td>
						<td></td>
					</tr>
					<tr>
						<td><?php esc_html_e('Trusted Proxy Preset', 'wordfence'); ?></td>
						<td><?php $preset = wfConfig::get('howGetIPs_trusted_proxy_preset'); $presets = wfConfig::getJSON('ipResolutionList', array()); echo esc_html((is_array($presets) && isset($presets[$preset])) ? $presets[$preset]['name'] : __('(not set)', 'wordfence')); ?></td>
						<td></td>
					</tr>
					</tbody>
				</table>

			</div>
		</div>

		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-constants') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-wordpress-constants') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('WordPress Settings', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('WordPress version and internal settings/constants.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-constants') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody>
					<?php
					foreach (wfDiagnostic::getWordpressValues() as $settingName => $settingData):
						$escapedName = esc_html($settingName);
						$escapedDescription = '';
						$escapedValue = __('(not set)', 'wordfence');
						if (is_array($settingData)) {
							$escapedDescription = esc_html($settingData['description']);
							if (isset($settingData['value'])) {
								$escapedValue = esc_html($settingData['value']);
							}
						} else {
							$escapedDescription = esc_html($settingData);
							if (defined($settingName)) {
								$escapedValue = esc_html(constant($settingName));
							}
						}
						?>
						<tr>
							<td><strong><?php echo $escapedName ?></strong></td>
							<td><?php echo $escapedDescription ?></td>
							<td><?php echo $escapedValue ?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-plugins') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-wordpress-plugins') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('WordPress Plugins', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('Status of installed plugins.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-plugins') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody>
					<?php foreach ($plugins as $plugin => $pluginData): ?>
						<?php
						$slug = $plugin;
						if (preg_match('/^([^\/]+)\//', $plugin, $matches)) {
							$slug = $matches[1];
						}
						else if (preg_match('/^([^\/.]+)\.php$/', $plugin, $matches)) {
							$slug = $matches[1];
						}
						?>
						<tr>
							<td>
								<strong><?php echo esc_html($pluginData['Name']); ?> (<?php echo esc_html($slug); ?>)</strong>
								<?php if (!empty($pluginData['Version'])): ?>
									- <?php echo esc_html(sprintf(/* translators: version number */ __('Version %s', 'wordfence'), $pluginData['Version'])); ?>
								<?php endif ?>
							</td>
							<?php if (array_key_exists(trailingslashit(WP_PLUGIN_DIR) . $plugin, $activeNetworkPlugins)): ?>
								<td class="wf-result-success"><?php esc_html_e('Network Activated', 'wordfence'); ?></td>
							<?php elseif (array_key_exists($plugin, $activePlugins)): ?>
								<td class="wf-result-success"><?php esc_html_e('Active', 'wordfence'); ?></td>
							<?php else: ?>
								<td class="wf-result-inactive"><?php esc_html_e('Inactive', 'wordfence'); ?></td>
							<?php endif ?>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-mu-wordpress-plugins') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-mu-wordpress-plugins') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('Must-Use WordPress Plugins', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('WordPress "mu-plugins" that are always active, including those provided by hosts.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-mu-wordpress-plugins') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<?php if (!empty($muPlugins)): ?>
						<tbody>
						<?php foreach ($muPlugins as $plugin => $pluginData): ?>
							<?php
							$slug = $plugin;
							if (preg_match('/^([^\/]+)\//', $plugin, $matches)) {
								$slug = $matches[1];
							}
							else if (preg_match('/^([^\/.]+)\.php$/', $plugin, $matches)) {
								$slug = $matches[1];
							}
							?>
							<tr>
								<td>
									<strong><?php echo esc_html($pluginData['Name']) ?> (<?php echo esc_html($slug); ?>)</strong>
									<?php if (!empty($pluginData['Version'])): ?>
										- <?php echo esc_html(sprintf(/* translators: version number */ __('Version %s', 'wordfence'), $pluginData['Version'])); ?>
									<?php endif ?>
								</td>
								<td class="wf-result-success"><?php esc_html_e('Active', 'wordfence'); ?></td>
							</tr>
						<?php endforeach ?>
						</tbody>
					<?php else: ?>
						<tbody>
						<tr>
							<td><?php esc_html_e('No MU-Plugins', 'wordfence'); ?></td>
						</tr>
						</tbody>

					<?php endif ?>
				</table>
			</div>
		</div>
		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-dropin-wordpress-plugins') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-dropin-wordpress-plugins') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('Drop-In WordPress Plugins', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('WordPress "drop-in" plugins that are active.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-dropin-wordpress-plugins') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody>
					<?php
					//Taken from plugin.php and modified to always show multisite drop-ins
					$dropins = array(
						'advanced-cache.php'	 => array( __( 'Advanced caching plugin', 'wordfence' ), 'WP_CACHE' ), // WP_CACHE
						'db.php'            	 => array( __( 'Custom database class', 'wordfence' ), true ), // auto on load
						'db-error.php'      	 => array( __( 'Custom database error message', 'wordfence' ), true ), // auto on error
						'install.php'       	 => array( __( 'Custom installation script', 'wordfence' ), true ), // auto on installation
						'maintenance.php'   	 => array( __( 'Custom maintenance message', 'wordfence' ), true ), // auto on maintenance
						'object-cache.php'  	 => array( __( 'External object cache', 'wordfence' ), true ), // auto on load
						'php-error.php'          => array( __( 'Custom PHP error message', 'wordfence' ), true ), // auto on error
						'fatal-error-handler.php'=> array( __( 'Custom PHP fatal error handler', 'wordfence' ), true ), // auto on error
					);
					$dropins['sunrise.php'       ] = array( __( 'Executed before Multisite is loaded', 'wordfence' ), is_multisite() && 'SUNRISE' ); // SUNRISE
					$dropins['blog-deleted.php'  ] = array( __( 'Custom site deleted message', 'wordfence' ), is_multisite() ); // auto on deleted blog
					$dropins['blog-inactive.php' ] = array( __( 'Custom site inactive message', 'wordfence' ), is_multisite() ); // auto on inactive blog
					$dropins['blog-suspended.php'] = array( __( 'Custom site suspended message', 'wordfence' ), is_multisite() ); // auto on archived or spammed blog
					?>
					<?php foreach ($dropins as $file => $data): ?>
						<?php
						$active = file_exists(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $file) && is_readable(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $file) && $data[1];
						?>
						<tr>
							<td>
								<strong><?php echo esc_html($data[0]) ?> (<?php echo esc_html($file); ?>)</strong>
							</td>
							<?php if ($active): ?>
								<td class="wf-result-success"><?php esc_html_e('Active', 'wordfence'); ?></td>
							<?php else: ?>
								<td class="wf-result-inactive"><?php esc_html_e('Inactive', 'wordfence'); ?></td>
							<?php endif; ?>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-themes') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-wordpress-themes') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('Themes', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('Status of installed themes.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-themes') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<?php if (!empty($themes)): ?>
						<tbody>
						<?php foreach ($themes as $theme => $themeData): ?>
							<?php
							$slug = $theme;
							if (preg_match('/^([^\/]+)\//', $theme, $matches)) {
								$slug = $matches[1];
							}
							else if (preg_match('/^([^\/.]+)\.php$/', $theme, $matches)) {
								$slug = $matches[1];
							}
							?>
							<tr>
								<td>
									<strong><?php echo esc_html($themeData['Name']) ?> (<?php echo esc_html($slug); ?>)</strong>
									<?php if (!empty($themeData['Version'])): ?>
										- <?php echo esc_html(sprintf(/* translators: version number */ __('Version %s', 'wordfence'), $themeData['Version'])); ?>
									<?php endif ?>
								<?php if ($currentTheme instanceof WP_Theme && $theme === $currentTheme->get_stylesheet()): ?>
									<td class="wf-result-success"><?php esc_html_e('Active', 'wordfence'); ?></td>
								<?php else: ?>
									<td class="wf-result-inactive"><?php esc_html_e('Inactive', 'wordfence'); ?></td>
								<?php endif ?>
							</tr>
						<?php endforeach ?>
						</tbody>
					<?php else: ?>
						<tbody>
						<tr>
							<td><?php esc_html_e('No Themes', 'wordfence'); ?></td>
						</tr>
						</tbody>

					<?php endif ?>
				</table>
			</div>
		</div>
		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-cron-jobs') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-wordpress-cron-jobs') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('Cron Jobs', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('List of WordPress cron jobs scheduled by WordPress, plugins, or themes.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-wordpress-cron-jobs') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody>
					<?php
					$cron = _get_cron_array();

					foreach ($cron as $timestamp => $values) {
						if (is_array($values)) {
							foreach ($values as $cron_job => $v) {
								if (is_numeric($timestamp)) {
									$overdue = ((time() - 1800) > $timestamp);
									?>
									<tr<?php echo $overdue ? ' class="wf-overdue-cron"' : ''; ?>>
										<td><?php echo esc_html(date('r', $timestamp)) . ($overdue ? ' <strong>(' . esc_html__('Overdue', 'wordfence') . ')</strong>' : '') ?></td>
										<td><?php echo esc_html($cron_job) ?></td>
									</tr>
									<?php
								}
							}
						}
					}
					?>
					</tbody>
				</table>
			</div>
		</div>

		<?php
		global $wpdb;
		$wfdb = new wfDB();
		
		//This must be done this way (rather than SHOW TABLES) because MySQL with InnoDB tables does a full regeneration of all metadata if we don't. That takes a long time with a large table count.
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
		if ($q):
			$databaseCols = count($q[0]);
			?>
			<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-database-tables') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-database-tables') ?>">
				<div class="wf-block-header">
					<div class="wf-block-header-content">
						<div class="wf-block-title">
							<strong><?php esc_html_e('Database Tables', 'wordfence') ?></strong>
							<span class="wf-text-small"><?php esc_html_e('Database table names, sizes, timestamps, and other metadata.', 'wordfence') ?></span>
						</div>
						<div class="wf-block-header-action">
							<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-database-tables') ? 'true' : 'false'); ?>" tabindex="0"></div>
						</div>
					</div>
				</div>
				<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
					<ul class="wf-block-list wf-padding-add-left-large wf-padding-add-right-large">
						<li>
							<div class="wf-diagnostics-item-label"><?php esc_html_e('Wordfence Table Check', 'wordfence'); ?></div>
							<div class="wf-right">
								<?php 
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

									if ($hasAll): ?>
									<div class="wf-result-success"><?php esc_html_e('All Tables Exist', 'wordfence'); ?></div>
									<?php else: ?>
									<div class="wf-result-error"><?php echo esc_html(sprintf(
											/* translators: 1. WordPress table prefix. 2. Wordfence table case. 3. List of database tables. */
											__('Tables missing (prefix %1$s, %2$s): %3$s', 'wordfence'), wfDB::networkPrefix(), wfSchema::usingLowercase() ? __('lowercase', 'wordfence') : __('regular case', 'wordfence'), implode(', ', $missingTables))); ?></div>
									<?php endif; ?>
							</div>
						</li>
						<li style="border-bottom: 1px solid #e2e2e2;">
							<div class="wf-diagnostics-item-label"><?php esc_html_e('Number of Database Tables', 'wordfence'); ?></div>
							<div class="wf-right">
								<div class="wf-result-info"><?php echo esc_html( $total ); ?></div>
							</div>
						</li>
					</ul>
					<div class="wf-add-top-large" style="max-width: 100%; overflow: auto; padding: 1px;">
						<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
							<tbody class="thead thead-subhead" style="font-size: 85%">
							<?php
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
							
							?>
							<tr>
								<?php foreach ($displayKeyOrder as $tkey): ?>
									<th><?php echo esc_html($tkey) ?></th>
								<?php endforeach; ?>
							</tr>
							</tbody>
							<tbody style="font-size: 85%">
							<?php
							$count = 0;
							
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
							
							foreach ($q as $val) {
								?>
								<tr>
								<?php foreach ($displayKeyOrder as $tkey): ?>
									<td><?php if (isset($val[$tkey])) { echo esc_html($val[$tkey]); } ?></td>
								<?php endforeach; ?>
								</tr>
								<?php
								$count++;
							}
							
							if ($total > $count) {
								?>
								<tr>
									<td colspan="<?php echo $databaseCols; ?>"><?php echo esc_html(sprintf(/* translators: Row/record count. */ __('and %d more', 'wordfence'), $total - $count)); ?></td>
								</tr>
								<?php
							}
							?>
							</tbody>

						</table>
					</div>

				</div>
			</div>
		<?php endif ?>
		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-log-files') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-log-files') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('Log Files', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('PHP error logs generated by your site, if enabled by your host.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-log-files') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<div style="max-width: 100%; overflow: auto; padding: 1px;">
					<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
						<tbody class="thead thead-subhead" style="font-size: 85%">
						<tr>
							<th><?php esc_html_e('File', 'wordfence'); ?></th>
							<th><?php esc_html_e('Download', 'wordfence'); ?></th>
						</tr>
						</tbody>
						<tbody style="font-size: 85%">
						<?php
						$errorLogs = wfErrorLogHandler::getErrorLogs();
						if (count($errorLogs) < 1): ?>
							<tr>
								<td colspan="2"><em><?php esc_html_e('No log files found.', 'wordfence'); ?></em></td>
							</tr>
						<?php else:
							foreach ($errorLogs as $log => $readable): ?>
								<?php
								$metadata = array();
								if (is_callable('filesize')) {
									$rawSize = @filesize($log);
									if ($rawSize !== false) {
										$metadata[] = wfUtils::formatBytes(filesize($log));
									}
								}
								
								if (is_callable('lstat')) {
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
								?>
								<tr>
									<td style="width: 100%"><?php echo esc_html($shortLog); if (!empty($metadata)) { echo ' (' . esc_html(implode(', ', $metadata)) . ')'; } ?></td>
									<td style="white-space: nowrap; text-align: right;"><?php echo($readable ? '<a href="#" data-logfile="' . esc_attr($log) . '" class="downloadLogFile" target="_blank" rel="noopener noreferrer" role="button">' . esc_html__('Download', 'wordfence') . '<span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>' : '<em>' . esc_html__('Requires downloading from the server directly', 'wordfence') . '</em>'); ?></td>
								</tr>
							<?php endforeach;
						endif; ?>
						</tbody>

					</table>
				</div>
			</div>
		</div>
	</div>
	
	<?php
	if (!empty($inEmail)) {
		echo '<h1>' . esc_html__('Scan Issues', 'wordfence') . "</h1>\n";
		$issues = wfIssues::shared()->getIssues(0, 50, 0, 50);
		$issueCounts = array_merge(array('new' => 0, 'ignoreP' => 0, 'ignoreC' => 0), wfIssues::shared()->getIssueCounts());
		$issueTypes = wfIssues::validIssueTypes();
		
		echo '<h2>' . esc_html(sprintf(/* translators: Number of scan issues. */ __('New Issues (%d total)', 'wordfence'), $issueCounts['new'])) . "</h2>\n";
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
					echo nl2br($viewContent) . "<br><br>\n";
				}
			}
		}
		else {
			echo '<h1>' . esc_html__('No New Issues', 'wordfence') . "</h1>\n";
		}
	}
	?>
	
	<?php if (!empty($inEmail)): ?>
	<div class="wf-diagnostics-wrapper">
		<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-wordfence-settings') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-wordfence-settings') ?>">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong><?php esc_html_e('Wordfence Settings', 'wordfence') ?></strong>
						<span class="wf-text-small"><?php esc_html_e('Diagnostic Wordfence settings/constants.', 'wordfence') ?></span>
					</div>
					<div class="wf-block-header-action">
						<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-wordfence-settings') ? 'true' : 'false'); ?>" tabindex="0"></div>
					</div>
				</div>
			</div>
			<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody>
					<?php
					foreach (wfDiagnostic::getWordfenceValues() as $settingName => $settingData):
						if (isset($settingData['subheader'])) {
						?>
							<tr>
								<td colspan="2"><strong><?php echo esc_html($settingData['subheader']) ?></strong></td>
							</tr>
						<?php
							continue;
						}
						
						$escapedDescription = strip_tags($settingData['description']);
						$escapedValue = __('(not set)', 'wordfence');
						if (isset($settingData['value'])) {
							$escapedValue = nl2br(strip_tags($settingData['value']));
						}
						?>
						<tr>
							<td><?php echo $escapedDescription ?></td>
							<td><?php echo $escapedValue ?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif ?>
	
	<?php if (!empty($inEmail)): ?>
		<div class="wf-diagnostics-wrapper">
			<div class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-wordfence-central') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-wordfence-central') ?>">
				<div class="wf-block-header">
					<div class="wf-block-header-content">
						<div class="wf-block-title">
							<strong><?php esc_html_e('Wordfence Central', 'wordfence') ?></strong>
							<span class="wf-text-small"><?php esc_html_e('Diagnostic connection information for Wordfence Central.', 'wordfence') ?></span>
						</div>
						<div class="wf-block-header-action">
							<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-wordfence-central') ? 'true' : 'false'); ?>" tabindex="0"></div>
						</div>
					</div>
				</div>
				<div class="wf-block-content wf-clearfix wf-padding-no-left wf-padding-no-right">
					<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
						<tbody>
						<?php
						foreach (wfDiagnostic::getWordfenceCentralValues() as $settingName => $settingData):
							if (isset($settingData['subheader'])) {
								?>
								<tr>
									<td colspan="2"><strong><?php echo esc_html($settingData['subheader']) ?></strong></td>
								</tr>
								<?php
								continue;
							}
							
							$escapedDescription = strip_tags($settingData['description']);
							$escapedValue = __('(not set)', 'wordfence');
							if (isset($settingData['value'])) {
								$escapedValue = nl2br(strip_tags($settingData['value']));
							}
							?>
							<tr>
								<td><?php echo $escapedDescription ?></td>
								<td><?php echo $escapedValue ?></td>
							</tr>
						<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif ?>

	<?php if (!empty($inEmail)): ?>
		<?php if (wfUtils::funcEnabled('phpinfo')) { phpinfo(); } else { echo '<strong>' . esc_html__('Unable to output phpinfo content because it is disabled', 'wordfence') . "</strong>\n"; } ?>
	<?php endif ?>

	<?php if (!empty($emailForm)): ?>
		<div class="wf-diagnostics-wrapper">
			<div id="wf-diagnostics-other-tests" class="wf-block wf-legacy<?php echo(wfPersistenceController::shared()->isActive('wf-diagnostics-other-tests') ? ' wf-active' : '') ?>" data-persistence-key="<?php echo esc_attr('wf-diagnostics-other-tests') ?>">
				<div class="wf-block-header">
					<div class="wf-block-header-content">
						<div class="wf-block-title">
							<strong><?php esc_html_e('Other Tests', 'wordfence') ?></strong>
							<span class="wf-text-small"><?php esc_html_e('System configuration, memory test, send test email from this server.', 'wordfence') ?></span>
						</div>
						<div class="wf-block-header-action">
							<div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('wf-diagnostics-other-tests') ? 'true' : 'false'); ?>" tabindex="0"></div>
						</div>
					</div>
				</div>
				<div class="wf-block-content wf-clearfix">
					<ul class="wf-block-list">
						<li>
							<span>
								<a href="<?php echo wfUtils::siteURLRelative(); ?>?_wfsf=sysinfo&nonce=<?php echo wp_create_nonce('wp-ajax'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Click to view your system\'s configuration in a new window', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
								<a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_DIAGNOSTICS_SYSTEM_CONFIGURATION); ?>" target="_blank" rel="noopener noreferrer" class="wfhelp wf-inline-help"><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
							</span>
						</li>
						<li>
							<span>
								<a href="<?php echo wfUtils::siteURLRelative(); ?>?_wfsf=testmem&nonce=<?php echo wp_create_nonce('wp-ajax'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Test your WordPress host\'s available memory', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
							<a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_DIAGNOSTICS_TEST_MEMORY); ?>" target="_blank" rel="noopener noreferrer" class="wfhelp wf-inline-help"><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
							</span>
						</li>
						<li class="wordfence-vue-wrapper" data-base-component="DiagnosticsSendTestEmail"></li>
						<li class="wordfence-vue-wrapper" data-base-component="DiagnosticsSendTestActivityReport"></li>
						<li class="wordfence-vue-wrapper" data-base-component="DiagnosticsClearCentralConnectionData"></li>
					</ul>

				</div>
			</div>

			<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupDiagnostics" data-prop-state-key="wf-diagnostics-debugging-options"></div>
		</div>

	<?php endif ?>
</div>
<?php if (!$sendingDiagnosticEmail): ?>
<div class="wordfence-vue-wrapper" data-base-component="DiagnosticsModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="ScrollTop"></div>
<?php endif ?>
