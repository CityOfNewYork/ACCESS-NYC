<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wpscan_directoryList',
	'displayType' => __('Directory Listing Enabled', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('URL', 'wordfence') => '$data.url',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();