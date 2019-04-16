/* eslint-env browser */

'use strict';

import $ from 'jquery';
import _ from 'underscore';

/**
 * Creates a tooltip. The constructor is passed an HTML element that serves as
 * the trigger to show or hide the tooltip. The tooltip should have an
 * `aria-describedby` attribute, the value of which is the ID of the tooltip
 * content to show or hide.
 */
class Tooltip {
  /**
   * @param {HTMLElement} el - The trigger element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElment} The triggering HTML element. */
    this._trigger = el;

    /** @private {HTMLElement} The tooltip element. */
    this._tooltip = document.getElementById($(el).attr('aria-describedby'));

    /** @private {boolean} Whether the tooltip is visible. */
    this._active = false;
  }

  /**
   * Sets event listeners, decorates the tooltip element, and appends the
   * tooltip to the body to avoid positioning issues.
   * @method
   * @return {this} Tooltip
   */
  init() {
    $(this._tooltip).addClass(`${Tooltip.CssClass.TOOLTIP}
        ${Tooltip.CssClass.HIDDEN}`).attr({
          'aria-hidden': true,
          'role': 'tooltip'
        }).on('click', e => {
          // Stop click propagation so clicking on the tip doesn't trigger a
          // click on body, which would close the tooltip.
          e.stopPropagation();
        }).detach().appendTo('body');
    $(this._trigger).on('click', e => {
      e.preventDefault();
      e.stopPropagation();
      this.toggle();
    });
    Tooltip.AllTips.push(this);
    return this;
  }

  /**
   * Displays the tooltip. Sets a one-time listener on the body to close the
   * tooltip when a click event bubbles up to it.
   * @method
   * @return {this} Tooltip
   */
  show() {
    Tooltip.hideAll();
    $(this._tooltip).removeClass(Tooltip.CssClass.HIDDEN)
        .attr('aria-hidden', false);
    $('body').one('click.tooltip', () => {
      this.hide();
    });
    $(window).on('resize.tooltip', _.debounce(() => {
      this.reposition();
    }, 200));
    this.reposition();
    this._active = true;
    return this;
  }

  /**
   * Hides the tooltip and removes the click event listener on the body.
   * @method
   * @return {this} Tooltip
   */
  hide() {
    $(this._tooltip).addClass(Tooltip.CssClass.HIDDEN)
        .attr('aria-hidden', true);
    $('body').off('click.tooltip');
    this._active = false;
    return this;
  }

  /**
   * Toggles the state of the tooltip.
   * @method
   * @return {this} Tooltip
   */
  toggle() {
    if (this._active) {
      this.hide();
    } else {
      this.show();
    }
    return this;
  }

  /**
   * Positions the tooltip beneath the triggering element.
   * @method
   * @return {this} Tooltip
   */
  reposition() {
    const positioning = {
      'left': 'auto',
      'position': 'absolute',
      'right': 'auto',
      'top': 'auto',
      'width': ''
    };
    // TODO(jjandoc): For RTL languages, we should make the default right
    // alignment. Right now, the default is left alignment.
    // const isRTL = $('html').attr('dir') === 'rtl';

    // Reset positioning.
    $(this._tooltip).css(positioning);

    const triggerOffset = $(this._trigger).offset();
    const tooltipWidth = $(this._tooltip).outerWidth();
    const viewportWidth = $(window).innerWidth();
    const gutter = 15; // Minimum distance from screen edge.

    const topPos = triggerOffset.top + $(this._trigger).outerHeight();
    let leftPos = 'auto';
    let rightPos = 'auto';

    // Determine left or right alignment.
    // If the tooltip is wider than the screen minus gutters, then position
    // the tooltip to extend to the gutters.
    if (tooltipWidth >= viewportWidth - (2 * gutter)) {
      leftPos = `${gutter}px`;
      rightPos = `${gutter}px`;
      positioning.width = 'auto';
    } else if (triggerOffset.left + tooltipWidth + gutter > viewportWidth) {
    // If the tooltip, when left aligned with the trigger, would cause the
    // tip to go offscreen (determined by taking the trigger left offset and
    // adding the tooltip width and the left gutter) then align the tooltip
    // to the right side of the trigger element.
      leftPos = 'auto';
      rightPos = viewportWidth -
          (triggerOffset.left + $(this._trigger).outerWidth()) + 'px';
    } else {
    // Align the tooltip to the left of the trigger element.
      leftPos = `${triggerOffset.left}px`;
      rightPos = 'auto';
    }

    // Set styling positions, reversing left and right if this is an RTL
    // language.
    positioning.left = leftPos;
    positioning.right = rightPos;
    positioning.top = topPos;
    $(this._tooltip).css(positioning);

    return this;
  }
}

/**
 * Array of all the instantiated tooltips.
 * @type {Array<Tooltip>}
 */
Tooltip.AllTips = [];

/**
 * Hide all Tooltips.
 * @public
 */
Tooltip.hideAll = function() {
  _.each(Tooltip.AllTips, tip => {
    tip.hide();
  });
};

/**
 * CSS classes used by this component.
 * @enum {string}
 */
Tooltip.CssClass = {
  HIDDEN: 'hidden',
  TOOLTIP: 'tooltip-bubble',
  TRIGGER: 'js-tooltip-trigger'
};

export default Tooltip;
