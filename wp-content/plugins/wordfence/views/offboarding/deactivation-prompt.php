<?php
if (!defined('WORDFENCE_VERSION')) exit;

$selectedOptionKey = $deactivationOption === null ? null : $deactivationOption->getKey();
?>
<div style="display: none;">
	<div class="wf-modal wf-deactivate-modal" id="wf-offboarding-delete-prompt-template">
		<div class="wf-modal-header">
			<div class="wf-modal-header-content">
				<div class="wf-modal-title"><strong><?php esc_html_e('Deactivate Wordfence', 'wordfence') ?></strong></div>
			</div>
		</div>
		<div class="wf-modal-content">
			<p><?php esc_html_e('You are about to deactivate Wordfence. Would you like to delete its data or keep it in place?', 'wordfence') ?></p>
			<div class="wf-radio-group">
				<?php foreach (wfDeactivationOption::getAll() as $option): ?>
					<?php
						$inputId = 'wf-deactivate-option-' . $option->getKey();
					?>
					<div class="wf-radio-option">
						<input type="radio" data-name="wf-deactivate-option" value="<?php echo esc_attr($option->getKey()) ?>" class="wf-templated" data-id="<?php echo esc_attr($inputId) ?>"<?php if ($option->getKey() === $selectedOptionKey): ?> checked<?php endif ?>>
						<label for="<?php echo esc_attr($inputId) ?>"><?php echo esc_html($option->getLabel()) ?></label>
					</div>
				<?php endforeach ?>
			</div>
		</div>
		<div class="wf-modal-footer">
			<button data-id="wf-deactivate-delete" class="wf-btn wf-btn-danger wf-deactivate-confirm wf-templated"><?php esc_html_e('Deactivate and Delete Data', 'wordfence') ?></button>
			<button data-id="wf-deactivate-retain" class="wf-btn wf-btn-primary wf-deactivate-confirm wf-templated"><?php esc_html_e('Deactivate', 'wordfence') ?></button>
			<button data-id="wf-deactivate-cancel" class="wf-btn wf-btn-default wf-templated"><?php esc_html_e('Cancel', 'wordfence') ?></button>
		</div>
	</div>
	<?php if ($wafOptimized): ?>
		<div class="wf-modal wf-deactivate-modal" id="wf-offboarding-waf-optimized-template">
			<div class="wf-modal-header">
				<div class="wf-modal-header-content">
					<div class="wf-modal-title"><strong><?php esc_html_e('Extended Protection Still Enabled', 'wordfence') ?></strong></div>
				</div>
			</div>
			<div class="wf-modal-content">
				<p><?php esc_html_e('The Wordfence firewall is still optimized. You should remove the firewall\'s extended protection before deleting to avoid PHP errors if some firewall files cannot be removed, or if PHP\'s "auto_prepend_file" setting is cached.', 'wordfence') ?></p>
				<p><a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_REMOVE_OPTIMIZATION) ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'wordfence') ?></a></p>
			</div>
			<div class="wf-modal-footer">
				<a class="wf-btn wf-btn-danger" href="<?php echo esc_attr(network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options&wf_deactivate=true#removeAutoPrepend')) ?>"><?php esc_html_e('Remove Extended Protection', 'wordfence') ?></a>
				<button onclick="wordfenceExt.currentDeactivationDialog.dialog('close'); return false;" class="wf-btn wf-btn-default"><?php esc_html_e('Cancel', 'wordfence') ?></a>
			</div>
		</div>
	<?php endif ?>
	<div class="wf-modal wf-deactivate-modal" id="wf-offboarding-delete-confirm-template">
		<div class="wf-modal-header">
			<div class="wf-modal-header-content">
				<div class="wf-modal-title"><strong><?php esc_html_e('Delete Wordfence Data?', 'wordfence') ?></strong></div>
			</div>
		</div>
		<div class="wf-modal-content"><span class="message"><?php esc_html_e('Are you sure you want to delete the selected Wordfence data? If you reactivate Wordfence later, deleted settings and history cannot be recovered.', 'wordfence') ?></span></div>
		<div class="wf-modal-footer">
			<button class="wf-btn wf-btn-danger wf-deactivate-delete-confirm"><?php esc_html_e('Deactivate and Delete Data', 'wordfence') ?></button>
			<button class="wf-btn wf-btn-default wf-deactivate-delete-cancel"><?php esc_html_e('Cancel', 'wordfence') ?></a>
		</div>
	</div>
	<div class="wf-modal wf-deactivate-modal" id="wf-offboarding-delete-error-template">
		<div class="wf-modal-header">
			<div class="wf-modal-header-content">
				<div class="wf-modal-title"><strong><?php esc_html_e('Error', 'wordfence') ?></strong></div>
			</div>
		</div>
		<div class="wf-modal-content"><span class="message"><?php esc_html_e('An unexpected error occurred while attempting to configure Wordfence to delete its data on deactivation.', 'wordfence') ?></span></div>
		<div class="wf-modal-footer wf-modal-footer-center">
			<button onclick="wordfenceExt.currentDeactivationDialog.dialog('close'); return false;" class="wf-btn wf-btn-primary"><?php esc_html_e('Close', 'wordfence') ?></a>
		</div>
	</div>
</div>
<script type="text/javascript">
	(function($) {

		var wafOptimized = <?php echo json_encode($wafOptimized); ?>;

		var stateController = new (function() {

			var processing = false;

			function applyState() {
				var modal = $("#wf-deactivation-prompt .wf-deactivate-modal");
				buttons = modal.find('button');
				options = modal.find('input[type=radio]');
				[buttons, options].forEach(function(element) {
					element.prop('disabled', processing).toggleClass('disabled', processing);
				});
			}

			function setProcessing(state) {
				processing = state;
				applyState();
				return true;
			}

			this.startProcessing = function() {
				if (processing)
					return false;
				return setProcessing(true);
			};

			this.endProcessing = function() {
				if (!processing)
					return false;
				return setProcessing(false);
			};

			this.refresh = function() {
				applyState();
			}

		})();

		function updateButtons() {
			var retain = $('input[name=wf-deactivate-option]:checked').val() === 'retain';
			$('#wf-deactivate-retain').toggle(retain);
			$('#wf-deactivate-delete').toggle(!retain);
		}

		function replaceTemplatedAttribute(element, key) {
			var value = element.data(key);
			if (typeof value !== 'undefined')
				element.attr(key, value);
		}

		function showOffboardingModal(id) {
			var content = $("#wf-offboarding-" + id + "-template").clone().attr('id', null);
			content.find('.wf-templated').each(function() {
				var element = $(this);
				['id', 'name'].forEach(function(key) {
					replaceTemplatedAttribute(element, key);
				});
			});

			if (wordfenceExt.currentDeactivationDialog) {
				wordfenceExt.currentDeactivationDialog.dialog('close');
			}

			wordfenceExt.currentDeactivationDialog = $('<div>', { html: content }).dialog({
				modal: true,
				width: (wordfenceExt.isSmallScreen ? 300 : 500),
				resizable: false,
				draggable: false,
				zIndex: 9998,
				close: function() {
					$(this).dialog('destroy').remove();
					wordfenceExt.currentDeactivationDialog = false;
				},
				open: function() {
					$('.ui-widget-overlay').last().css('z-index', 9998);
					$('#wf-deactivation-prompt').css('z-index', 9999);
					updateButtons();
					stateController.refresh();
				},
			});

			wordfenceExt.currentDeactivationDialog.dialog('widget').attr('id', 'wf-deactivation-prompt');
		}

		function deactivate() {
			if (wordfenceExt.currentDeactivationDialog) {
				wordfenceExt.currentDeactivationDialog.dialog('close');
			}

			$(document).off('click.wf-deactivate');
			$('#deactivate-wordfence').get(0).click();
		}

		function showDeletionPrompt() {
			showOffboardingModal('delete-prompt');
		}

		$(document)
			.on('click', '.wf-deactivate-confirm', function (event) {
				if (!stateController.startProcessing())
					return;
				var option = $('input[name=wf-deactivate-option]:checked').val();
				function fail() {
					showOffboardingModal('delete-error');
					stateController.endProcessing();
				}
				wordfenceExt.ajax(
					'wordfence_setDeactivationOption',
					{ option: option},
					function(data) {
						if (data.success) {
							if (option !== <?php echo json_encode(wfDeactivationOption::RETAIN); ?>) {
								if (wafOptimized) {
									showOffboardingModal('waf-optimized');
								}
								else {
									showOffboardingModal('delete-confirm');
								}
								stateController.endProcessing();
							}
							else {
								deactivate();
							}
							stateController.endProcessing();
						}
						else {
							fail();
						}
					},
					fail
				);
			})
			.on('click', '.wf-deactivate-delete-confirm', function (event) {
				deactivate();
			})
			.on('click', '.wf-deactivate-delete-cancel', function (event) {
				showDeletionPrompt();
			})
			.on('click', '#wf-deactivate-cancel', function (event) {
				if (wordfenceExt.currentDeactivationDialog) {
					wordfenceExt.currentDeactivationDialog.dialog('close');
				}
			})
			.on('click.wf-deactivate', '#deactivate-wordfence', function (event) {
				event.preventDefault();
				showDeletionPrompt();
			})
			.on('change', 'input[name=wf-deactivate-option]', function(event) {
				updateButtons();
			});

		<?php if ($deactivate): ?>
			$(document).ready(function() {
				$('#deactivate-wordfence').trigger('click');
			});
		<?php endif ?>

	})(jQuery);
</script>