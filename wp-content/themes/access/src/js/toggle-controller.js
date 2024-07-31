/* eslint-env browser */
'use strict';

import Toggle from 'utilities/toggle/toggle';

/**
 * This controls the translate text module
 *
 * @class
 */
class TextController {
  /**
   * @param {Object} el  The html element for the component
   *
   * @constructor
   */
  constructor(el) {
    /** @var {Object} el  The component element. */
    this.el = el;

    /** @var {Object} _toggle  The toggle instance for the Text Controller */
    this._toggle = new Toggle({
      selector: TextController.selectors.TOGGLE
    });

    return this;
  }
}

/** @type {String} The component selector */
TextController.selector = '[data-js="text-controller"]';

/** @type {Object} element selectors within the component */
TextController.selectors = {
  TOGGLE: '[data-js*="text-controller__control"]'
};

export default TextController;