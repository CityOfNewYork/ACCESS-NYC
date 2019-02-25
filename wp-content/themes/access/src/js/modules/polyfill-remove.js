'use strict';

/**
 * Polyfill for Element.prototype.remove()
 * https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/remove#Polyfill
 */
class Remove {
  /**
   * Class contructor
   */
  constructor() {
    /* eslint-disable no-undef */
    (function(arr) {
      arr.forEach(function(item) {
        if (item.hasOwnProperty('remove')) {
          return;
        }
        Object.defineProperty(item, 'remove', {
          configurable: true,
          enumerable: true,
          writable: true,
          value: function remove() {
            if (this.parentNode !== null)
              this.parentNode.removeChild(this);
          }
        });
      });
    })([
      Element.prototype,
      CharacterData.prototype,
      DocumentType.prototype
    ]);
    /* eslint-enable no-undef */
  }
}

export default Remove;
