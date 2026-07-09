<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'file',
	'displayType' => __('File', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Filename', 'wordfence') => '$data.realFile',
		__('File Type', 'wordfence') => '$data.ucType',
		__('File Type', 'wordfence') => '$data.wpconfig',
		__('File Type', 'wordfence') => array(array('!$data.ucType', '!$data.wpconfig'), 'Not a core, theme, or plugin file from wordpress.org'),
		__('Bad URL', 'wordfence') => '$data.badURL',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();