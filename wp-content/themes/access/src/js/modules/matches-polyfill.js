'use strict';

/**
 * Polyfill for the Element.matches
 * https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
 */
class Matches {
  /**
   * Class contructor
   */
  constructor() {
    /* eslint-disable no-undef */
    if (!Element.prototype.matches) {
      Element.prototype.matches = Element.prototype.msMatchesSelector;
    }
    /* eslint-enable no-undef */
  }
}

export default Matches;
