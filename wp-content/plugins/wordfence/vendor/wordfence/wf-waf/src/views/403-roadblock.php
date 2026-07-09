<?php
if (!defined('WFWAF_VIEW_RENDERING')) { exit; }

/** @var wfWAF $waf */
/** @var wfWAFView $this */

/*
 * IMPORTANT:
 * 
 * If the form variables below change name or format, admin.ajaxWatcher.js in the main plugin also needs changed. It
 * processes these to generate its whitelist button.
 */

$method = wfWAFUtils::strtolower($waf->getRequest()->getMethod());
$urlParamsToWhitelist = array();
foreach ($waf->getFailedRules() as $paramKey => $categories) {
	foreach ($categories as $category => $failedRules) {
		foreach ($failedRules as $failedRule) {
			/**
			 * @var wfWAFRule $rule
			 * @var wfWAFRuleComparisonFailure $failedComparison
			 */
			$rule = $failedRule['rule'];
			$failedComparison = $failedRule['failedComparison'];

			$urlParamsToWhitelist[] = array(
				'path'     => $waf->getRequest()->getPath(),
				'paramKey' => $failedComparison->getParamKey(),
				'ruleID'   => $rule->getRuleID(),
			);
		}
	}
}


?>
<!DOCTYPE html>
<html>
<head>
	<title><?php wfWAFI18n::esc_html_e('403 Forbidden') ?></title>
	<style>
		html {
			font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
			font-size: 0.875rem;
			line-height: 1.42857143;
			color: #333;
			background-color: #fff;
			padding: 0;
			margin: 0;
		}

		body {
			padding: 0;
			margin: 0;
		}

		a {
			color:#00709e;
		}

		h1, h2, h3, h4, h5, h6 {
			font-weight: 200;
			line-height: 1.1;
		}

		h1, .h1 { font-size: 3rem; }
		h2, .h2 { font-size: 2.5rem; }
		h3, .h3 { font-size: 1.5rem; }
		h4, .h4 { font-size: 1rem; }
		h5, .h5 { font-size: 0.875rem; }
		h6, .h6 { font-size: 0.75rem; }

		h1, h2, h3 {
			margin-top: 20px;
			margin-bottom: 10px;
		}
		h4, h5, h6 {
			margin-top: 10px;
			margin-bottom: 10px;
		}

		.wf-btn {
			display: inline-block;
			margin-bottom: 0;
			font-weight: normal;
			text-align: center;
			vertical-align: middle;
			touch-action: manipulation;
			cursor: pointer;
			background-image: none;
			border: 1px solid transparent;
			white-space: nowrap;
			text-transform: uppercase;
			padding: .4rem 1rem;
			font-size: .875rem;
			line-height: 1.3125rem;
			border-radius: 4px;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none
		}

		@media (min-width: 768px) {
			.wf-btn {
				padding: .5rem 1.25rem;
				font-size: .875rem;
				line-height: 1.3125rem;
				border-radius: 4px
			}
		}

		.wf-btn:focus,
		.wf-btn.wf-focus,
		.wf-btn:active:focus,
		.wf-btn:active.wf-focus,
		.wf-btn.wf-active:focus,
		.wf-btn.wf-active.wf-focus {
			outline: 5px auto -webkit-focus-ring-color;
			outline-offset: -2px
		}

		.wf-btn:hover,
		.wf-btn:focus,
		.wf-btn.wf-focus {
			color: #00709e;
			text-decoration: none
		}

		.wf-btn:active,
		.wf-btn.wf-active {
			outline: 0;
			background-image: none;
			-webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
			box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125)
		}

		.wf-btn.wf-disabled,
		.wf-btn[disabled],
		.wf-btn[readonly],
		fieldset[disabled] .wf-btn {
			cursor: not-allowed;
			-webkit-box-shadow: none;
			box-shadow: none
		}

		a.wf-btn {
			text-decoration: none
		}

		a.wf-btn.wf-disabled,
		fieldset[disabled] a.wf-btn {
			cursor: not-allowed;
			pointer-events: none
		}

		.wf-btn-default {
			color: #00709e;
			background-color: #fff;
			border-color: #00709e
		}

		.wf-btn-default:focus,
		.wf-btn-default.focus {
			color: #00709e;
			background-color: #e6e6e6;
			border-color: #00161f
		}

		.wf-btn-default:hover {
			color: #00709e;
			background-color: #e6e6e6;
			border-color: #004561
		}

		.wf-btn-default:active,
		.wf-btn-default.active {
			color: #00709e;
			background-color: #e6e6e6;
			border-color: #004561
		}

		.wf-btn-default:active:hover,
		.wf-btn-default:active:focus,
		.wf-btn-default:active.focus,
		.wf-btn-default.active:hover,
		.wf-btn-default.active:focus,
		.wf-btn-default.active.focus {
			color: #00709e;
			background-color: #d4d4d4;
			border-color: #00161f
		}

		.wf-btn-default:active,
		.wf-btn-default.wf-active {
			background-image: none
		}

		.wf-btn-default.wf-disabled,
		.wf-btn-default[disabled],
		.wf-btn-default[readonly],
		fieldset[disabled] .wf-btn-default {
			color: #777;
			background-color: #fff;
			border-color: #e2e2e2;
			cursor: not-allowed
		}

		.wf-btn-default.wf-disabled:hover,
		.wf-btn-default.wf-disabled:focus,
		.wf-btn-default.wf-disabled.wf-focus,
		.wf-btn-default[disabled]:hover,
		.wf-btn-default[disabled]:focus,
		.wf-btn-default[disabled].wf-focus,
		.wf-btn-default[readonly]:hover,
		.wf-btn-default[readonly]:focus,
		.wf-btn-default[readonly].wf-focus,
		fieldset[disabled] .wf-btn-default:hover,
		fieldset[disabled] .wf-btn-default:focus,
		fieldset[disabled] .wf-btn-default.wf-focus {
			background-color: #fff;
			border-color: #00709e
		}

		input[type="text"], input.wf-input-text {
			text-align: left;
			max-width: 200px;
			height: 30px;
			border-radius: 0;
			border: 0;
			background-color: #ffffff;
			box-shadow: 0px 0px 0px 1px rgba(215,215,215,0.65);
			padding: 0.25rem;
		}

		hr {
			margin-top: 1rem;
			margin-bottom: 1rem;
			border: 0;
			border-top: 4px solid #eee
		}

		p {
			font-size: 1.4rem;
			font-weight: 300;
		}

		p.medium, div.medium p {
			font-size: 1.1rem;
		}

		p.small, div.small p {
			font-size: 1rem;
		}

		.container {
			max-width: 900px;
			padding: 0 1rem;
			margin: 0 auto;
		}

		.top-accent {
			height: 25px;
			background-color: #00709e;
		}

		.block-data {
			width: 100%;
			border-top: 6px solid #00709e;
		}

		.block-data tr:nth-child(odd) th, .block-data tr:nth-child(odd) td {
			background-color: #eeeeee;
		}

		.block-data th, .block-data td {
			text-align: left;
			padding: 1rem;
			font-size: 1.1rem;
		}

		.block-data th.reason, .block-data td.reason {
			color: #930000;
		}

		.block-data th {
			font-weight: 300;
		}

		.block-data td {
			font-weight: 500;
		}

		.about {
			margin-top: 2rem;
			display: flex;
			flex-direction: row;
			align-items: stretch;
		}

		.about .badge {
			flex-basis: 116px;
			flex-grow: 0;
			flex-shrink: 0;
			display: flex;
			align-items: center;
			justify-content: flex-start;
		}

		.about svg {
			width: 100px;
			height: 100px;

		}

		.about-text {
			background-color: #00709e;
			color: #ffffff;
			padding: 1rem;
		}

		.about-text .h4 {
			font-weight: 500;
			margin-top: 0;
			margin-bottom: 0.25rem;
			font-size: 0.875rem;
		}

		.about-text p {
			font-size: 0.875rem;
			font-weight: 200;
			margin-top: 0.3rem;
			margin-bottom: 0.3rem;
		}

		.about-text p:first-of-type {
			margin-top: 0;
		}

		.about-text p:last-of-type {
			margin-bottom: 0;
		}

		.st0{fill:#00709e;}
		.st1{fill:#FFFFFF;}

		.generated {
			color: #999999;
			margin-top: 2rem;
		}
	</style>
</head>
<body>
<?php if (!empty($errorNonce)) { echo '<!-- WFWAF NONCE: ' . htmlspecialchars($errorNonce) . ' -->'; } ?>
<div class="top-accent"></div>
<div class="container">
	<h1><?php wfWAFI18n::esc_html_e('A potentially unsafe operation has been detected in your request to this site') ?></h1>
	<p><?php wfWAFI18n::esc_html_e('Your access to this service has been limited. (HTTP response code 403)') ?></p>
	<p><?php wfWAFI18n::esc_html_e('If you think you have been blocked in error, contact the owner of this site for assistance.') ?></p>
	<?php if (!empty($customText)): ?>
		<hr>
		<div class="medium"><?php echo $customText; ?></div>
	<?php endif; ?>
	<?php if ($urlParamsToWhitelist): ?>
		<hr>
		<p><?php wfWAFI18n::esc_html_e('If you are an administrator and you are certain this is a false positive, you can automatically allowlist this request and repeat the same action.') ?></p>

		<form id="whitelist-form" action="<?php echo htmlentities($waf->getRequest()->getPath(), ENT_QUOTES, 'utf-8') ?>" method="post">
			<input type="hidden" name="wfwaf-false-positive-params" value="<?php echo htmlentities(wfWAFUtils::json_encode($urlParamsToWhitelist), ENT_QUOTES, 'utf-8') ?>">
			<input type="hidden" name="wfwaf-false-positive-nonce" value="<?php echo htmlentities($waf->getAuthCookieValue('nonce', ''), ENT_QUOTES, 'utf-8') ?>">

			<div id="whitelist-actions">
				<p><label><input id="verified-false-positive-checkbox" type="checkbox" name="wfwaf-false-positive-verified" value="1"> <em><?php wfWAFI18n::esc_html_e('I am certain this is a false positive.') ?></em></label></p>

				<p><button id="whitelist-button" type="submit"><?php wfWAFI18n::esc_html_e('Allowlist This Action') ?></button></p>
			</div>

			<p id="success" style="color: #35b13a; font-weight: bold; display: none"><em><?php wfWAFI18n::esc_html_e('All set! You can refresh the page to try this action again.') ?></em></p>
			<p id="error" style="color: #dd422c; font-weight: bold; display: none"><em><?php wfWAFI18n::esc_html_e('Something went wrong allowlisting this request. You can try setting the Firewall Status to Learning Mode under Web Application Firewall in the Wordfence menu, and retry this same action.') ?></em></p>
		</form>
		<script>
			var whitelistButton = document.getElementById('whitelist-button');
			var verified = document.getElementById('verified-false-positive-checkbox');
			verified.checked = false;
			verified.onclick = function() {
				whitelistButton.disabled = !this.checked;
			};
			verified.onclick();

			document.getElementById('whitelist-form').onsubmit = function(evt) {
				evt.preventDefault();
				var request = new XMLHttpRequest();
				request.addEventListener("load", function() {
					if (this.status === 200 && this.responseText.indexOf('Successfully allowlisted') > -1) {
						document.getElementById('whitelist-actions').style.display = 'none';
						document.getElementById('success').style.display = 'block';
					} else {
						document.getElementById('error').style.display = 'block';
					}
				});
				request.open("POST", this.action, true);
				request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				var inputs = this.querySelectorAll('input[name]');
				var data = '';
				for (var i = 0; i < inputs.length; i++) {
					data += encodeURIComponent(inputs[i].name) + '=' + encodeURIComponent(inputs[i].value) + '&';
				}
				request.send(data);
				return false;
			};


		</script>
		<hr>
	<?php endif ?>

	<h2 class="h3"><?php wfWAFI18n::esc_html_e('Block Technical Data') ?></h2>
	<table border="0" cellspacing="0" cellpadding="0" class="block-data">
		<tr>
			<th class="reason"><?php wfWAFI18n::esc_html_e('Block Reason:') ?></th>
			<td class="reason"><?php wfWAFI18n::esc_html_e('A potentially unsafe operation has been detected in your request to this site') ?></td>
		</tr>
		<tr>
			<th class="time"><?php wfWAFI18n::esc_html_e('Time:') ?></th>
			<td class="time"><?php echo htmlspecialchars(gmdate('D, j M Y G:i:s T', wfWAFUtils::normalizedTime())); ?></td>
		</tr>
	</table>

	<div class="about">
		<div class="badge">
			<?php
			$contents = file_get_contents(dirname(__FILE__) . '/../../../../../images/wf-error-badge.svg');
			$contents = preg_replace('/^<\?xml.+?\?>\s*/i', '', $contents);
			$contents = preg_replace('/^<!DOCTYPE.+?>\s*/i', '', $contents);
			$contents = preg_replace('/<svg\s+xmlns="[^"]*"/i', '<svg', $contents);
			echo $contents;
			?>
		</div>
		<div class="about-text">
			<h3 class="h4"><?php wfWAFI18n::esc_html_e('About Wordfence') ?></h3>
			<p><?php wfWAFI18n::esc_html_e('Wordfence is a security plugin installed on over 5 million WordPress sites. The owner of this site is using Wordfence to manage access to their site.') ?></p>
			<p><?php wfWAFI18n::esc_html_e('You can also read the documentation to learn about Wordfence\'s blocking tools, or visit wordfence.com to learn more about Wordfence.') ?></p>
		</div>
	</div>

	<p class="documentation small"><?php wfWAFI18n::esc_html_e('Click here to learn more: '); ?><a href="https://www.wordfence.com/help/?query=locked-out" target="_blank" rel="noopener noreferrer"><?php wfWAFI18n::esc_html_e('Documentation') ?></a></p>
	<p class="generated small"><em><?php printf(/* translators: Timestamp */ wfWAFI18n::esc_html__('Generated by Wordfence at %s.'), gmdate('D, j M Y G:i:s T', wfWAFUtils::normalizedTime())) ?><br><?php /* translators: Timestamp */ wfWAFI18n::esc_html_e('Your computer\'s time: ') ?><script type="application/javascript">document.write(new Date().toUTCString());</script>.</em></p>
</div>
</body>
</html>