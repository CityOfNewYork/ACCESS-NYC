<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'postBadURL',
	'displayType' => __('URL', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Title', 'wordfence') => '$data.postTitle',
		__('Bad URL', 'wordfence') => '$data.badURL',
		__('Posted on', 'wordfence') => '$data.postDate',
		null,
		__('Details', 'wordfence') => '$longMsg',
		null,
		__('Multisite Blog ID', 'wordfence') => array('$data.isMultisite', '$data.blog_id'),
		__('Multisite Blog Domain', 'wordfence') => array('$data.isMultisite', '$data.domain'),
		__('Multisite Blog Path', 'wordfence') => array('$data.isMultisite', '$data.path'),
	),
))->render();