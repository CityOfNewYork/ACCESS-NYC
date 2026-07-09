(function($) {
	var sprintf,
		__;

	if (!window['wordfenceAdmin']) { //To compile for checking: java -jar /usr/local/bin/closure.jar --js=admin.js --js_output_file=test.js
		window['wordfenceAdmin'] = {
			isSmallScreen: false,
			loadingCount: 0,
			mode: '',
			nonce: false,
			debugOn: false,
			_windowHasFocus: true,
			basePageName: '',
			siteCleaningIssueTypes: ['file', 'checkGSB', 'checkSpamIP', 'commentBadURL', 'knownfile', 'optionBadURL', 'postBadTitle', 'postBadURL', 'spamvertizeCheck', 'suspiciousAdminUsers'],

			//Screen sizes
			SCREEN_XS: 'xs',
			SCREEN_SM: 'sm',
			SCREEN_MD: 'md',
			SCREEN_LG: 'lg',

			init: function() {
				this.isSmallScreen = window.matchMedia("only screen and (max-width: 500px)").matches;
				
				this.nonce = WordfenceAdminVars.firstNonce;
				this.debugOn = WordfenceAdminVars.debugOn == '1' ? true : false;
				this.basePageName = document.title;
				var self = this;

				$(window).on('blur', function() {
					self._windowHasFocus = false;
				}).on('focus', function() {
					self._windowHasFocus = true;
				}).focus();

				$('.do-show').click(function() {
					var $this = $(this);
					$this.hide();
					$($this.data('selector')).show();
					return false;
				});
				
				$('.downloadLogFile').each(function() {
					$(this).attr('href', WordfenceAdminVars.ajaxURL + '?action=wordfence_downloadLogFile&nonce=' + WFAD.ajaxNonce('downloadLogFile') + '&logfile=' + encodeURIComponent($(this).data('logfile')));
				});

				var tabs = jQuery('.wf-page-tabs').find('.wf-tab a');
				if (tabs.length > 0) {
					tabs.click(function() {
						jQuery('.wf-page-tabs').find('.wf-tab').removeClass('wf-active').find('a').attr('aria-selected', 'false');
						jQuery('.wf-tab-content').removeClass('wf-active');
						
						var tab = jQuery(this).closest('.wf-tab');
						tab.addClass('wf-active');
						tab.find('a').attr('aria-selected', 'true');
						var content = jQuery('#' + tab.data('target'));
						content.addClass('wf-active');
						document.title = tab.data('pageTitle') + " \u2039 " + self.basePageName;
						self.sectionInit();
						$(window).trigger('wfTabChange', [tab.data('target')]);
					});
					if (window.location.hash) {
						var hashes = WFAD.parseHashes();
						var hash = hashes[hashes.length - 1];
						for (var i = 0; i < tabs.length; i++) {
							if (hash == jQuery(tabs[i]).closest('.wf-tab').data('target')) {
								jQuery(tabs[i]).trigger('click');
							}
						}
					}
					else {
						jQuery(tabs[0]).trigger('click');
					}
					jQuery(window).on('hashchange', function () {
						var hashes = WFAD.parseHashes();
						var hash = hashes[hashes.length - 1];
						for (var i = 0; i < tabs.length; i++) {
							if (hash == jQuery(tabs[i]).closest('.wf-tab').data('target')) {
								jQuery(tabs[i]).trigger('click');
							}
						}
					});
				}
				else {
					this.sectionInit();
				}
				
				if ($('.wf-options-controls-spacer').length) { //The WP code doesn't move update nags and we need to
					$('.update-nag, #update-nag').insertAfter($('.wf-options-controls-spacer'));
				}

				$(document).focus();

				// (docs|support).wordfence.com GA links
				$(document).on('click', 'a', function() {
					if (this.href && this.href.indexOf('utm_source') > -1) {
						return;
					}
					var utm = '';
					if ((this.host == 'www.wordfence.com' || this.host == 'wordfence.com') && /^\/help(?:$|\/)/.test(this.pathname)) {
						utm = 'utm_source=plugin&utm_medium=pluginUI&utm_campaign=docsIcon';
					}
					if (utm) {
						utm = (this.search ? '&' : '?') + utm;
						this.href = this.protocol + '//' + this.host + this.pathname + this.search + utm + this.hash;
					}

					if (this.href == 'http://support.wordfence.com/') {
						this.href = 'https://support.wordfence.com/support/home?utm_source=plugin&utm_medium=pluginUI&utm_campaign=supportLink';
					}
				});

				$('.wf-block-header-action-disclosure.wf-legacy').each(function() {
					$(this).on('keydown', function(e) {
						if (e.keyCode == 32) {
							e.preventDefault();
							e.stopPropagation();

							$(this).closest('.wf-block-header').trigger('click');
						}
					});

					$(this).closest('.wf-block-header').css('cursor', 'pointer');
					$(this).closest('.wf-block-header').on('click', function(e) {
						// Let links in the header work.
						if (e.target && e.target.nodeName === 'A' && e.target.href) {
							return;
						}
						e.preventDefault();
						e.stopPropagation();

						if ($(this).closest('.wf-block').hasClass('wf-disabled')) {
							return;
						}

						var isActive = $(this).closest('.wf-block').hasClass('wf-active');
						if (isActive) {
							$(this).closest('.wf-block').find('.wf-block-content').slideUp({
								always: function() {
									$(this).closest('.wf-block').removeClass('wf-active');
									$(this).closest('.wf-block').find('.wf-block-header-action-disclosure').attr('aria-checked', 'false');
								}
							});
						}
						else {
							$(this).closest('.wf-block').find('.wf-block-content').slideDown({
								always: function() {
									$(this).closest('.wf-block').addClass('wf-active');
									$(this).closest('.wf-block').find('.wf-block-header-action-disclosure').attr('aria-checked', 'true');
								}
							});
						}

						WFAD.ajax('wordfence_saveDisclosureState', {name: $(this).closest('.wf-block').data('persistenceKey'), state: !isActive}, function() {}, function() {}, true);
					});
				});
			},
			sectionInit: function() {
				var self = this;
				this.mode = false;
				if (jQuery('#wordfenceMode_dashboard:visible').length > 0) {
					this.mode = 'dashboard';
				} else if (jQuery('#wordfenceMode_scan:visible').length > 0) {
					this.mode = 'scan';
				} else if (jQuery('#wordfenceMode_waf:visible').length > 0) {
					this.mode = 'waf';
				} else if (jQuery('#wordfenceMode_twoFactor:visible').length > 0) {
					this.mode = 'twoFactor';
				} else if (jQuery('#wordfenceMode_scanScheduling:visible').length > 0) {
					this.mode = 'scanScheduling';
					this.sched_modeChange();
				}
			},
			wordfenceSatisfactionChoice: function(choice) {
				if (choice == 'yes') {
					$('#wordfenceSatisfactionPrompt-yes').slideDown(400, function() {
						$('#wordfenceSatisfactionPrompt-initial .wf-btn').addClass('wf-disabled').css('opacity', 0.3);
						$('#wordfenceSatisfactionPrompt-initial .wf-btn:first-of-type').css('opacity', 0.8);
					});
					WFAD.ajax('wordfence_wordfenceSatisfactionChoice', {choice: choice});
				}
				else if (choice == 'no') {
					$('#wordfenceSatisfactionPrompt-no').slideDown(400, function() {
						$('#wordfenceSatisfactionPrompt-initial .wf-btn').addClass('wf-disabled').css('opacity', 0.3);
						$('#wordfenceSatisfactionPrompt-initial .wf-btn:last-of-type').css('opacity', 0.8);
					});
					WFAD.ajax('wordfence_wordfenceSatisfactionChoice', {choice: choice});
				}
				else if (choice == 'feedback') {
					WFAD.ajax('wordfence_wordfenceSatisfactionChoice', {
							choice: choice,
							feedback: $('#wordfenceSatisfactionPrompt-feedback').val(),
						},
						function(res) { $('#wordfenceSatisfactionPrompt-no').fadeOut(); $('#wordfenceSatisfactionPrompt-complete').fadeIn(); },
						function() { $('#wordfenceSatisfactionPrompt-no').fadeOut(); $('#wordfenceSatisfactionPrompt-complete').fadeIn(); }
					);
				}
				else if (choice == 'dismiss') {
					$('#wordfenceSatisfactionPrompt').fadeOut();
					WFAD.ajax('wordfence_wordfenceSatisfactionChoice', {choice: choice});
				}
			},
			showLoading: function() {
				this.loadingCount++;
				if (this.loadingCount == 1) {
					$('<div id="wordfenceWorking">' + __('Wordfence is working...') + '</div>').appendTo('body');
				}
			},
			removeLoading: function() {
				this.loadingCount--;
				if (this.loadingCount == 0) {
					jQuery('#wordfenceWorking').remove();
				}
			},

			/**
			 * Returns the nonce for the given action. If there isn't one, returns null.
			 *
			 * @param {string} action
			 * @returns {string|null}
			 */
			ajaxNonce: function(action) {
				const normalizedAction = String(action ?? '').replace(/^wordfence_/, '');
				for (const entry of Object.values(this.nonce ?? {})) {
					if (entry?.actions?.includes(normalizedAction)) {
						return entry.nonce ?? null;
					}
				}
				return null;
			},

			/**
			 * Updates the cached nonce for the given action.
			 *
			 * @param {string} action
			 * @param {string} newNonce
			 * @returns {boolean}
			 */
			updateAjaxNonce: function(action, newNonce) {
				if (!newNonce) { return false; }
				const normalizedAction = String(action ?? '').replace(/^wordfence_/, '');
				for (const entry of Object.values(this.nonce ?? {})) {
					if (entry?.actions?.includes(normalizedAction)) {
						entry.nonce = newNonce;
						return true;
					}
				}
				return false;
			},

			/**
			 * Calls the AJAX endpoint for the given action with the payload provided. Depending on the response, calls cb
			 * or cbErr with the result.
			 *
			 * @param {string} action
			 * @param {string|array|object} data
			 * @param {function} cb
			 * @param {function} cbErr
			 * @param {boolean} noLoading
			 */
			ajax(action, data, cb = () => {}, cbErr = () => {}, noLoading = false) {
				const onSuccess = (typeof cb === 'function') ? cb : () => {};
				const onError = (typeof cbErr === 'function') ? cbErr : () => {};

				if (typeof data === 'string') {
					data += `${data.length > 0 ? '&' : ''}action=${action}&nonce=${this.ajaxNonce(action)}`;
				}
				else if (typeof(data) == 'object' && data instanceof Array) {
					data.push({ name: 'action', value: action });
					data.push({ name: 'nonce', value: this.ajaxNonce(action) });
				}
				else if (data && typeof data === 'object') {
					data = Object.assign({}, data, { action, nonce: this.ajaxNonce(action) });
				}

				if (!noLoading) { this.showLoading(); }
				jQuery.ajax({
					type: 'POST',
					url: WordfenceAdminVars.ajaxURL,
					dataType: 'json',
					data,
					success: (json) => {
						if (!noLoading) { this.removeLoading(); }
						this.updateAjaxNonce(action, json?.nonce);
						if (json?.errorMsg) {
							window.WFEventEmitter.emit('showModal', { name: 'simple-confirmation-modal', title: __('An error occurred'), message: json.errorMsg });
						}
						onSuccess(json);
					},
					error: (response) => {
						if (!noLoading) { this.removeLoading(); }
						onError();
					},
				});
			},
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
			inet_aton: function(dot) {
				var d = dot.split('.');
				return ((((((+d[0]) * 256) + (+d[1])) * 256) + (+d[2])) * 256) + (+d[3]);
			},
			inet_ntoa: function(num) {
				var d = num % 256;
				for (var i = 3; i > 0; i--) {
					num = Math.floor(num / 256);
					d = num % 256 + '.' + d;
				}
				return d;
			},

			inet_pton: function(a) {
				//  discuss at: http://phpjs.org/functions/inet_pton/
				// original by: Theriault
				//   example 1: inet_pton('::');
				//   returns 1: '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0'
				//   example 2: inet_pton('127.0.0.1');
				//   returns 2: '\x7F\x00\x00\x01'

				var r, m, x, i, j, f = String.fromCharCode;
				m = a.match(/^(?:\d{1,3}(?:\.|$)){4}/); // IPv4
				if (m) {
					m = m[0].split('.');
					m = f(m[0]) + f(m[1]) + f(m[2]) + f(m[3]);
					// Return if 4 bytes, otherwise false.
					return m.length === 4 ? m : false;
				}
				r = /^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i;
				m = a.match(r); // IPv6
				if (m) {
					if (a == '::') {
						return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
					}

					var colonCount = a.split(':').length - 1;
					var doubleColonPos = a.indexOf('::');
					if (doubleColonPos > -1) {
						var expansionLength = ((doubleColonPos == 0 || doubleColonPos == a.length - 2) ? 9 : 8) - colonCount;
						var expansion = '';
						for (i = 0; i < expansionLength; i++) {
							expansion += ':0000';
						}
						a = a.replace('::', expansion + ':');
						a = a.replace(/(?:^\:|\:$)/, '', a);
					}
					
					var ipGroups = a.split(':');
					var ipBin = '';
					for (i = 0; i < ipGroups.length; i++) {
						var group = ipGroups[i];
						if (group.length > 4) {
							return false;
						}
						group = ("0000" + group).slice(-4);
						var b1 = parseInt(group.slice(0, 2), 16);
						var b2 = parseInt(group.slice(-2), 16);
						if (isNaN(b1) || isNaN(b2)) {
							return false;
						}
						ipBin += f(b1) + f(b2);
					}
					
					return ipBin.length == 16 ? ipBin : false;
				}
				return false; // Invalid IP.
			},
			inet_ntop: function(a) {
				//  discuss at: http://phpjs.org/functions/inet_ntop/
				// original by: Theriault
				//   example 1: inet_ntop('\x7F\x00\x00\x01');
				//   returns 1: '127.0.0.1'
				//   example 2: inet_ntop('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\1');
				//   returns 2: '::1'

				var i = 0,
					m = '',
					c = [];
				a += '';
				if (a.length === 4) { // IPv4
					return [
						a.charCodeAt(0), a.charCodeAt(1), a.charCodeAt(2), a.charCodeAt(3)].join('.');
				} else if (a.length === 16) { // IPv6
					for (i = 0; i < 16; i++) {
						c.push(((a.charCodeAt(i++) << 8) + a.charCodeAt(i))
							.toString(16));
					}
					return c.join(':')
						.replace(/((^|:)0(?=:|$))+:?/g, function(t) {
							m = (t.length > m.length) ? t : m;
							return t;
						})
						.replace(m || ' ', '::');
				} else { // Invalid length
					return false;
				}
			},

			getParameterByName: function(name, url) {
				if (!url) url = window.location.href;
				name = name.replace(/[\[\]]/g, "\\$&");
				var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
					results = regex.exec(url);
				if (!results) return null;
				if (!results[2]) return '';
				return decodeURIComponent(results[2].replace(/\+/g, " "));
			},
		};

		window['WFAD'] = window['wordfenceAdmin'];
	}

	__ = window.wfi18n.__;
	sprintf = window.wfi18n.sprintf;

	jQuery(function() {
		wordfenceAdmin.init();
		jQuery(window).on('focus', function() {
			if (jQuery('body').hasClass('wordfenceLiveActivityPaused')) {
				jQuery('body').removeClass('wordfenceLiveActivityPaused');
			}
		});
	});
})(jQuery);

//wfMobileMenu
(function ($, document, window) {
	var __ = window.wfi18n.__;

	var defaults = {
		width: '280px',
		clickOverlayDismiss: true,
		menuItems: [],
		onDismiss: false,
	};

	var publicMethod = $.fn['wfMobileMenu'] = $['wfMobileMenu'] = function (options) {
		var opts = $.extend({}, defaults, options);

		var overlay = $('<div class="wf-mobile-menu-overlay"></div>').css('opacity', 0);
		if (opts.clickOverlayDismiss) {
			overlay.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				typeof opts.onDismiss === 'function' && opts.onDismiss(false);
				$.wfMobileMenu.close();
			});
		}
		$('body').append(overlay);

		var menu = $('<div class="wf-mobile-menu"><ul class="wf-mobile-menu-items"></ul></div>').css('width', opts.width).css('bottom', '-9999px');
		var itemsWrapper = menu.find('.wf-mobile-menu-items');
		for (var i = 0; i < opts.menuItems.length; i++) {
			var button = $('<li><a href="#" class="wf-btn wf-btn-callout-subtle" role="button"></a></li>');
			button.find('a').text(opts.menuItems[i].title).css('width', opts.width).on('click', null, {action: opts.menuItems[i].action}, function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				typeof opts.onDismiss === 'function' && opts.onDismiss(true);
				$.wfMobileMenu.close();
				e.data.action();
			});
			
			if (opts.menuItems[i].primary) {
				button.find('a').addClass('wf-btn-primary');
			}
			else {
				button.find('a').addClass('wf-btn-default');
			}
			
			if (opts.menuItems[i].disabled) {
				button.find('a').addClass('wf-disabled');
			}
			
			itemsWrapper.append(button);
		}

		var button = $('<li class="wf-padding-add-top-small"><a href="#" class="wf-btn wf-btn-callout-subtle wf-btn-default" role="button">' + __('Close') + '</a></li>');
		button.find('a').css('width', opts.width).on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			typeof opts.onDismiss === 'function' && opts.onDismiss(false);
			$.wfMobileMenu.close();
		});
		itemsWrapper.append(button);
		
		$('body').append(menu);
		menu.css('bottom', '-' + menu.height() + 'px');

		overlay.animate({
			"opacity": 1
		});
		menu.animate({
				bottom: '0px'
			},
			{
				complete: function() {
					typeof opts.onComplete === 'function' && opts.onComplete();
				}
			});
	};

	publicMethod.close = function() {
		var overlay = $('.wf-mobile-menu-overlay');
		overlay.animate({
				"opacity": 0
			},
			{
				complete: function() {
					overlay.remove();
				}
			});

		var menu = $('.wf-mobile-menu');
		menu.animate({
			bottom: '-' + menu.height() + 'px'
			},
			{
				complete: function() {
					menu.remove();
				}
			});
	};
}(jQuery, document, window));

/*! @source https://github.com/eligrey/FileSaver.js/blob/master/dist/FileSaver.min.js */

(function(a,b){if("function"==typeof define&&define.amd)define([],b);else if("undefined"!=typeof exports)b();else{b(),a.FileSaver={exports:{}}.exports}})(this,function(){"use strict";function b(a,b){return"undefined"==typeof b?b={autoBom:!1}:"object"!=typeof b&&(console.warn("Deprecated: Expected third argument to be a object"),b={autoBom:!b}),b.autoBom&&/^\s*(?:text\/\S*|application\/xml|\S*\/\S*\+xml)\s*;.*charset\s*=\s*utf-8/i.test(a.type)?new Blob(["\uFEFF",a],{type:a.type}):a}function c(a,b,c){var d=new XMLHttpRequest;d.open("GET",a),d.responseType="blob",d.onload=function(){g(d.response,b,c)},d.onerror=function(){console.error("could not download file")},d.send()}function d(a){var b=new XMLHttpRequest;b.open("HEAD",a,!1);try{b.send()}catch(a){}return 200<=b.status&&299>=b.status}function e(a){try{a.dispatchEvent(new MouseEvent("click"))}catch(c){var b=document.createEvent("MouseEvents");b.initMouseEvent("click",!0,!0,window,0,0,0,80,20,!1,!1,!1,!1,0,null),a.dispatchEvent(b)}}var f="object"==typeof window&&window.window===window?window:"object"==typeof self&&self.self===self?self:"object"==typeof global&&global.global===global?global:void 0,a=/Macintosh/.test(navigator.userAgent)&&/AppleWebKit/.test(navigator.userAgent)&&!/Safari/.test(navigator.userAgent),g=f.saveAs||("object"!=typeof window||window!==f?function(){}:"download"in HTMLAnchorElement.prototype&&!a?function(b,g,h){var i=f.URL||f.webkitURL,j=document.createElement("a");g=g||b.name||"download",j.download=g,j.rel="noopener","string"==typeof b?(j.href=b,j.origin===location.origin?e(j):d(j.href)?c(b,g,h):e(j,j.target="_blank")):(j.href=i.createObjectURL(b),setTimeout(function(){i.revokeObjectURL(j.href)},4E4),setTimeout(function(){e(j)},0))}:"msSaveOrOpenBlob"in navigator?function(f,g,h){if(g=g||f.name||"download","string"!=typeof f)navigator.msSaveOrOpenBlob(b(f,h),g);else if(d(f))c(f,g,h);else{var i=document.createElement("a");i.href=f,i.target="_blank",setTimeout(function(){e(i)})}}:function(b,d,e,g){if(g=g||open("","_blank"),g&&(g.document.title=g.document.body.innerText="downloading..."),"string"==typeof b)return c(b,d,e);var h="application/octet-stream"===b.type,i=/constructor/i.test(f.HTMLElement)||f.safari,j=/CriOS\/[\d]+/.test(navigator.userAgent);if((j||h&&i||a)&&"undefined"!=typeof FileReader){var k=new FileReader;k.onloadend=function(){var a=k.result;a=j?a:a.replace(/^data:[^;]*;/,"data:attachment/file;"),g?g.location.href=a:location=a,g=null},k.readAsDataURL(b)}else{var l=f.URL||f.webkitURL,m=l.createObjectURL(b);g?g.location=m:location.href=m,g=null,setTimeout(function(){l.revokeObjectURL(m)},4E4)}});f.saveAs=g.saveAs=g,"undefined"!=typeof module&&(module.exports=g)});
