<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'optionBadURL',
	'displayType' => __('URL', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Option Name', 'wordfence') => '$data.optionKey',
		__('Bad URL', 'wordfence') => '$data.badURL',
		null,
		__('Details', 'wordfence') => '$longMsg',
		null,
		__('Multisite Blog ID', 'wordfence') => array('$data.isMultisite', '$data.blog_id'),
		__('Multisite Blog Domain', 'wordfence') => array('$data.isMultisite', '$data.domain'),
		__('Multisite Blog Path', 'wordfence') => array('$data.isMultisite', '$data.path'),
	),
))->render();