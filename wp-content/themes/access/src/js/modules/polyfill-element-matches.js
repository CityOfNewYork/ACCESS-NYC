'use strict';

/**
 * Polyfill for Element.prototype.matches()
 * https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
 */
class Matches {
  /**
   * Class contructor
   */
  constructor() {
    /* eslint-disable no-undef */
    if (!Element.prototype.matches) {
      Element.prototype.matches =
        Element.prototype.matchesSelector ||
        Element.prototype.mozMatchesSelector ||
        Element.prototype.msMatchesSelector ||
        Element.prototype.oMatchesSelector ||
        Element.prototype.webkitMatchesSelector ||
        function(s) {
          let matches = (this.document || this.ownerDocument)
            .querySelectorAll(s);
          let i = matches.length;
          // eslint-disable-next-line no-empty
          while (--i >= 0 && matches.item(i) !== this) {}
          return i > -1;
        };
    }
    /* eslint-enable no-undef */
  }
}

new Matches();
