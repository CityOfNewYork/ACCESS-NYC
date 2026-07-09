<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfPluginVulnerable',
	'displayType' => __('Plugin Vulnerable', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Plugin Name', 'wordfence') => '$data.Name',
		__('Current Plugin Version', 'wordfence') => '$data.Version',
		null,
		__('Details', 'wordfence') => '$longMsg',
		null,
		__('Vulnerability Information', 'wordfence') => '$data.vulnerabilityLink',
		__('Vulnerability Severity', 'wordfence') => '${data.cvssScore}/10.0 (${data.severityLabel})',
	),
))->render();