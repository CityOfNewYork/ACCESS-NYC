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

		escapeHTML: function(str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;');
		},

		/**
		 * Closes the current standalone modal dialog. Supports being used directly as an event handler.
		 *
		 * @param event (optional)
		 */
		closeStandaloneModal: function(event) {
			event && event.preventDefault();
			event && event.stopPropagation();
			WFLS.currentDialog && WFLS.currentDialog.dialog('close');
		},

		/**
		 * Displays a modal dialog with fixed HTML content using built-in WP functionality. This is intended for
		 * use by content that may not have the Vue runtime available.
		 *
		 * @param html
		 * @param settings
		 */
		standaloneModalHTML: function(html, settings) {
			if (!settings) { settings = {}; }
			WFLS.currentDialog && WFLS.currentDialog.dialog('close');
			WFLS.currentDialog = $('<div>', { html: html }).dialog({
				modal: true,
				width: settings.width ? settings.width : (WFLS.screenSize(500) ? 300 : 400),
				resizable: false,
				draggable: false,
				zIndex: 9998,
				close: function () {
					$(this).dialog('destroy').remove();
					WFLS.currentDialog = false;
					typeof settings.onClose === 'function' && settings.onClose();
				},
				open: function () {
					var dialog = $(this);
					var widget = dialog.dialog('widget');
					var instance = dialog.dialog('instance');

					widget.attr('id', 'wfls-standalone-modal').css('z-index', 9999);
					instance && instance.overlay && instance.overlay.attr('id', 'wfls-standalone-modal-overlay');

					typeof settings.onOpen === 'function' && settings.onOpen(this);
				}
			});
		},

		standaloneModal: function(heading, body, settings) {
			if (!settings) { settings = {}; }
			var html =
				'<div class="wfls-modal">' +
					'<div class="wfls-modal-header">' +
					'<div class="wfls-modal-header-content">' +
						'<div class="wfls-modal-title">' +
							'<strong>' + (typeof heading === 'object' ? heading.html : WFLS.escapeHTML(heading)) + '</strong>' +
						'</div>' +
					'</div>' +
					'<div class="wfls-modal-header-action"></div>' +
					'</div>' +
					'<div class="wfls-modal-content">' +
						(typeof body === 'object' ? body.html : WFLS.escapeHTML(body)) +
					'</div>' +
					'<div class="wfls-modal-footer">' +
						'<ul class="wfls-flex-horizontal wfls-flex-align-right wfls-full-width">' +
							'<li class="wfls-padding-add-left-small"><a href="#" class="wfls-btn wfls-btn-primary wfls-btn-callout-subtle wfls-generic-close-btn">' + __('Close') + '</a></li>' +
						'</ul>' +
					'</div>' +
				'</div>';

			var originalOpen = settings.onOpen;
			settings.onOpen = function(dialog) {
				$(dialog).find('.wfls-generic-close-btn').on('click', WFLS.closeStandaloneModal);
				typeof originalOpen === 'function' && originalOpen(dialog);
			}
			WFLS.standaloneModalHTML(html, settings)
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
