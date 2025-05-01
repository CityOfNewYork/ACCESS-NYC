(function($) {
	function __(string) {
		return WFLS_ADMIN_TRANSLATIONS[string] || string;
	}
	window['WFLS'] = {
		panelIsOpen: false,
		basePageName: '',
		panelQueue: [],
		pendingChanges: {},
		userIsActivating: false,
		
		//Screen sizes
		SCREEN_XS: 'xs',
		SCREEN_SM: 'sm',
		SCREEN_MD: 'md',
		SCREEN_LG: 'lg',

		init: function() {
			this.basePageName = document.title;

			var tabs = $('.wfls-page-tabs').find('.wfls-tab a');
			if (tabs.length > 0) {
				tabs.click(function() {
					$('.wfls-page-tabs').find('.wfls-tab').removeClass('wfls-active');
					$('.wfls-tab-content').removeClass('wfls-active');

					var tab = $(this).closest('.wfls-tab');
					tab.addClass('wfls-active');
					var content = $('#' + tab.data('target'));
					content.addClass('wfls-active');
					document.title = tab.data('pageTitle') + " \u2039 " + WFLS.basePageName;
					$(window).trigger('wfls-tab-change', [tab.data('target')]);
				});
				if (window.location.hash) {
					var hashes = WFLS.parseHashes();
					var hash = hashes[hashes.length - 1];
					for (var i = 0; i < tabs.length; i++) {
						if (hash == $(tabs[i]).closest('.wfls-tab').data('target')) {
							$(tabs[i]).trigger('click');
						}
					}
				}
				else {
					$(tabs[0]).trigger('click');
				}
				$(window).on('hashchange', function () {
					var hashes = WFLS.parseHashes();
					var hash = hashes[hashes.length - 1];
					for (var i = 0; i < tabs.length; i++) {
						if (hash == $(tabs[i]).closest('.wfls-tab').data('target')) {
							$(tabs[i]).trigger('click');
						}
					}
				});
			}

			//On/Off Option
			$('.wfls-option.wfls-option-toggled .wfls-option-checkbox').each(function() {
				$(this).on('keydown', function(e) {
					if (e.keyCode == 32) {
						e.preventDefault();
						e.stopPropagation();

						$(this).trigger('click');
					}
				});

				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var optionElement = $(this).closest('.wfls-option');
					if (optionElement.hasClass('wfls-option-premium') || optionElement.hasClass('wfls-disabled')) {
						return;
					}

					var option = optionElement.data('option');
					var value = false;
					var isActive = $(this).hasClass('wfls-checked');
					if (isActive) {
						$(this).removeClass('wfls-checked').attr('aria-checked', 'false');
						value = optionElement.data('disabledValue');
					}
					else {
						$(this).addClass('wfls-checked').attr('aria-checked', 'true');
						value = optionElement.data('enabledValue');
					}

					var originalValue = optionElement.data('originalValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				});

				$(this).parent().find('.wfls-option-title').on('click', function(e) {
					var links = $(this).find('a');
					var buffer = 10;
					for (var i = 0; i < links.length; i++) {
						var t = $(links[i]).offset().top;
						var l = $(links[i]).offset().left;
						var b = t + $(links[i]).height();
						var r = l + $(links[i]).width();

						if (e.pageX > l - buffer && e.pageX < r + buffer && e.pageY > t - buffer && e.pageY < b + buffer) {
							return;
						}
					}
					$(this).parent().find('.wfls-option-checkbox').trigger('click');
				}).css('cursor', 'pointer');
			});

			//On/Off Boolean Switch Option
			$('.wfls-option.wfls-option-toggled-boolean-switch .wfls-boolean-switch').each(function() {
				$(this).on('keydown', function(e) {
					if (e.keyCode == 32) {
						e.preventDefault();
						e.stopPropagation();

						$(this).trigger('click');
					}
				});

				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					$(this).find('.wfls-boolean-switch-handle').trigger('click');
				});

				$(this).find('.wfls-boolean-switch-handle').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var optionElement = $(this).closest('.wfls-option');
					if (optionElement.hasClass('wfls-option-premium') || optionElement.hasClass('wfls-disabled')) {
						return;
					}

					var switchElement = $(this).closest('.wfls-boolean-switch');
					var option = optionElement.data('option');
					var value = false;
					var isActive = switchElement.hasClass('wfls-active');
					if (isActive) {
						switchElement.removeClass('wfls-active').attr('aria-checked', 'false');
						value = optionElement.data('disabledValue');
					}
					else {
						switchElement.addClass('wfls-active').attr('aria-checked', 'true');
						value = optionElement.data('enabledValue');
					}

					var originalValue = optionElement.data('originalValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				});

				$(this).parent().find('.wfls-option-title').on('click', function(e) {
					var links = $(this).find('a');
					var buffer = 10;
					for (var i = 0; i < links.length; i++) {
						var t = $(links[i]).offset().top;
						var l = $(links[i]).offset().left;
						var b = t + $(links[i]).height();
						var r = l + $(links[i]).width();

						if (e.pageX > l - buffer && e.pageX < r + buffer && e.pageY > t - buffer && e.pageY < b + buffer) {
							return;
						}
					}
					$(this).parent().find('.wfls-boolean-switch-handle').trigger('click');
				}).css('cursor', 'pointer');
			});

			//On/Off Segmented Option
			$('.wfls-option.wfls-option-toggled-segmented [type=radio]').each(function() {
				$(this).on('click', function(e) {
					var optionElement = $(this).closest('.wfls-option');
					if (optionElement.hasClass('wfls-option-premium') || optionElement.hasClass('wfls-disabled')) {
						return;
					}

					var option = optionElement.data('option');
					var value = this.value;

					var originalValue = optionElement.data('originalValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				});
			});

			//On/Off Multiple Option
			$('.wfls-option.wfls-option-toggled-multiple .wfls-option-checkbox').each(function() {
				$(this).on('keydown', function(e) {
					if (e.keyCode == 32) {
						e.preventDefault();
						e.stopPropagation();

						$(this).trigger('click');
					}
				});

				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var optionElement = $(this).closest('.wfls-option');
					if (optionElement.hasClass('wfls-option-premium') || optionElement.hasClass('wfls-disabled') || $(this).hasClass('wfls-disabled')) {
						return;
					}

					var checkboxElement = $(this).closest('ul');
					var option = checkboxElement.data('option');
					var value = false;
					var isActive = $(this).hasClass('wfls-checked');
					if (isActive) {
						$(this).removeClass('wfls-checked').attr('aria-checked', 'false');
						value = checkboxElement.data('disabledValue');
					}
					else {
						$(this).addClass('wfls-checked').attr('aria-checked', 'true');
						value = checkboxElement.data('enabledValue');
					}

					var originalValue = checkboxElement.data('originalValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				});

				$(this).parent().find('.wfls-option-title').on('click', function(e) {
					var links = $(this).find('a');
					var buffer = 10;
					for (var i = 0; i < links.length; i++) {
						var t = $(links[i]).offset().top;
						var l = $(links[i]).offset().left;
						var b = t + $(links[i]).height();
						var r = l + $(links[i]).width();

						if (e.pageX > l - buffer && e.pageX < r + buffer && e.pageY > t - buffer && e.pageY < b + buffer) {
							return;
						}
					}
					$(this).parent().find('.wfls-option-checkbox').trigger('click');
				}).css('cursor', 'pointer');
			});

			//Text field option
			$('.wfls-option.wfls-option-text > .wfls-option-content > ul > li.wfls-option-text input').on('change paste keyup', function() {
				var e = this;

				setTimeout(function() {
					var optionElement = $(e).closest('.wfls-option');
					var option = optionElement.data('textOption');

					if (typeof option !== 'undefined') {
						var value = $(e).val();

						var originalValue = optionElement.data('originalTextValue');
						if (originalValue == value) {
							delete WFLS.pendingChanges[option];
						}
						else {
							WFLS.pendingChanges[option] = value;
						}

						$(optionElement).trigger('change', [false]);
						WFLS.updatePendingChanges();
					}
				}, 4);
			});
			
			//Menu option
			$('.wfls-option.wfls-option-toggled-select > .wfls-option-content > ul > li.wfls-option-select select, .wfls-option.wfls-option-select > .wfls-option-content > ul > li.wfls-option-select select, .wf-option.wfls-option-select > li.wfls-option-select select').each(function() {
				if (!$.fn.wfselect2) { return; }

				var width = (WFLS.screenSize(500) ? '200px' : 'resolve');
				if ($(this).data('preferredWidth')) {
					width = $(this).data('preferredWidth');
				}

				$(this).wfselect2({
					minimumResultsForSearch: -1,
					width: width
				}).on('change', function () {
					var optionElement = $(this).closest('.wfls-option');
					var option = optionElement.data('selectOption');
					var value = $(this).val();

					var originalValue = optionElement.data('originalSelectValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				});
			}).triggerHandler('change');

			//Text area option
			$('.wfls-option.wfls-option-textarea > .wfls-option-content > ul > li.wfls-option-textarea textarea').on('change paste keyup', function() {
				var e = this;

				setTimeout(function() {
					var optionElement = $(e).closest('.wfls-option');
					var option = optionElement.data('textOption');
					var value = $(e).val();

					var originalValue = optionElement.data('originalTextValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				}, 4);
			});

			//Switch Option
			$('.wfls-option.wfls-option-switch .wfls-switch > li').each(function(index, element) {
				$(this).on('keydown', function(e) {
					if (e.keyCode == 32) {
						e.preventDefault();
						e.stopPropagation();

						$(this).trigger('click');
					}
				});

				$(element).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var optionElement = $(this).closest('ul.wfls-option-switch, div.wfls-option-switch');
					var optionName = optionElement.data('optionName');
					var originalValue = optionElement.data('originalValue');
					var value = $(this).data('optionValue');

					var control = $(this).closest('.wfls-switch');
					control.find('li').each(function() {
						$(this).toggleClass('wfls-active', value == $(this).data('optionValue')).attr('aria-checked', value == $(this).data('optionValue') ? 'true' : 'false');
					});

					if (originalValue == value) {
						delete WFLS.pendingChanges[optionName];
					}
					else {
						WFLS.pendingChanges[optionName] = value;
					}

					$(optionElement).trigger('change', [false]);
					WFLS.updatePendingChanges();
				});
			});

			//Dropdown/Text Options
			$('select.wfls-option-select, input.wfls-option-input').each(function() {
				$(this).data('original', $(this).val());
			}).on('change input', function(e) {
				var input = $(this);
				var name = input.attr('name');
				var value = input.val();
				var original = input.data('original');
				if (value === original || (input.hasClass('wfls-option-input-required') && value === '')) {
					delete WFLS.pendingChanges[name];
				}
				else {
					WFLS.pendingChanges[name] = value;
				}
				WFLS.updatePendingChanges();
			});

			$('#wfls-save-changes').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				WFLS.saveOptions(function(res) {
					WFLS.pendingChanges = {};
					WFLS.updatePendingChanges();

					if (res.redirect) {
						window.location.href = res.redirect;
					}
					else {
						window.location.reload(true);
					}
				});
			});

			$('#wfls-cancel-changes').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				//On/Off options
				$('.wfls-option.wfls-option-toggled').each(function() {
					var enabledValue = $(this).data('enabledValue');
					var disabledValue = $(this).data('disabledValue');
					var originalValue = $(this).data('originalValue');
					if (enabledValue == originalValue) {
						$(this).find('.wfls-option-checkbox').addClass('wfls-checked').attr('aria-checked', 'true');
					}
					else {
						$(this).find('.wfls-option-checkbox').removeClass('wfls-checked').attr('aria-checked', 'false');
					}
					$(this).trigger('change', [true]);
				});

				$('.wfls-option-toggled-boolean-switch').each(function() {
					var enabledValue = $(this).data('enabledValue');
					var disabledValue = $(this).data('disabledValue');
					var originalValue = $(this).data('originalValue');
					if (enabledValue == originalValue) {
						$(this).find('.wfls-boolean-switch').addClass('wfls-active').attr('aria-checked', 'true');
					}
					else {
						$(this).find('.wfls-boolean-switch').removeClass('wfls-active').attr('aria-checked', 'false');
					}
					$(this).trigger('change', [true]);
				});

				$('.wfls-option.wfls-option-toggled-segmented').each(function() {
					var originalValue = $(this).data('originalValue');
					$(this).find('[type=radio]').each(function() {
						if (this.value == originalValue) {
							this.checked = true;
							return false;
						}
					});
					$(this).trigger('change', [true]);
				});

				//On/Off multiple options
				$('.wfls-option.wfls-option-toggled-multiple').each(function() {
					$(this).find('.wfls-option-checkboxes > ul').each(function() {
						var enabledValue = $(this).data('enabledValue');
						var disabledValue = $(this).data('disabledValue');
						var originalValue = $(this).data('originalValue');
						if (enabledValue == originalValue) {
							$(this).find('.wfls-option-checkbox').addClass('wfls-checked').attr('aria-checked', 'true');
						}
						else {
							$(this).find('.wfls-option-checkbox').removeClass('wfls-checked').attr('aria-checked', 'false');
						}
					});
					$(this).trigger('change', [true]);
				});

				//On/Off options with menu
				$('.wfls-option.wfls-option-toggled-select').each(function() {
					var selectElement = $(this).find('.wfls-option-select select');
					var enabledToggleValue = $(this).data('enabledToggleValue');
					var disabledToggleValue = $(this).data('disabledToggleValue');
					var originalToggleValue = $(this).data('originalToggleValue');
					if (enabledToggleValue == originalToggleValue) {
						$(this).find('.wfls-option-checkbox').addClass('wfls-checked').attr('aria-checked', 'true');
						selectElement.attr('disabled', false);
					}
					else {
						$(this).find('.wfls-option-checkbox').removeClass('wfls-checked').attr('aria-checked', 'false');
						selectElement.attr('disabled', true);
					}

					var originalSelectValue = $(this).data('originalSelectValue');
					$(this).find('.wfls-option-select select').val(originalSelectValue).trigger('change');
					$(this).trigger('change', [true]);
				});

				//Menu options
				$('.wfls-option.wfls-option-select').each(function() {
					var originalSelectValue = $(this).data('originalSelectValue');
					$(this).find('.wfls-option-select select').val(originalSelectValue).trigger('change');
					$(this).trigger('change', [true]);
				});

				//Text options
				$('.wfls-option.wfls-option-text').each(function() {
					var originalTextValue = $(this).data('originalTextValue');
					if (typeof originalTextValue !== 'undefined') {
						$(this).find('.wfls-option-text input').val(originalTextValue);
					}
					$(this).trigger('change', [true]);
				});

				//Text area options
				$('.wfls-option.wfls-option-textarea').each(function() {
					var originalTextValue = $(this).data('originalTextValue');
					$(this).find('.wfls-option-textarea textarea').val(originalTextValue);
					$(this).trigger('change', [true]);
				});

				//Token options
				$('.wfls-option.wfls-option-token').each(function() {
					var originalTokenValue = $(this).data('originalTokenValue');
					$(this).find('select').val(originalTokenValue).trigger('change');
					$(this).trigger('change', [true]);
				});

				//Switch options
				$('.wfls-option.wfls-option-switch').each(function() {
					var originalValue = $(this).data('originalValue');
					$(this).find('.wfls-switch > li').each(function() {
						$(this).toggleClass('wfls-active', originalValue == $(this).data('optionValue')).attr('aria-checked', originalValue == $(this).data('optionValue') ? 'true' : 'false');
					});
					$(this).trigger('change', [true]);
				});

				//Other options
				$(window).trigger('wflsOptionsReset');
				
				WFLS.pendingChanges = {};
				WFLS.updatePendingChanges();
			});
		},

		updatePendingChanges: function() {
			$(window).off('beforeunload', WFLS._unsavedOptionsHandler);
			if (Object.keys(WFLS.pendingChanges).length) {
				$('#wfls-cancel-changes').removeClass('wfls-disabled');
				$('#wfls-save-changes').removeClass('wfls-disabled');
				$(window).on('beforeunload', WFLS._unsavedOptionsHandler);
			}
			else {
				$('#wfls-cancel-changes').addClass('wfls-disabled');
				$('#wfls-save-changes').addClass('wfls-disabled');
			}
		},

		_unsavedOptionsHandler: function(e) {
			var message = __("You have unsaved changes to your options. If you leave this page, those changes will be lost."); //Only shows on older browsers, newer browsers don't allow message customization 
			e = e || window.event;
			if (e) {
				e.returnValue = message; //IE and Firefox
			}
			return message; //Others
		},
		
		setOptions: function(options, successCallback, failureCallback) {
			if (!Object.keys(options).length) {
				return;
			}

			this.ajax('wordfence_ls_save_options', {changes: JSON.stringify(options)}, function(res) {
				if (res.success) {
					typeof successCallback == 'function' && successCallback(res);
				}
				else {
					if (res.hasOwnProperty('html') && res.html) {
						WFLS.panelModalHTML((WFLS.screenSize(500) ? '300px' : '400px'), 'Error Saving Options', res.error);
					}
					else {
						WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), 'Error Saving Options', res.error);
					}

					typeof failureCallback == 'function' && failureCallback
				}
			});
		},

		saveOptions: function(successCallback, failureCallback) {
			this.setOptions(WFLS.pendingChanges, successCallback, failureCallback);
		},

		updateIPPreview: function(value, successCallback) {
			this.ajax('wordfence_ls_update_ip_preview', value, function(response) {
				if (successCallback) {
					successCallback(response);
				}
			});
		},

		/**
		 * Sends a WP AJAX call, automatically adding our nonce.
		 * 
		 * @param string action
		 * @param string|array|object payload
		 * @param function successCallback
		 * @param function failureCallback
		 */
		ajax: function(action, payload, successCallback, failureCallback) {
			if (typeof(payload) == 'string') {
				if (payload.length > 0) {
					payload += '&';
				}
				payload += 'action=' + action + '&nonce=' + WFLSVars.nonce;
			}
			else if (typeof(payload) == 'object' && payload instanceof Array) {
				// jQuery serialized form data
				payload.push({
					name: 'action',
					value: action
				});
				payload.push({
					name: 'nonce',
					value: WFLSVars.nonce
				});
			}
			else if (typeof(payload) == 'object') {
				payload['action'] = action;
				payload['nonce'] = WFLSVars.nonce;
			}
			
			
			$.ajax({
				type: 'POST',
				url: WFLSVars.ajaxurl,
				dataType: "json",
				data: payload,
				success: function(json) {
					typeof successCallback == 'function' && successCallback(json);
				},
				error: function() {
					typeof failureCallback == 'function' && failureCallback();
				}
			});
		},

		/**
		 * Displays a generic panel.
		 * 
		 * @param @param string width A width string in the format '100px'
		 * @param string heading
		 * @param string body
		 * @param object settings
		 */
		panel: function(width, heading, body, settings) {
			if (typeof settings === 'undefined') {
				settings = {};
			}
			WFLS.panelQueue.push([width, "<h3>" + heading + "</h3><p>" + body + "</p>", settings]);
			WFLS._panelServiceQueue();
		},

		/**
		 * Displays a modal panel with fixed HTML content.
		 * 
		 * @param @param string width A width string in the format '100px'
		 * @param string heading
		 * @param string body
		 * @param object settings
		 */
		panelModalHTML: function(width, heading, body, settings) {
			if (typeof settings === 'undefined') {
				settings = {};
			}

			var prompt = $.tmpl(WFLSVars.modalHTMLTemplate, {title: heading, message: body});
			var promptHTML = $("<div />").append(prompt).html();
			var callback = settings.onComplete;
			settings.overlayClose = false;
			settings.closeButton = false;
			settings.className = 'wfls-modal';
			settings.onComplete = function() {
				$('#wfls-generic-modal-close').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					WFLS.panelClose();
				});

				typeof callback === 'function' && callback();
			};
			WFLS.panelHTML(width, promptHTML, settings)
		},

		/**
		 * Displays a modal panel, automatically escaping the content.
		 *
		 * @param @param string width A width string in the format '100px'
		 * @param string heading
		 * @param string body
		 * @param object settings
		 */
		panelModal: function(width, heading, body, settings) {
			if (typeof settings === 'undefined') {
				settings = {};
			}

			if (width === null)
				width = WFLS.screenSize(500) ? '300px' : '400px';

			var includeDefaultButtons = typeof settings.includeDefaultButtons === 'undefined' ? false : settings.includeDefaultButtons;
			var prompt = $.tmpl(WFLSVars[includeDefaultButtons ? 'modalTemplate' : 'modalNoButtonsTemplate'], {title: heading, message: body});

			if (typeof settings.additional_buttons !== 'undefined') {
				var buttonSection = prompt.find('.wfls-modal-footer > ul');
				for(index in settings.additional_buttons) {
					var buttonSettings = settings.additional_buttons[index];
					var button = $('<button>').text(buttonSettings.label)
						.addClass('wfls-btn wfls-btn-callout-subtle wfls-additional-button')
						.attr('id', buttonSettings.id);
					var buttonType = typeof buttonSettings.type === 'undefined' ? 'default' : buttonSettings.type;
					button.addClass('wfls-btn-' + buttonType);
					buttonSection.prepend($("<li>").addClass('wfls-padding-add-left-small').append(button));
				}
			}

			var promptHTML = $("<div />").append(prompt).html();
			var callback = settings.onComplete;
			settings.overlayClose = false;
			settings.closeButton = false;
			settings.className = 'wfls-modal';
			settings.onComplete = function() {
				$('#wfls-generic-modal-close').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					WFLS.panelClose();
				});

				typeof callback === 'function' && callback();
			};
			WFLS.panelHTML(width, promptHTML, settings)
		},

		/**
		 * Displays a modal with the given title and message text.
		 *
		 * @param string title the modal title
		 * @param string message the message (this will be treated as text, not HTML)
		 * @param array buttons the buttons to include in the modal footer
		 *	Each item in the array should be an object with the following properties:
		 *		- label: The button text
		 *		- id: An ID for the button
		 *		- type: The type of button for styling purposes - i.e. default, primary (default: 'default')
		 * @param object settings
		 *
		 * @see WFLS.panelModal
		 */
		displayModalMessage: function(title, message, buttons, settings) {
			if (typeof settings !== 'object')
				settings = {};
			var width = typeof settings.width === 'undefined' ? null : settings.width;
			settings.includeDefaultButtons = false;
			settings.additional_buttons = buttons;
			WFLS.panelModal(width, title, message, settings);
		},

		/**
		 * Displays a modal panel with the error formatting.
		 *
		 * @param string errorMsg
		 * @param bool isTokenError Whether or not this error is an expired nonce error.
		 */
		panelError: function(errorMsg, isTokenError) {
			var callback = false;
			if (isTokenError) {
				if (WFLS.tokenErrorShowing) {
					return;
				}

				callback = function() {
					setTimeout(function() {
						WFLS.tokenErrorShowing = false;
					}, 30000);
				};

				WFLS.tokenErrorShowing = true;
			}

			var prompt = $.tmpl(WFLSVars.tokenInvalidTemplate, {title: 'An error occurred', message: errorMsg});
			var promptHTML = $("<div />").append(prompt).html();
			var settings = {};
			settings.overlayClose = false;
			settings.closeButton = false;
			settings.className = 'wfls-modal';
			settings.onComplete = function() {
				$('#wfls-token-invalid-modal-reload').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					window.location.reload(true);
				});

				typeof callback === 'function' && callback();
			};
			WFLS.panelHTML((WFLS.screenSize(500) ? '300px' : '400px'), promptHTML, settings);
		},

		/**
		 * Displays a panel with fixed HTML content.
		 *
		 * @param string width A width string in the format '100px'
		 * @param string html
		 * @param object settings
		 */
		panelHTML: function(width, html, settings) {
			if (typeof settings === 'undefined') {
				settings = {};
			}
			WFLS.panelQueue.push([width, html, settings]);
			WFLS._panelServiceQueue();
		},

		/**
		 * Displays the next panel in the queue.
		 */
		_panelServiceQueue: function() {
			if (WFLS.panelIsOpen) {
				return;
			}
			if (WFLS.panelQueue.length < 1) {
				return;
			}
			var elem = WFLS.panelQueue.shift();
			WFLS._panelOpen(elem[0], elem[1], elem[2]);
		},

		/**
		 * Does the actual function call to display the panel.
		 *
		 * @param string width A width string in the format '100px'
		 * @param string html
		 * @param object settings
		 */
		_panelOpen: function(width, html, settings) {
			this.panelIsOpen = true;
			$.extend(settings, {
				width: width,
				html: html,
				onClosed: function() {
					WFLS.panelClose();
				}
			});
			$.wflscolorbox(settings);
		},

		/**
		 * Closes the current panel.
		 */
		panelClose: function() {
			WFLS.panelIsOpen = false;
			if (WFLS.panelQueue.length < 1) {
				$.wflscolorbox.close();
			}
			else {
				WFLS._panelServiceQueue();
			}
		},

		/**
		 * Parses and returns the hash portion of a URL, working around user agents that URL-encode the # character.
		 * 
		 * @returns {Array}
		 */
		parseHashes: function() {
			var hashes = window.location.hash.replace('%23', '#');
			var splitHashes = hashes.split('#');
			var result = [];
			for (var i = 0; i < splitHashes.length; i++) {
				if (splitHashes[i].length > 0) {
					result.push(splitHashes[i]);
				}
			}
			return result;
		},

		/**
		 * Returns whether or not the screen size is within the size given. This may be a numerical value
		 * or one of the WFLS_SCREEN_ constants.
		 * 
		 * @param size
		 * @returns {boolean}
		 */
		screenSize: function(size) {
			switch (size) {
				case WFLS.SCREEN_XS:
					return window.matchMedia("only screen and (max-width: 767px)").matches;
				case WFLS.SCREEN_SM:
					return window.matchMedia("only screen and (max-width: 991px)").matches;
				case WFLS.SCREEN_MD:
					return window.matchMedia("only screen and (max-width: 1199px)").matches;
				case WFLS.SCREEN_LG:
					return window.matchMedia("only screen and (max-width: 32767px)").matches;
			}
			
			var parsed = parseInt(size);
			if (isNaN(parsed)) {
				return false;
			}
			return window.matchMedia("only screen and (max-width: " + parsed + "px)").matches;
		},
	};
	
	$(function() {
		WFLS.init();
	});

	$.fn.crossfade = function(incoming, duration, complete) {
		duration = duration || 400;
		complete = complete || function() { };
		
		return this.each(function() {
			$(this).fadeOut(duration, function() {
				$(incoming).fadeIn(duration, complete);
			});
		});
	};
})(jQuery);

/*! @source https://github.com/eligrey/FileSaver.js/blob/master/dist/FileSaver.min.js */
(function(a,b){if("function"==typeof define&&define.amd)define([],b);else if("undefined"!=typeof exports)b();else{b(),a.FileSaver={exports:{}}.exports}})(this,function(){"use strict";function b(a,b){return"undefined"==typeof b?b={autoBom:!1}:"object"!=typeof b&&(console.warn("Deprecated: Expected third argument to be a object"),b={autoBom:!b}),b.autoBom&&/^\s*(?:text\/\S*|application\/xml|\S*\/\S*\+xml)\s*;.*charset\s*=\s*utf-8/i.test(a.type)?new Blob(["\uFEFF",a],{type:a.type}):a}function c(a,b,c){var d=new XMLHttpRequest;d.open("GET",a),d.responseType="blob",d.onload=function(){g(d.response,b,c)},d.onerror=function(){console.error("could not download file")},d.send()}function d(a){var b=new XMLHttpRequest;b.open("HEAD",a,!1);try{b.send()}catch(a){}return 200<=b.status&&299>=b.status}function e(a){try{a.dispatchEvent(new MouseEvent("click"))}catch(c){var b=document.createEvent("MouseEvents");b.initMouseEvent("click",!0,!0,window,0,0,0,80,20,!1,!1,!1,!1,0,null),a.dispatchEvent(b)}}var f="object"==typeof window&&window.window===window?window:"object"==typeof self&&self.self===self?self:"object"==typeof global&&global.global===global?global:void 0,a=/Macintosh/.test(navigator.userAgent)&&/AppleWebKit/.test(navigator.userAgent)&&!/Safari/.test(navigator.userAgent),g=f.saveAs||("object"!=typeof window||window!==f?function(){}:"download"in HTMLAnchorElement.prototype&&!a?function(b,g,h){var i=f.URL||f.webkitURL,j=document.createElement("a");g=g||b.name||"download",j.download=g,j.rel="noopener","string"==typeof b?(j.href=b,j.origin===location.origin?e(j):d(j.href)?c(b,g,h):e(j,j.target="_blank")):(j.href=i.createObjectURL(b),setTimeout(function(){i.revokeObjectURL(j.href)},4E4),setTimeout(function(){e(j)},0))}:"msSaveOrOpenBlob"in navigator?function(f,g,h){if(g=g||f.name||"download","string"!=typeof f)navigator.msSaveOrOpenBlob(b(f,h),g);else if(d(f))c(f,g,h);else{var i=document.createElement("a");i.href=f,i.target="_blank",setTimeout(function(){e(i)})}}:function(b,d,e,g){if(g=g||open("","_blank"),g&&(g.document.title=g.document.body.innerText="downloading..."),"string"==typeof b)return c(b,d,e);var h="application/octet-stream"===b.type,i=/constructor/i.test(f.HTMLElement)||f.safari,j=/CriOS\/[\d]+/.test(navigator.userAgent);if((j||h&&i||a)&&"undefined"!=typeof FileReader){var k=new FileReader;k.onloadend=function(){var a=k.result;a=j?a:a.replace(/^data:[^;]*;/,"data:attachment/file;"),g?g.location.href=a:location=a,g=null},k.readAsDataURL(b)}else{var l=f.URL||f.webkitURL,m=l.createObjectURL(b);g?g.location=m:location.href=m,g=null,setTimeout(function(){l.revokeObjectURL(m)},4E4)}});f.saveAs=g.saveAs=g,"undefined"!=typeof module&&(module.exports=g)});
