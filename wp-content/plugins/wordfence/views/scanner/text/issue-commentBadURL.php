<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'commentBadURL',
	'displayType' => __('URL', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Author', 'wordfence') => '$data.author',
		__('Bad URL', 'wordfence') => '$data.badURL',
		__('Posted on', 'wordfence') => '$data.commentDate',
		null,
		__('Details', 'wordfence') => '$longMsg',
		null,
		__('Multisite Blog ID', 'wordfence') => array('$data.isMultisite', '$data.blog_id'),
		__('Multisite Blog Domain', 'wordfence') => array('$data.isMultisite', '$data.domain'),
		__('Multisite Blog Path', 'wordfence') => array('$data.isMultisite', '$data.path'),
	),
))->render();