<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'diskSpace',
	'displayType' => __('Disk Space', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Space Remaining', 'wordfence') => '$data.spaceLeft',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();