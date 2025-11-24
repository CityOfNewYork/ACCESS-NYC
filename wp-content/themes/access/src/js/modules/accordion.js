'use strict';

import Toggle from 'modules/accordion-toggle';

/**
 * The Accordion module
 * @class
 */
class Accordion {
  /**
   * @constructor
   *
   * @return {object} The class
   */
  constructor() {
    this._toggle = new Toggle({
      selector: Accordion.selector
    });

    return this;
  }
}

/**
 * The dom selector for the module
 * @type {String}
 */
Accordion.selector = '[data-js*="accordion"]';

export default Accordion;