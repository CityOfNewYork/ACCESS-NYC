<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<?php $scanOptions = $scanController->scanOptions(); ?>
<p><?php echo esc_html(sprintf(
	/* translators: URL to the site's homepage. */
		__('This email was sent from your website "%s" by the Wordfence plugin.', 'wordfence'), get_bloginfo('name', 'raw'))); ?></p>

<p><?php

	if (count($previousIssues) > 0) {
		printf(
			/* translators: 1. URL to the site's homepage. 2. Number of scan results. */
			_n('Wordfence found the following new issues on "%1$s" (%2$d existing issue was also found again).',
			'Wordfence found the following new issues on "%1$s" (%2$d existing issues were also found again).',
			count($previousIssues),
			'wordfence'),
			esc_html(get_bloginfo('name', 'raw')),
			count($previousIssues)
		);
	} else {
		echo esc_html(sprintf(
		/* translators: 1. URL to the site's homepage. */
			__('Wordfence found the following new issues on "%1$s".', 'wordfence'),
			get_bloginfo('name', 'raw')
		));
	}


	?></p>

<p><?php echo esc_html(sprintf(
		/* translators: Localized date. */
		__('Alert generated at %s', 'wordfence'), wfUtils::localHumanDate())); ?></p>

<br>

<p><?php echo esc_html(sprintf(
	/* translators: URL to WordPress admin panel. */
		__('See the details of these scan results on your site at: %s', 'wordfence'), wfUtils::wpAdminURL('admin.php?page=WordfenceScan'))); ?></p>

<?php if ($scanOptions['scansEnabled_highSense']): ?>
	<div style="margin: 12px 0;padding: 8px; background-color: #ffffe0; border: 1px solid #ffd975; border-width: 1px 1px 1px 10px;">
		<em><?php esc_html_e('HIGH SENSITIVITY scanning is enabled, it may produce false positives', 'wordfence'); ?></em>
	</div>
<?php endif ?>

<?php if ($timeLimitReached): ?>
	<div style="margin: 12px 0;padding: 8px; background-color: #ffffe0; border: 1px solid #ffd975; border-width: 1px 1px 1px 10px;">
		<em><?php echo wp_kses(sprintf(
			/* translators: 1. URL to WordPress admin panel. 2. URL to WordPress admin panel. 3. URL to Wordfence support page. 4. URL to Wordfence support page. */
				__('The scan was terminated early because it reached the time limit for scans. If you would like to allow your scans to run longer, you can customize the limit on the options page: <a href="%1$s">%2$s</a> or read more about scan options to improve scan speed here: <a href="%3$s">%4$s</a>', 'wordfence'), esc_attr(wfUtils::wpAdminURL('admin.php?page=WordfenceScan&subpage=scan_options#wf-scanner-options-performance')), esc_attr(wfUtils::wpAdminURL('admin.php?page=WordfenceScan&subpage=scan_options')), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_TIME_LIMIT), esc_html(wfSupportController::supportURL(wfSupportController::ITEM_SCAN_TIME_LIMIT))), array('a'=>array('href'=>array()))); ?></em>
	</div>
<?php endif ?>

<?php
$severitySections = array(
	wfIssues::SEVERITY_CRITICAL => __('Critical Problems:', 'wordfence'),
	wfIssues::SEVERITY_HIGH => __('High Severity Problems:', 'wordfence'),
	wfIssues::SEVERITY_MEDIUM => __('Medium Severity Problems:', 'wordfence'),
	wfIssues::SEVERITY_LOW => __('Low Severity Problems:', 'wordfence'),
);
?>

<?php
foreach ($severitySections as $severityLevel => $severityLabel):
	if ($severityLevel < $level) {
		continue;
	}
	$hasIssuesAtSeverity = false;

	foreach($issues as $i){ if($i['severity'] == $severityLevel){ ?>
<?php if (!$hasIssuesAtSeverity): $hasIssuesAtSeverity = true; ?>
<p><?php echo esc_html($severityLabel) ?></p>
<?php endif ?>
<p>* <?php echo htmlspecialchars($i['shortMsg']) ?></p>
<?php
	if ((isset($i['tmplData']['wpRemoved']) && $i['tmplData']['wpRemoved']) || (isset($i['tmplData']['abandoned']) && $i['tmplData']['abandoned'])) {
		if (isset($i['tmplData']['vulnerable']) && $i['tmplData']['vulnerable']) {
			echo '<p><strong>' . esc_html__('Plugin contains an unpatched security vulnerability.', 'wordfence') . '</strong>';
			if (isset($i['tmplData']['cvssScore'])) {
				echo ' <br>' . esc_html__('Vulnerability Severity', 'wordfence') . ': ' . number_format($i['tmplData']['cvssScore'], 1) . '/10.0 (<span style="color:' . wfUpdateCheck::cvssScoreSeverityHexColor($i['tmplData']['cvssScore']) . '">' . wfUpdateCheck::cvssScoreSeverityLabel($i['tmplData']['cvssScore']) . '</span>)';
			}
			if (isset($i['tmplData']['vulnerabilityLink'])) {
				echo ' <br><a href="' . $i['tmplData']['vulnerabilityLink'] . '" target="_blank" rel="nofollow noreferrer noopener">' . esc_html__('Vulnerability Information', 'wordfence') . '</a>';
			}
			echo '</p>';
		}
	}
	if ($i['type'] == 'coreUnknown') {
		echo '<p>' . esc_html__('The core files scan has not run because this version is not currently indexed by Wordfence. New WordPress versions may take up to a day to be indexed.', 'wordfence') . '</p>';
	}
	else if ($i['type'] == 'wafStatus') {
		echo '<p>' . esc_html__('Firewall issues may be caused by file permission changes or other technical problems.', 'wordfence') . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_WAF_DISABLED) . '" target="_blank" rel="nofollow noreferrer noopener">' . esc_html__('More Details and Instructions', 'wordfence') . '</a></p>';
    }
	else if ($i['type'] == 'skippedPaths') {
		echo '<p>' . esc_html__('Scanning additional paths is optional and is not always necessary.', 'wordfence') . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_SKIPPED_PATHS) . '" target="_blank" rel="nofollow noreferrer noopener">' . esc_html__('Learn More', 'wordfence') . '</a></p>';
	}

	$showWPParagraph = !empty($i['tmplData']['vulnerable']) || isset($i['tmplData']['wpURL']);
	if ($showWPParagraph) {
		echo '<p>';
	}
	if (!empty($i['tmplData']['vulnerable'])) {
		if (isset($i['tmplData']['updateAvailable']) && $i['tmplData']['updateAvailable'] !== false)
			echo '<strong>' . esc_html__('Update includes security-related fixes.', 'wordfence') . '</strong>';
		if (isset($i['tmplData']['cvssScore'])) {
			echo ' <br>' . esc_html__('Vulnerability Severity', 'wordfence') . ': ' . number_format($i['tmplData']['cvssScore'], 1) . '/10.0 (<span style="color:' . wfUpdateCheck::cvssScoreSeverityHexColor($i['tmplData']['cvssScore']) . '">' . wfUpdateCheck::cvssScoreSeverityLabel($i['tmplData']['cvssScore']) . '</span>)';
		}
		if (isset($i['tmplData']['vulnerabilityLink'])) {
			echo ' <a href="' . $i['tmplData']['vulnerabilityLink'] . '" target="_blank" rel="nofollow noreferrer noopener">' . esc_html__('Vulnerability Information', 'wordfence') . '</a>';
		}
	}
	if (isset($i['tmplData']['wpURL'])) {
		if(!empty($i['tmplData']['vulnerable']))
			echo '<br>';
		echo $i['tmplData']['wpURL'] . '/#developers';
	}
	if ($showWPParagraph) {
		echo '</p>';
	}
	?>

<?php
if (!empty($i['tmplData']['badURL'])):
	$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
	$url = set_url_scheme($api->getTextImageURL($i['tmplData']['badURL']), 'https');
?>
<p><img src="<?php echo esc_url($url) ?>" alt="<?php esc_html_e('The malicious URL matched', 'wordfence') ?>" /></p>
<?php endif ?>

<?php } } ?>
<?php endforeach; ?>

<?php
$sentences = array();
if (count($previousIssues)) {
	$sentences[] = sprintf(/* translators: Number of scan results */_n('%d existing issue was found again and is not shown.', '%d existing issues were found again and are not shown.', count($previousIssues), 'wordfence'), count($previousIssues));
}
if ($issuesNotShown > 0) {
	$sentences[] = sprintf(/* translators: Number of scan results */ _n('%d issue was omitted from this email due to length limits.', '%d issues were omitted from this email due to length limits.', $issuesNotShown, 'wordfence'), $issuesNotShown);
	$sentences[] = esc_html__('View every issue:', 'wordfence') . sprintf(' <a href="%s">%s</a>', esc_attr(wfUtils::wpAdminURL('admin.php?page=WordfenceScan')), esc_html(wfUtils::wpAdminURL('admin.php?page=WordfenceScan')));
}

if (count($sentences)) {
	printf('<p>%s</p>', implode(' ', $sentences));
}
?>

<?php if(! $isPaid){ ?>
	<p><?php esc_html_e('NOTE: You are using the free version of Wordfence. Upgrade today:', 'wordfence'); ?></p>
	
	<ul>
		<li><?php esc_html_e('Receive real-time Firewall and Scan engine rule updates for protection as threats emerge', 'wordfence'); ?></li>
		<li><?php esc_html_e('Real-time IP Blocklist blocks the most malicious IPs from accessing your site', 'wordfence'); ?></li>
		<li><?php esc_html_e('Country blocking', 'wordfence'); ?></li>
		<li><?php esc_html_e('IP reputation monitoring', 'wordfence'); ?></li>
		<li><?php esc_html_e('Schedule scans to run more frequently and at optimal times', 'wordfence'); ?></li>
		<li><?php esc_html_e('Access to Premium Support', 'wordfence'); ?></li>
		<li><?php esc_html_e('Discounts for multi-year and multi-license purchases', 'wordfence'); ?></li>
	</ul>

	<p><?php esc_html_e('Click here to upgrade to Wordfence Premium:', 'wordfence'); ?><br><a href="https://www.wordfence.com/zz2/wordfence-signup/">https://www.wordfence.com/zz2/wordfence-signup/</a></p>
<?php } ?>

<p><!-- ##UNSUBSCRIBE## --></p>