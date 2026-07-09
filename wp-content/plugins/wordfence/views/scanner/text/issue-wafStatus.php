<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wafStatus',
	'displayType' => __('WAF Status', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Status', 'wordfence') => '$data.wafStatusDisplay',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();