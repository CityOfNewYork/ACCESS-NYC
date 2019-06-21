/**
 * GatherContent Plugin - v3.1.13 - 2019-05-22
 * http://www.gathercontent.com
 *
 * Copyright (c) 2019 GatherContent
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

module.exports = function log() {
	var method = 'log';

	if (arguments[0] in window.console) {
		method = Array.prototype.shift.apply(arguments);
	}

	log.history = log.history || [];
	log.history.push(arguments);

	if (window.console && this.debug) {
		window.console[method].apply(window.console, arguments);
	}
};

},{}],2:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.el = function (id) {
		return document.getElementById(id);
	};

	gc.$id = function (id) {
		return $(gc.el(id));
	};

	gc.log = require('./log.js').bind(gc);

	var main = gc.main = {};

	main.init = function () {
		$(document.body).on('click', '.gc-nav-tab-wrapper:not( .gc-nav-tab-wrapper-bb ) .nav-tab', main.changeTabs).on('click', '.gc-reveal-items', main.maybeReveal);

		if (gc.queryargs.mapping) {
			var $menu = gc.$id('toplevel_page_gathercontent-import');
			$menu.find('.current').removeClass('current');
			$menu.find('[href="edit.php?post_type=gc_templates"]').parent().addClass('current');
		}
	};

	main.changeTabs = function (evt) {
		evt.preventDefault();

		main.$tabNav = main.$tabNav || $('.gc-nav-tab-wrapper .nav-tab');
		main.$tabs = main.$tabs || $('.gc-template-tab');

		main.$tabNav.removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		main.$tabs.addClass('hidden');
		gc.$id($(this).attr('href').substring(1)).removeClass('hidden');
	};

	main.maybeReveal = function (evt) {
		var $this = $(this);
		evt.preventDefault();

		if ($this.hasClass('dashicons-arrow-right')) {
			$this.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
			$this.next().removeClass('hidden');
		} else {
			$this.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
			$this.next().addClass('hidden');
		}
	};

	$(main.init);
})(window, document, jQuery, window.GatherContent);

},{"./log.js":1}]},{},[2]);
