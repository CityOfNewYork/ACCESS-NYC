<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wpscan_fullPathDiscl',
	'displayType' => __('Full Path Disclosure', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('URL', 'wordfence') => '$data.url',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();