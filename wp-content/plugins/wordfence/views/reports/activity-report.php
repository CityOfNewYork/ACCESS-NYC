<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * @var wfActivityReportView $this
 */
?>
<a class="wf-logo" href="//www.wordfence.com/zz8/"><img src="<?php echo wfUtils::getBaseURL(); ?>images/wf-horizontal.svg" alt="Wordfence"/></a>

<h2><?php printf(/* translators: Number of IPs. */ esc_html__('Top %d IPs Blocked', 'wordfence'), $limit); ?></h2>

<?php wfHelperString::cycle(); ?>

<table class="wf-striped-table wf-fixed-table">
	<thead>
		<tr>
			<th width="40%"><?php esc_html_e('IP', 'wordfence'); ?></th>
			<th width="35%"><?php esc_html_e('Country', 'wordfence'); ?></th>
			<th width="25%"><?php esc_html_e('Block Count', 'wordfence'); ?></th> 
		</tr>
	</thead>
	<tbody>
		<?php
		if ($top_ips_blocked):
			require(dirname(__FILE__) . '/../../lib/flags.php'); /** @var array $flags */
			foreach ($top_ips_blocked as $row): ?>
				<tr class="<?php echo wfHelperString::cycle('odd', 'even') ?>">
					<td class="wf-split-word"><code><?php echo wfUtils::inet_ntop($row->IP) ?></code></td>
					<td>
						<?php if ($row->countryCode): ?>
							<span class="wf-flag <?php echo esc_attr('wf-flag-' . strtolower($row->countryCode)); ?>" title="<?php echo esc_attr($row->countryName); ?>"></span>
							&nbsp;
							<?php echo esc_html($row->countryName) ?>
						<?php else: ?>
							<?php esc_html_e('(Unknown)', 'wordfence'); ?>
						<?php endif ?>
					</td>
					<td><?php echo (int) $row->blockCount ?></td>
				</tr>
			<?php endforeach ?>
		<?php else: ?>
			<tr>
				<td colspan="3">
					<?php esc_html_e('No IPs blocked yet.', 'wordfence'); ?>
				</td>
			</tr>
		<?php endif ?>
	</tbody>
</table>

<p>
	<a class="button button-primary" href="<?php echo wfUtils::wpAdminURL('admin.php?page=WordfenceWAF#top#blocking') ?>"><?php esc_html_e('Update Blocked IPs', 'wordfence'); ?></a>
</p>

<?php wfHelperString::cycle(); ?>

<h2><?php printf(/* translators: Number of countries. */ __('Top %d Countries Blocked', 'wordfence'), $limit); ?></h2>

<table class="wf-striped-table wf-fixed-table">
	<thead>
		<tr>
			<th><?php esc_html_e('Country', 'wordfence'); ?></th>
			<th><?php esc_html_e('Total IPs Blocked', 'wordfence'); ?></th>
			<th><?php esc_html_e('Block Count', 'wordfence'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if ($top_countries_blocked):
			require(dirname(__FILE__) . '/../../lib/flags.php'); /** @var array $flags */
			foreach ($top_countries_blocked as $row): ?>
				<tr class="<?php echo wfHelperString::cycle('odd', 'even') ?>">
					<td>
						<?php if ($row->countryCode): ?>
							<span class="wf-flag <?php echo esc_attr('wf-flag-' . strtolower($row->countryCode)); ?>" title="<?php echo esc_attr($row->countryName); ?>"></span>
							&nbsp;
							<?php echo esc_html($row->countryName) ?>
						<?php else: ?>
							<?php esc_html_e('(Unknown)', 'wordfence'); ?>
						<?php endif ?>
					</td>
					<td><?php echo esc_html($row->totalIPs) ?></td>
					<td><?php echo (int) $row->totalBlockCount ?></td>
				</tr>
			<?php endforeach ?>
		<?php else: ?>
			<tr>
				<td colspan="3">
					<?php esc_html_e('No requests blocked yet.', 'wordfence'); ?>
				</td>
			</tr>
		<?php endif ?>
	</tbody>
</table>

<p>
	<a class="button button-primary" href="<?php echo wfUtils::wpAdminURL('admin.php?page=WordfenceWAF#top#blocking') ?>"><?php esc_html_e('Update Blocked Countries', 'wordfence'); ?></a>
</p>

<?php wfHelperString::cycle(); ?>

<h2><?php printf(/* translators: Number of failed logins. */ esc_html__('Top %d Failed Logins', 'wordfence'), $limit); ?></h2>

<table class="wf-striped-table wf-fixed-table">
	<thead>
		<tr>
			<th><?php esc_html_e('Username', 'wordfence'); ?></th>
			<th><?php esc_html_e('Login Attempts', 'wordfence'); ?></th>
			<th><?php esc_html_e('Existing User', 'wordfence'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ($top_failed_logins): ?>
			<?php foreach ($top_failed_logins as $row): ?>
				<tr class="<?php echo wfHelperString::cycle('odd', 'even') ?>">
					<td class="wf-split-word"><?php echo esc_html($row->username) ?></td>
					<td><?php echo esc_html($row->fail_count) ?></td>
					<td class="<?php echo sanitize_html_class($row->is_valid_user ? 'loginFailValidUsername' : 'loginFailInvalidUsername') ?>"><?php echo $row->is_valid_user ? esc_html__('Yes', 'wordfence') : esc_html__('No', 'wordfence') ?></td>
				</tr>
			<?php endforeach ?>
		<?php else: ?>
			<tr>
				<td colspan="3">
					<?php esc_html_e('No failed logins yet.', 'wordfence'); ?>
				</td>
			</tr>
		<?php endif ?>
	</tbody>
</table>

<p>
	<a class="button button-primary" href="<?php echo wfUtils::wpAdminURL('admin.php?page=WordfenceWAF&subpage=waf_options#waf-options-bruteforce') ?>"><?php esc_html_e('Update Login Security Options', 'wordfence'); ?></a>
</p>

<?php wfHelperString::cycle(); ?>

<?php /*?>
<h2>Recently Modified Files</h2>

<table class="activity-table recently-modified-files">
	<thead>
		<tr>
			<th>Modified</th>
			<th>File</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($recently_modified_files as $file_row):
			list($file, $mod_time) = $file_row;
			?>
			<tr class="<?php echo wfHelperString::cycle('odd', 'even') ?>">
				<td style="white-space: nowrap;"><?php echo $this->modTime($mod_time) ?></td>
				<td class="display-file-table-cell">
					<pre class="display-file"><?php echo esc_html($this->displayFile($file)) ?></pre>
				</td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>
<?php */ ?>


<?php wfHelperString::cycle(); ?>

<h2><?php esc_html_e('Updates Needed', 'wordfence'); ?></h2>

<?php
if (!is_array($updates_needed)) {
	$updates_needed = array('core' => array(), 'plugins' => array(), 'themes' => array());
}
?>
<?php if ($updates_needed['core']): ?>
	<h4><?php esc_html_e('Core', 'wordfence'); ?></h4>
	<ul>
		<li><?php printf(/* translators: WordPress version. */ esc_html__('A new version of WordPress (v%s) is available.', 'wordfence'), esc_html($updates_needed['core'])); ?></li>
	</ul>
<?php endif ?>
<?php if ($updates_needed['plugins']): ?>
	<h4><?php esc_html_e('Plugins', 'wordfence'); ?></h4>
	<ul>
		<?php
		foreach ($updates_needed['plugins'] as $plugin):
			$newVersion = ($plugin['newVersion'] == 'Unknown' ? $plugin['newVersion'] : "v{$plugin['newVersion']}");
		?>
			<li>
				<?php echo esc_html(sprintf(/* translators: Plugin name. */ __('A new version of the plugin "%s" is available.', 'wordfence'), "{$plugin['Name']} ({$newVersion})")); ?>
			</li>
		<?php endforeach ?>
	</ul>
<?php endif ?>
<?php if ($updates_needed['themes']): ?>
	<h4><?php esc_html_e('Themes', 'wordfence'); ?></h4>
	<ul>
		<?php
		foreach ($updates_needed['themes'] as $theme):
			$newVersion = ($theme['newVersion'] == 'Unknown' ? $theme['newVersion'] : "v{$theme['newVersion']}");
		?>
			<li>
				<?php echo esc_html(sprintf(/* translators: Theme name. */ __('A new version of the theme "%s" is available.', 'wordfence'), "{$theme['name']} ({$newVersion})")); ?>
			</li>
		<?php endforeach ?>
	</ul>
<?php endif ?>

<?php if ($updates_needed['core'] || $updates_needed['plugins'] || $updates_needed['themes']): ?>
	<p><a class="button button-primary" href="<?php echo esc_attr(wfUtils::wpAdminURL('update-core.php')) ?>"><?php esc_html_e('Update Now', 'wordfence'); ?></a></p>
<?php else: ?>
	<p><?php esc_html_e('No updates are available at this time.', 'wordfence'); ?></p>
<?php endif ?>
<?php if ((defined('WP_DEBUG') && WP_DEBUG) || wfConfig::get('debugOn')): ?>
	<p><?php printf(/* translators: Seconds with millisecond precision. */ esc_html__('Generated in %.4f seconds', 'wordfence'), $microseconds); ?></p>
<?php endif ?>