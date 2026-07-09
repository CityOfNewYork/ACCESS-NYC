<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfThemeUpgrade',
	'displayType' => __('Theme Upgrade', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Theme Name', 'wordfence') => '$data.name',
		__('Current Theme Version', 'wordfence') => '$data.version',
		__('New Theme Version', 'wordfence') => '$data.newVersion',
		null,
		__('Details', 'wordfence') => '$longMsg',
		__('Vulnerability Status', 'wordfence') => array('$data.vulnerable', __('Update includes security-related fixes.', 'wordfence')),
		null,
		__('Theme URL', 'wordfence') => '$data.URL',
		__('Vulnerability Information', 'wordfence') => '$data.vulnerabilityLink',
		__('Vulnerability Severity', 'wordfence') => '${data.cvssScore}/10.0 (${data.severityLabel})',
	),
))->render();