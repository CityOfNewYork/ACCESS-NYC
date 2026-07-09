(function($) {
	$(function() {
		$(document).on('click', '#wf-onboarding-delay', function() {
			wordfenceExt.setOption(
				'onboardingDelayedAt',
				$('#wf-onboarding-delay').data('timestamp'),
				function() {
					$('#wf-onboarding-banner').hide();
					if (window.WFEventEmitter) { window.WFEventEmitter.emit('showModal', { name: 'onboarding-delay-modal' }); } //May not display if outside a Wordfence page
				},
				function() {
					if (window.WFEventEmitter) { window.WFEventEmitter.emit('showModal', { name: 'onboarding-delay-error-modal' }); } //May not display if outside a Wordfence page
				}
			);
		});
	});
})(jQuery);
