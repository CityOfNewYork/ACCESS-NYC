<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfUpgrade',
	'displayType' => __('Core Upgrade', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Current WordPress Version', 'wordfence') => '$data.currentVersion',
		__('New WordPress Version', 'wordfence') => '$data.newVersion',
		null,
		__('Details', 'wordfence') => '$longMsg',
		__('Vulnerability Status', 'wordfence') => array('$data.vulnerable', __('Update includes security-related fixes.', 'wordfence')),
		null,
		__('Vulnerability Information', 'wordfence') => '$data.vulnerabilityLink',
		__('Vulnerability Severity', 'wordfence') => '${data.cvssScore}/10.0 (${data.severityLabel})',
	),
))->render();