import tippy from 'tippy.js';
import '../scss/otgsPopoverTooltip.scss';

window.addEventListener("DOMContentLoaded", () => {
	initialize();
});

/**
 *
 * @param {Element} element
 */
export function initSingle(element) {
	/**
	 * @see https://atomiks.github.io/tippyjs/#all-options
	 * @type {{arrow: boolean, theme: string, animation: string, sticky: boolean, interactive: boolean}}
	 */
	const args = {
		arrow:       true,
		theme:       'otgs',
		animation:   'fade',
		sticky:      true,
		interactive: true,
	};
	if ( !element.getAttribute('data-tippy-content') && element.getAttribute('title') ) {
		args.content = element.getAttribute('title');
		element.removeAttribute('title');
	}
	tippy(element, args);
}

export function initialize() {
	/**
	 * @param {NodeList} elements
	 */
		//TODO change all .wpml-popover to otgs-popover
	const elements = [...document.querySelectorAll('.js-otgs-popover-tooltip, .js-wpml-popover-tooltip')];

	/**
	 * @param {Element} element
	 */
	elements.forEach(element => initSingle(element));
}
