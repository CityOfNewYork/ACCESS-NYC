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
		$(document.body)
			.on('click', '.gc-nav-tab-wrapper:not( .gc-nav-tab-wrapper-bb ) .nav-tab', main.changeTabs)
			.on('click', '.gc-reveal-items', main.maybeReveal)
			.on('click', '.gc-reveal-items-component', main.maybeRevealComponent);

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

	/**
	 * Accordion Toggle > Template Mapping: Field Description
	 * - Opens the drawer for a single field's description
	 */
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

	/**
	 * Accordion Toggle > Template Mapping: Component Fields
	 * - Opens the drawer for the component's description and subfields
	 */
	main.maybeRevealComponent = function (evt) {
		var $this = $(this);
		evt.preventDefault();

		if ($this.hasClass('dashicons-arrow-right')) {
			$this.closest('table').find('.gc-component-row').addClass('hidden');
		} else {
			$this.closest('table').find('.gc-component-row').removeClass('hidden');
		}
	};

	$(main.init);

	window.onload = function () {
		var textarea = jQuery('#system-info-textarea');
		if (textarea.length) {
			textarea.css('height', jQuery(window).height() * 0.7 + 'px');
		}
	};

	document.addEventListener('DOMContentLoaded', function () {
		if (typeof redirectData !== 'undefined' && redirectData.redirectUrl) {
			window.location = redirectData.redirectUrl;
		}
	});

})(window, document, jQuery, window.GatherContent);
