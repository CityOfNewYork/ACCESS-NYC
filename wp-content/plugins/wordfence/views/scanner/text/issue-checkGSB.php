<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'checkGSB',
	'displayType' => __('URL', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Bad URL', 'wordfence') => '$data.badURL',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();