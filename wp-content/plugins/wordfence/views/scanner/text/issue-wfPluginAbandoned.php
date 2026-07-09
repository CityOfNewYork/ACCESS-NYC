<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfPluginAbandoned',
	'displayType' => __('Plugin Abandoned', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Plugin Name', 'wordfence') => '$data.name',
		__('Current Plugin Version', 'wordfence') => '$data.version',
		__('Last Updated', 'wordfence') => '$data.dateUpdated',
		null,
		__('Details', 'wordfence') => '$longMsg',
		__('Vulnerability Status', 'wordfence') => array('$data.vulnerable', __('Plugin has unpatched security issues.', 'wordfence')),
		null,
		__('Plugin URL', 'wordfence') => '$data.homepage',
		__('Repository URL', 'wordfence') => '$data.wpURL',
		__('Vulnerability Information', 'wordfence') => '$data.vulnerabilityLink',
		__('Vulnerability Severity', 'wordfence') => '${data.cvssScore}/10.0 (${data.severityLabel})',
	),
))->render();