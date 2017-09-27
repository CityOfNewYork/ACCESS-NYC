/* eslint-env browser */
'use strict';

import _ from 'underscore';
import DataBinding from 'lib/data-binding';

/**
 * This component is the object class for a "household" in the screener.
 * @class
 */
class ScreenerHousehold extends DataBinding {
  /**
   * @param {string} name      - the dom element to bind to the model
   * @param {?object} obj      - initial attributes to set.
   * @param {object} callbacks - collection of functions to hook into
   *                             data-binding
   * @constructor
   */
  constructor(name, obj, callbacks) {
    super(name, callbacks);

    /** @private {object} The attributes that are exposed to Drools. */
    this._attrs = {
      /** @type {string} */
      zip: '',
      /** @type {string} can be blank string or 'NYC' */
      city: '',
      /** @type {Number} must be an integer */
      members: 1,
      /** @type {Number} */
      cashOnHand: 0,
      /** @type {string} must be one of ScreenerHousehold.RENTAL_TYPE or
        empty string */
      livingRentalType: '',
      /** @type {boolean} */
      livingRenting: false,
      /** @type {boolean} */
      livingOwner: false,
      /** @type {boolean} */
      livingStayingWithFriend: false,
      /** @type {boolean} */
      livingHotel: false,
      /** @type {boolean} */
      livingShelter: false,
      /** @type {boolean} */
      livingPreferNotToSay: false
    };

    this._defaults = this._attrs;

    if (obj) {
      this.set(obj);
    }
  }

  /**
   * If supplied param is an object, sets this._attrs values, matching keys.
   * If supplied params are a string and a second value, the string matches
   * the key and applies the second value.
   * @method
   * @param {object|string} param - Object of attributes, or a key for an
   *   individual attribute
   * @param {?string|number|boolean|array} value - Optional value to set.
   * @return {this} ScreenerPerson
   */
  set(param, value) {
    if (_.isObject(param)) {
      for (let key in param) {
        if (Object.prototype.hasOwnProperty.call(param, key)) {
          this._setAttr(key, param[key]);
        }
      }
    } else {
      this._setAttr(param, value);
    }
    return this;
  }

  /**
   * Returns the value of a given key in this._attrs.
   * @method
   * @param {string} key
   * @return {string|number|boolean|array} value
   */
  get(key) {
    const value = (key in this._attrs) ? this._attrs[key] : null;
    return value;
  }

  /**
   * Sets an individual attribute, matching for type.
   * @param {string} key
   * @param {string|number|boolean|array} value
   */
  setAttr(key, value) {
    console.dir([key, value]);
    if (key in this._attrs && typeof this._attrs[key] === typeof value) {
      if (key === 'city' && value !== 'NYC') {
        this._attrs[key] = '';
        value = '';
      } else if (key == 'livingRentalType' &&
          ScreenerHousehold.RENTAL_TYPE.indexOf(value) <= -1) {
        this._attrs[key] = '';
        value = '';
      } else {
        this._attrs[key] = value;
      }
    }
  }

  /**
   * Returns the value of this._attrs as an object.
   * @method
   * @return {object} this._attrs
   */
  toObject() {
    return this._attrs;
  }
}

/**
 * Valid renatl types.
 * @type {Array<string>}
 */
ScreenerHousehold.RENTAL_TYPE = [
  'NYCHA',
  'MarketRate',
  'RentControlled',
  'RentRegulatedHotel',
  'Section213',
  'LimitedDividendDevelopment',
  'MitchellLama',
  'RedevelopmentCompany',
  'HDFC',
  'FamilyHome',
  'Condo'
];

export default ScreenerHousehold;
