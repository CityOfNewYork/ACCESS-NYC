<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'database',
	'displayType' => __('Option', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Option Name', 'wordfence') => '$data.option_name',
		__('Bad URL', 'wordfence') => '$data.badURL',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();