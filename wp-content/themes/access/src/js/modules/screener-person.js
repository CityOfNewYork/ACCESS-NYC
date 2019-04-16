/* eslint-env browser */
'use strict';

import _ from 'underscore';

/**
 * This component is the object class for screener individuals.
 * @class
 */
class ScreenerPerson {
  /**
   * @param {?object} obj - initial attributes to set.
   * @constructor
   */
  constructor(obj) {
    /** @private {object} The attributes that are exposed to Drools. */
    this._attrs = {
      /** @type {Number} must be an integer */
      age: 0,
      /** @type {boolean} is this person the applicant or not */
      applicant: false,
      /** @type {array<object>} */
      incomes: [],
      /** @type {array<object>} */
      expenses: [],
      /** @type {boolean} */
      student: false,
      /** @type {boolean} */
      studentFulltime: false,
      /** @type {boolean} */
      pregnant: false,
      /** @type {boolean} */
      unemployed: false,
      /** @type {boolean} */
      unemployedWorkedLast18Months: false,
      /** @type {boolean} */
      blind: false,
      /** @type {boolean} */
      disabled: false,
      /** @type {boolean} */
      veteran: false,
      /** @type {boolean} */
      benefitsMedicaid: false,
      /** @type {boolean} */
      benefitsMedicaidDisability: false,
      /** @type {boolean} */
      headOfHousehold: false,
      /** @type {string} */
      headOfHouseholdRelation: '',
      /** @type {boolean} */
      livingOwnerOnDeed: false,
      /** @type {boolean} */
      livingRentalOnLease: false
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
   * Returns an object of just the condition key/value pairs.
   * @method
   * @return {object<boolean>} condition attributes
   */
  getConditions() {
    const conditionKeys = [
      'student',
      'studentFulltime',
      'pregnant',
      'unemployed',
      'unemployedWorkedLast18Months',
      'blind',
      'disabled',
      'veteran'
    ];
    const obj = {};
    _.each(conditionKeys, key => {
      obj[key] = this.get(key);
    });
    return obj;
  }

  /**
   * Returns an object of just the benefit key/value pairs.
   * @method
   * @return {object<boolean>} benefit attributes
   */
  getBenefits() {
    const benefitKeys = [
      'benefitsMedicaid',
      'benefitsMedicaidDisability'
    ];
    const obj = {};
    _.each(benefitKeys, key => {
      obj[key] = this.get(key);
    });
    return obj;
  }

   /**
    * Adds an income item to this._attrs.incomes.
    * @method
    * @param {number|string} amount
    * @param {string} type
    * @param {string} frequency
    * @return {this} ScreenerPerson
    */
  addIncome(amount, type, frequency) {
    const obj = {
      amount: parseFloat(amount),
      type: type,
      frequency: frequency
    };

    if ((_.isNumber(obj.amount) && !_.isNaN(obj.amount)) &&
        ScreenerPerson.INCOME.indexOf(obj.type) >= 0 &&
        ScreenerPerson.FREQUENCY.indexOf(obj.frequency) >= 0) {
      this._attrs.incomes.push(obj);
    }
    return this;
  }

  /**
   * Adds an expense item to this._attrs.expenses.
   * @method
   * @param {number|string} amount
   * @param {string} type
   * @param {string} frequency
   * @return {this} ScreenerPerson
   */
  addExpense(amount, type, frequency) {
    const obj = {
      amount: parseFloat(amount),
      type: type,
      frequency: frequency
    };

    if ((_.isNumber(obj.amount) && !_.isNaN(obj.amount)) &&
        ScreenerPerson.EXPENSE.indexOf(obj.type) >= 0 &&
        ScreenerPerson.FREQUENCY.indexOf(obj.frequency) >= 0) {
      this._attrs.expenses.push(obj);
    }
    return this;
  }

  /**
   * [addPayment description]
   * @param {string} key  - expenses or incomes
   * @param {string} type - type of expenses or income
   * @return {this} ScreenerPerson
   */
  addPayment(key, type) {
    const obj = {type: type, amount: '', frequency: ''};
    let valid = false;

    switch (key) {
      case 'expenses':
        valid = (ScreenerPerson.EXPENSE.indexOf(obj.type) >= 0) ? true : valid;
        break;
      case 'incomes':
        valid = (ScreenerPerson.INCOME.indexOf(obj.type) >= 0) ? true : valid;
        break;
    }

    if (valid) this._attrs[key].push(obj);

    return this;
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

ScreenerPerson.INCOME = [
  'Wages',
  'SelfEmployment',
  'Unemployment',
  'CashAssistance',
  'ChildSupport',
  'DisabilityMedicaid',
  'SSI',
  'SSDependent',
  'SSDisability',
  'SSSurvivor',
  'SSRetirement',
  'NYSDisability',
  'Veteran',
  'Pension',
  'DeferredComp',
  'WorkersComp',
  'Alimony',
  'Boarder',
  'Gifts',
  'Rental',
  'Investment'
];

ScreenerPerson.EXPENSE = [
  'ChildCare',
  'ChildSupport',
  'DependentCare',
  'Rent',
  'Medical',
  'Heating',
  'Cooling',
  'Mortgage',
  'Utilities',
  'Telephone',
  'InsurancePremiums'
];

ScreenerPerson.FREQUENCY = [
  'weekly',
  'biweekly',
  'monthly',
  'semimonthly',
  'yearly'
];

/**
 * Attributes for retrieving person's condition
 * @type {Array}
 */
ScreenerPerson.CONDITION_ATTRS = [
  'student',
  'studentFulltime',
  'pregnant',
  'unemployed',
  'unemployedWorkedLast18Months',
  'blind',
  'disabled',
  'veteran'
];

/**
 * Attributes for retrieving a person's benefits
 * @type {Array}
 */
ScreenerPerson.BENEFIT_ATTRS = [
  'benefitsMedicaid',
  'benefitsMedicaidDisability'
];

export default ScreenerPerson;
