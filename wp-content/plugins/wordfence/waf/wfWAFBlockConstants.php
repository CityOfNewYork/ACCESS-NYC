<?php

interface wfWAFBlockConstants {
	const WFWAF_BLOCK_UAREFIPRANGE = 'UA/Referrer/IP Range not allowed';
	const WFWAF_BLOCK_COUNTRY_BYPASS_REDIR = 'redirected to bypass URL';
	const WFWAF_BLOCK_INVALIDUSERNAME_REGEX = '/Used an invalid username \'([^\']+)\' to try to sign in/'; //These regex patterns exist because sscanf does not provide granular enough capture to correctly capture the formatted values
	const WFWAF_BLOCK_WFSN = 'Blocked by Wordfence Security Network';
	const WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD_REGEX = '/Exceeded the maximum number of tries to recover their password which is set at: (\d+). The last username or email they entered before getting locked out was: \'([^\']+)\'/';
	const WFWAF_BLOCK_LOGINSEC_FAILURES_REGEX = '/Exceeded the maximum number of login failures which is: (\d+). The last username they tried to sign in with was: \'([^\']+)\'/';
	const WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND = 'Exceeded the maximum number of page not found errors per minute for a crawler.';
	const WFWAF_BLOCK_THROTTLEGLOBAL = 'Exceeded the maximum global requests per minute for crawlers or humans.';
	const WFWAF_BLOCK_COUNTRY_REDIR_REGEX = '/blocked access via country blocking and redirected to URL \(([^\(]+)\)/';
	const WFWAF_BLOCK_LOGINSEC = 'Blocked by login security setting.';
	const WFWAF_BLOCK_LOGINSEC_FAILURES = 'Exceeded the maximum number of login failures which is: %1$s. The last username they tried to sign in with was: \'%2$s\'';
	const WFWAF_BLOCK_THROTTLECRAWLER = 'Exceeded the maximum number of requests per minute for crawlers.';
	const WFWAF_BLOCK_COUNTRY_REDIR = 'blocked access via country blocking and redirected to URL (%s)';
	const WFWAF_BLOCK_BADPOST = 'POST received with blank user-agent and referer';
	const WFWAF_BLOCK_MANUAL = 'Manual block by administrator';
	const WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD = 'Exceeded the maximum number of tries to recover their password which is set at: %1$s. The last username or email they entered before getting locked out was: \'%2$s\'';
	const WFWAF_BLOCK_COUNTRY = 'blocked access via country blocking';
	const WFWAF_BLOCK_BANNEDURL = 'Accessed a banned URL';
	const WFWAF_BLOCK_INVALIDUSERNAME = 'Used an invalid username \'%s\' to try to sign in';
	const WFWAF_BLOCK_THROTTLEHUMANNOTFOUND = 'Exceeded the maximum number of page not found errors per minute for humans.';
	const WFWAF_BLOCK_THROTTLEHUMAN = 'Exceeded the maximum number of page requests per minute for humans.';
}