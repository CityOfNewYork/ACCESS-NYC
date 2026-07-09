<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfPluginUpgrade',
	'displayType' => __('Plugin Upgrade', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Plugin Name', 'wordfence') => '$data.Name',
		__('Current Plugin Version', 'wordfence') => '$data.Version',
		__('New Plugin Version', 'wordfence') => '$data.newVersion',
		null,
		__('Details', 'wordfence') => '$longMsg',
		__('Vulnerability Status', 'wordfence') => array('$data.vulnerable', __('Update includes security-related fixes.', 'wordfence')),
		null,
		__('Plugin URL', 'wordfence') => '$data.PluginURI',
		__('Repository URL', 'wordfence') => '$data.wpURL',
		__('Vulnerability Information', 'wordfence') => '$data.vulnerabilityLink',
		__('Vulnerability Severity', 'wordfence') => '${data.cvssScore}/10.0 (${data.severityLabel})',
	),
))->render(); 