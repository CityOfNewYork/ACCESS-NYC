<?php
if (!defined('WORDFENCE_VERSION')) {
	exit;
}
/**
 * @var string $subpage
 */

$stepContent = array(
	1 => __('Testing initial communication with Wordfence Central.', 'wordfence'),
	2 => __('Passing public key to Wordfence Central.', 'wordfence'),
	3 => __('Testing public key authentication with Wordfence Central.', 'wordfence'),
	4 => __('Testing that Wordfence Central is able to communicate with this site.', 'wordfence'),
	5 => __('Retrieving access token using authorization grant.', 'wordfence'),
	6 => __('Redirecting back to Wordfence Central.', 'wordfence'),
);
$connected = wfCentral::isConnected();
$partialConnection = wfCentral::isPartialConnection();

?>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
	<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<?php
if (function_exists('network_admin_url') && is_multisite()) {
	$wordfenceURL = network_admin_url('admin.php?page=Wordfence');
}
else {
	$wordfenceURL = admin_url('admin.php?page=Wordfence');
}
?>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
				<?php
				echo wfView::create('common/section-title', array(
					'title'    => __('Wordfence Central', 'wordfence'),
					'showIcon' => true,
				))->render();
				?>
			</div>

			<?php if ($connected): ?>
				<div class="wf-col-xs-12 wf-central-connected">
					<div class="wf-flex-row wf-flex-grow-all">
						<div class="wf-flex-row-1 wf-block wf-active">
							<div class="wf-central-dashboard">
								<img class="wf-central-dashboard-logo" src="<?php echo wfUtils::getBaseURL() ?>images/wf-central-logo.svg" alt="Wordfence Central">
								<div class="wf-central-dashboard-copy">
									<p><strong><?php esc_html_e('Wordfence Central', 'wordfence') ?></strong></p>
									<p><?php esc_html_e('Wordfence Central allows you to manage Wordfence on multiple sites from one location. It makes security monitoring and configuring Wordfence easier.', 'wordfence') ?></p>
									<p class="wf-right-lg"><a href="https://www.wordfence.com/central" target="_blank" rel="noopener noreferrer"><strong><?php esc_html_e('Visit Wordfence Central', 'wordfence') ?></strong><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></p>
								</div>
							</div>
						</div>
						<div class="wf-flex-row-1 wf-block wf-active">
							<p><strong><?php esc_html_e('Wordfence Central Status', 'wordfence') ?></strong></p>
							<p><?php echo esc_html(sprintf(
									/* translators: 1. Email address. 2. Localized date. */
									__('Activated - connected by %1$s on %2$s', 'wordfence'), wfConfig::get('wordfenceCentralConnectEmail'), date_i18n('F j, Y', (int) wfConfig::get('wordfenceCentralConnectTime')))) ?></p>
							<p class="wf-right-lg"><a href="<?php echo esc_url($wordfenceURL); ?>"><strong><?php esc_html_e('Disconnect This Site', 'wordfence') ?></strong></a></p>
						</div>
					</div>
				</div>
			<?php elseif (isset($_GET['grant'])): ?>
				<div class="wf-col-xs-12">
					<div class="wf-block wf-active">
						<div class="wf-block-header">
							<div class="wf-block-header-content">
								<strong><?php esc_html_e('Wordfence Central Installation Process', 'wordfence') ?></strong>
							</div>
						</div>
						<div class="wf-block-content">
							<ul class="wf-block-list" id="wf-central-progress">
								<?php for ($i = 1; $i <= 6; $i++): ?>
									<li id="wf-central-progress-step<?php echo $i ?>" class="pending">
										<div class="wf-central-progress-icon">
											<div class="wf-step-pending"></div>
											<div class="wf-step-running">
												<?php
												echo wfView::create('common/indeterminate-progress', array(
													'size' => 50,
												))->render();
												?>
											</div>
											<div class="wf-step-complete-success"></div>
											<div class="wf-step-complete-warning"></div>
										</div>
										<div class="wf-central-progress-content">
											<p><?php echo esc_html($stepContent[$i]) ?></p>
										</div>
									</li>
								<?php endfor ?>
							</ul>
						</div>
					</div>
				</div>
			<?php elseif ($partialConnection): ?>
				<div class="wf-center-lg">
					<p><?php esc_html_e('It looks like you\'ve tried to connect this site to Wordfence Central, but the installation did not finish.', 'wordfence') ?></p>
					<p>
						<a href="<?php echo WORDFENCE_CENTRAL_URL_SEC ?>/sites/connection-issues?complete-setup=<?php echo esc_attr(wfConfig::get('wordfenceCentralSiteID')) ?>"
								class="wf-btn wf-btn-primary"
						><?php esc_html_e('Resume Installation', 'wordfence') ?></a>
						<a href="<?php echo esc_url($wordfenceURL); ?>" class="wf-btn wf-btn-warning"><?php esc_html_e('Disconnect Site', 'wordfence') ?></a>
					</p>
				</div>
			<?php else: ?>
				<div class="wf-center-lg">
					<p><?php esc_html_e('Wordfence Central allows you to manage Wordfence on multiple sites from one location. It makes security monitoring and configuring Wordfence easier.', 'wordfence') ?></p>
					<p><?php esc_html_e('To connect your site your site to Wordfence Central, use the link below:', 'wordfence') ?></p>
					<p class="wf-center">
						<a href="<?php echo WORDFENCE_CENTRAL_URL_SEC ?>?newsite=<?php echo esc_attr(home_url()) ?>" class="wf-btn wf-btn-primary"><?php esc_html_e('Connect Site', 'wordfence') ?></a>
					</p>
				</div>
			<?php endif ?>
		</div>
	</div>
</div>

<script>
	(function($) {
		var authGrant = '<?php echo esc_js(isset($_GET['grant']) ? $_GET['grant'] : '') ?>';
		var currentStep = <?php echo json_encode(wfConfig::getInt('wordfenceCentralCurrentStep', 1)) ?>;
		var connected = <?php echo json_encode($connected) ?>;
		var __ = window.wfi18n.__;

		function wfConnectError(error) {
			window.WFEventEmitter.emit('showModal', { name: 'simple-confirmation-modal', title: __('An error occurred'), message: error });
		}

		function wfCentralStepAjax(step, action, data, cb, cbErr, noLoading) {
			var el = $('#wf-central-progress-' + step);
			el.removeClass('pending')
			.addClass('running');

			WFAD.ajax(action, data, function(response) {
				if (response && response.success) {
					el.removeClass('running')
					.addClass('complete-success');
					cb && cb(response);
				} else if (response && response.err) {
					el.removeClass('running')
					.addClass('complete-warning');
				}
			}, function(response) {
				el.removeClass('running')
				.addClass('complete-warning');
				cbErr && cbErr(response);
			}, noLoading);
		}

		var WFCentralInstaller = {};
		window.WFCentralInstaller = WFCentralInstaller;

		// Step 1: Makes GET request to `/central/api/site/access-token` endpoint authenticated with the auth grant supplied by the user.
		// - Receives site GUID, public key, short lived JWT.

		WFCentralInstaller.step1 = function() {
			wfCentralStepAjax('step1', 'wordfence_wfcentral_step1', {
				'auth-grant': authGrant
			}, function(response) {
				$(window).trigger('step2', response);
			}, wfConnectError);
		};

		// Step 2: Makes PATCH request to `/central/api/wf/site/<guid>` endpoint passing in the new public key.
		// Uses JWT from auth grant endpoint as auth.
		WFCentralInstaller.step2 = function() {
			wfCentralStepAjax('step2', 'wordfence_wfcentral_step2', {}, function(response) {
				$(window).trigger('step3', response);
			}, wfConnectError);
		};

		$(window).on('step2', WFCentralInstaller.step2);

		// Step 3: Makes GET request to `/central/api/wf/site/<guid>` endpoint signed using Wordfence plugin private key.
		// - Expects 200 response with site data.
		WFCentralInstaller.step3 = function() {
			wfCentralStepAjax('step3', 'wordfence_wfcentral_step3', {}, function(response) {
				var callback = function() {
					$(window).trigger('step4')
				};
				var interval = setInterval(callback, 4000);
				$(window).on('step3-clearInterval', function() {
					clearInterval(interval);
				});
				callback();
			}, wfConnectError);
		};

		$(window).on('step3', WFCentralInstaller.step3);

		// Step 4: Poll for PUT request at `/wp-json/wp/v2/wordfence-auth-grant/` endpoint signed using Wordfence Central private key with short lived JWT.
		// - Expects verifiable signature of incoming request from Wordfence Central.
		// - Stores auth grant JWT.
		WFCentralInstaller.step4 = function() {
			wfCentralStepAjax('step4', 'wordfence_wfcentral_step4', {}, function(response) {
				if (response && response.success) {
					$(window).trigger('step3-clearInterval');
					$(window).trigger('step5');
				}
			}, wfConnectError);
		};

		$(window).on('step4', WFCentralInstaller.step4);

		// Step 5: Makes GET request to `/central/api/site/<guid>/access-token` endpoint signed using Wordfence plugin private key with auth grant JWT.
		// - Expects 200 response with access token.
		WFCentralInstaller.step5 = function() {
			wfCentralStepAjax('step5', 'wordfence_wfcentral_step5', {
				'auth-grant': authGrant
			}, function(response) {
				$(window).trigger('step6', response);
			}, wfConnectError);
		};

		$(window).on('step5', WFCentralInstaller.step5);

		// Step 6: Installation complete. Redirect user back to Wordfence Central with access token.
		WFCentralInstaller.step6 = function(response) {
			wfCentralStepAjax('step6', 'wordfence_wfcentral_step6', {}, function(response) {
				document.location.href = response['redirect-url'];
			}, wfConnectError);
		};

		$(window).on('step6', WFCentralInstaller.step6);

		var self = this;

		$(function() {
//			if (!authGrant) {
//				wfConnectError('Auth grant not found.');
//				return;
//			}

			if (!connected && authGrant) {
				for (var i = 0; i < currentStep; i++) {
					var el = $('#wf-central-progress-step' + i);
					el.removeClass('pending')
					.addClass('complete-success');
				}

				WFCentralInstaller['step' + currentStep]();
			}
		});

	})(jQuery);
</script>