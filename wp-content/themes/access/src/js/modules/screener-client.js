/* eslint-env browser */
'use strict';

import _ from 'underscore';

/**
 * This component is the object class for screener individuals.
 * @class
 */
class ScreenerClient {
  /**
   * @param {?object} obj - initial attributes to set.
   * @constructor
   */
  constructor(obj) {
    /** @private {object} The attributes that are exposed to Drools. */
    this._attrs = {
      /** @type {string} */
      firstName: '',
      /** @type {string} */
      lastName: '',
      /** @type {array} */
      language: [],
      /** @type {string} */
      languageOther: '',
      /** @type {string} */
      phone: '',
      /** @type {string} */
      phoneType: '',
      /** @type {string} */
      phoneAlt: '',
      /** @type {string} */
      phoneAltType: '',
      /** @type {array} */
      contactTimes: [],
      /** @type {string} */
      email: '',
      /** @type {string} */
      streetNumber: '',
      /** @type {string} */
      streetName: '',
      /** @type {string} */
      unit: '',
      /** @type {string} */
      borough: '',
      /** @type {boolean} */
      currentClient: false,
      /** @type {string} */
      caseNumber: ''
    };
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
   * @return {this} ScreenerClient
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
   * Sets an individual attribute, matching for type.
   * @private
   * @param {string} key
   * @param {string|number|boolean|array} value
   */
  _setAttr(key, value) {
    if (key in this._attrs && typeof this._attrs[key] === typeof value) {
      this._attrs[key] = value;
    }
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
   * Returns the value of this._attrs as an object.
   * @method
   * @return {object} this._attrs
   */
  toObject() {
    return this._attrs;
  }
}

ScreenerClient.LANGUAGE = [
  'en',
  'sp',
  'other'
];

ScreenerClient.PHONE_TYPE = [
  'cell',
  'home',
  'work'
];

ScreenerClient.CONTACT_TIMES = [
  'morning',
  'noon',
  'afternoon',
  'evening',
  'saturday'
];

ScreenerClient.BOROUGH = [
  'manhattan',
  'bronx',
  'queens',
  'brooklyn',
  'statenIsland'
];

export default ScreenerClient;
