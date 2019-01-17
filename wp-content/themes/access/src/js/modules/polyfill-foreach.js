'use strict';

/**
 * Polyfill for Array.prototype.forEach()
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/forEach#Polyfill
 */
class ForEach {
  /**
   * Class contructor
   */
  constructor() {
    /* eslint-disable no-undef, no-extend-native, prefer-rest-params */
    if (!Array.prototype.forEach) {
      Array.prototype.forEach = function(callback) {
        let T;
        let k;

        if (this == null) {
          throw new TypeError('this is null or not defined');
        }

        let O = Object(this);
        let len = O.length >>> 0;
        if (typeof callback !== 'function') {
          throw new TypeError(callback + ' is not a function');
        }

        if (arguments.length > 1) {
          T = arguments[1];
        }

        k = 0;

        while (k < len) {
          let kValue;
          if (k in O) {
            kValue = O[k];
            callback.call(T, kValue, k, O);
          }
          k++;
        }
      };
    }
    /* eslint-enable no-undef */
  }
}

export default ForEach;
