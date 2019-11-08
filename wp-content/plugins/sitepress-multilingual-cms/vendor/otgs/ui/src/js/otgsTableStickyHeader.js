/*global jQuery*/

import stickyTableHeaders from 'sticky-table-headers';

window.addEventListener("DOMContentLoaded", () => {

	/**
	 * @param {NodeList} elementS
	 */
	const elements = [...document.querySelectorAll('.js-otgs-table-sticky-header')];
	const args = {
		fixedOffset: jQuery('#wpadminbar')
	};

	/**
	 * @param {Element} element
	 */
	elements.forEach(element => {
		jQuery(element).stickyTableHeaders(args).on('enabledStickiness.stickyTableHeaders', () => {
			element.getElementsByClassName('tableFloatingHeaderOriginal')[0].style.background = 'rgba(255,255,255,.8)';
		});
	});
});
