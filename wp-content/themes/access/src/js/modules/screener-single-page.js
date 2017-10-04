/* eslint-env browser */
'use strict';

import $ from 'jquery';
// import Cookies from 'js-cookie';
import ScreenerHousehold from 'modules/screener-household';
import ScreenerPerson from 'modules/screener-person';
import ScreenerClient from 'modules/screener-client';
import ScreenerStaff from 'modules/screener-staff';

import Shared from 'modules/screener';

import Utility from 'modules/utility';

import _ from 'underscore';
import Vue from 'vue/dist/vue.common';
import Validator from 'vee-validate';

import Cleave from 'cleave.js/dist/cleave.min';

/**
 * Requires Documentation
 * @class
 */
class ScreenerSinglePage {
  /**
   * @param {HTMLElement} el - The form element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;

    /** @private {boolean} Whether the google reCAPTCHA widget is required. */
    this._recaptchaRequired = false;

    /** @private {boolean} Whether the google reCAPTCHA widget has passed. */
    this._recaptchaVerified = false;
  }

  /**
   * If this component has not yet been initialized, attaches event listeners.
   * @method
   * @return {this} OfficeMap
   */
  init() {

    if (this._initialized) {
      return this;
    }

    Validator.Validator.extend('zip', ScreenerSinglePage.validateZipField);

    Vue.use(Validator, {events: 'blur', zip: 'zip'});

    Vue.component('personlabel', ScreenerSinglePage.personLabel);

    const vue = new Vue({
      'delimiters': ['v{', '}'],
      'el': '#vue',
      'data': {
        /* these are modules required for the ACCESS NYC screener */
        'people': [new ScreenerPerson({headOfHousehold: true})],
        'household': new ScreenerHousehold(),
        'categories': [],
        /* these are modules created for the public engagement unit */
        'client': new ScreenerClient(),
        'staff': new ScreenerStaff(),
        'categoriesCurrent': [],
        'disclaimer': false,
        'expenses': []
      },
      'methods': {
        'resetAttr': ScreenerSinglePage.resetAttr,
        'setAttr': ScreenerSinglePage.setAttr,
        'populate': ScreenerSinglePage.populate,
        'pushPayment': ScreenerSinglePage.pushPayment,
        'getPayment': ScreenerSinglePage.getPayment,
        'push': ScreenerSinglePage.push,
        'checked': ScreenerSinglePage.checked,
        'singleOccupant': ScreenerSinglePage.singleOccupant,
        'recap': function() {
          ScreenerSinglePage.renderRecap(this);
        },
      }
    });

    window.addEventListener('hashchange', (e) => {
      const hash = window.location.hash;
      const $section = $(hash);
      const type = window.location.hash.split('-')[0];
      if (type === '#page') {
        this._goToPage(hash);
      }
    });

    let $el = $(this._el);

    $el.on('click', `.${ScreenerSinglePage.CssClass.SUBMIT}`, (e) => {
      if (!this._recaptchaRequired) {
        this._submit($(e.currentTarget).data('action'));
      } else {
        $(e.currentTarget).closest(`.${ScreenerSinglePage.CssClass.STEP}`)
          .find(`.${ScreenerSinglePage.CssClass.ERROR_MSG}`).remove();
        if (this._recaptchaVerified) {
          this._submit($(e.currentTarget).data('action'));
        } else {
          this._showError($('#screener-recaptcha')[0],
              ScreenerSinglePage.ErrorMessage.REQUIRED);
        }
      }
    })

    $el.on(
      'change', `.${ScreenerSinglePage.CssClass.TOGGLE}`, this._handleToggler
    );

    // Floats
    $el.on('focus', '[data-type="float"]', this._sanitizeDollarFloat);
    $el.on('keydown', '[data-type="float"]', this._limitDollarFloat);
    $el.on('keydown', '[data-type="float"]', this._enforceFloat);

    // Numbers
    $el.on('keydown', 'input[type="number"]', this._enforceNumbersOnly);

    // Max Length
    $el.on('keydown', 'input[maxlength]', this._enforceMaxLength);

    // $el.on('submit', (event) => {
    //   event.preventDefault();
    //   this._$steps.filter(`.${ScreenerSinglePage.CssClass.ACTIVE}`)
    //     .find(`.${ScreenerSinglePage.CssClass.VALIDATE_STEP},` +
    //     `.${ScreenerSinglePage.CssClass.SUBMIT}`).trigger('click');
    // });

    $el.on('click', '[data-js="question"]', (event) => {
      this._goToQuestion(event, event.currentTarget.hash);
    });

    window.location.hash = 'page-admin';

    this._goToPage('#page-admin');

    return this;
  }

  /**
   * For a given element with a maxlength attribute, enforce the maxlength rule.
   * This is necessary because input[type="number"] elements ignrore the
   * attribute natively.
   * [_enforceMaxLength description]
   * @param  {object} event the keypup event object
   * @return {null}
   */
  _enforceMaxLength(event) {
    let $input = $(event.currentTarget);
    let maxlength = parseInt($input.attr('maxlength'), 10);
    let val = $input.val();
    if (val.length === maxlength) {
      // if key code isn't a backspace or is a spacebar
      if (event.keyCode !== 8 || event.keyCode === 32) event.preventDefault();
    }
  }

  /**
   * Number inputs still allow certain characters outside of 0-9.
   * @param  {object} event the keypup event object
   * @return {null}
   */
  _enforceNumbersOnly(event) {
    if (
      event.keyCode === 69 || // 'e' key, used for scientific notation
      event.keyCode === 187 || // '=' key (for the '+' sign)
      event.keyCode === 188 || // ',' key
      event.keyCode === 189 // '-' key
    ) {
      event.preventDefault();
    }
  }

  /**
   * Limits key input to numbers and decimals
   * @param  {event} the keydown input event
   * @return [null]
   */
  _enforceFloat(event) {
    let block = true;
    let value = event.currentTarget.value;

    if (
      (event.keyCode >= 48 && event.keyCode <= 57) || // key board
      (event.keyCode >= 96 && event.keyCode <= 105) || // key pad
      event.keyCode === 190 // "." period
    ) {
      block = false;
      if (value.indexOf('.') > -1) {
        let split = value.split('.');
        if (split[1].length == 2){
          block = true;
        }
      }
    }

    // Backspace
    if (event.keyCode === 8) {
      block = false;
    }

    if (block) event.preventDefault();
  }

  /**
   * Format number value and make sure it has '.00', uses cleave for input
   * masking
   * @param  {object} event the blur event object
   * @return {null}
   */
  _sanitizeDollarFloat(event) {
    new Cleave(event.currentTarget, {
      delimiter: '',
      numeral: true,
      numeralPositiveOnly: true
    });

    event.currentTarget.addEventListener('blur', function(event) {
      let value = event.currentTarget.value;
      let postfix = '';
      if (value.indexOf('.') > -1) {
        let split = value.split('.');
        postfix = (split[1].length == 1) ? '0' : postfix;
        postfix = (split[1].length == 0) ? '00' : postfix;
        event.currentTarget.value += postfix;
      } else if (value != '') {
        event.currentTarget.value += '.00';
      }
    });
  }

  /**
   * For a given dollar float input, product requirements dictate we should
   * limit values to 6 digits before the decimal point and 2 after.
   * @param  {object} event the blur event object
   * @return {null}
   */
  _limitDollarFloat(event) {
    let value = event.currentTarget.value;
    let block = false;

    // if there is a decimal...
    if (value.indexOf('.') > -1) {
      // and the value length is 8 digits + 1 decimal...
      if (value.length === 9) {
        // and the key pressed isn't the backspace...
        block = (event.keyCode !== 8) ? true : block;
      }
    // if the value length is 6 digits...
    } else if (value.length === 6) {
      // and the key pressed isn't the backspace or '.' ...
      block = (event.keyCode !== 8 && event.keyCode !== 190) ? true : block;
    }

    if (block) event.preventDefault(); // stop input
  }

  /**
   * [_goToPage description]
   * @param  {[type]} section [description]
   * @return {this} Screener
   */
  _goToPage(page) {

    let $window = document.querySelector('#js-layout-body');

    $window.scrollTop = 0;

    $(`.${ScreenerSinglePage.CssClass.PAGE}`)
      .removeClass(ScreenerSinglePage.CssClass.ACTIVE)
      .attr('aria-hidden', 'true')
      .find(':input, a')
      .attr('tabindex', '-1');

    $(page).addClass(ScreenerSinglePage.CssClass.ACTIVE)
      .removeAttr('aria-hidden')
      .find(':input, a')
      .removeAttr('tabindex');

    return this;
  }

  /**
   * Jumps to screener question
   * @param  {string} hash The question's hash id
   * @return {this} Screener
   */
  _goToQuestion(event, hash) {
    let page = '#' + $(hash).closest(`.${ScreenerSinglePage.CssClass.PAGE}`).attr('id');
    let $questions = $(`.${ScreenerSinglePage.CssClass.TOGGLE_QUESTION}`);
    let $target = $(hash).find(`.${ScreenerSinglePage.CssClass.TOGGLE_QUESTION}`);
    let target = document.querySelector(hash);
    let $window = document.querySelector('#js-layout-body');

    if (!$(page).hasClass('active')) {
      window.location.hash = page;
    }

    if (!$target.hasClass('active')) {
      $questions
        .addClass('hidden')
        .removeClass('active')
        .prop('aria-hidden', true);
      $target
        .addClass('active')
        .removeClass('hidden')
        .prop('aria-hidden', false);

      // Scrolling Behavior
      event.preventDefault();
      target.scrollIntoView(true);
      $window.scrollBy({
        top: -60,
        left: 0,
        behavior: 'auto'
      });
    } else {
      $target
        .addClass('hidden')
        .removeClass('active')
        .prop('aria-hidden', true);

      // Scrolling Behavior
      event.preventDefault();
    }

    return this;
  }

  /**
   * For a given input, if the input has a value and can be coerced to a
   * positive integer then enforce that. Otherwise, show an error message.
   * @param {HTMLELement} el - Input element to validate.
   * @return {this} Screener
   */
  // _validateIntegerField(el) {
  //   const $input = $(el);
  //   const val = $input.val();
  //   this._removeError(el);

  //   // If there is a value for the element, make sure that it is rounded to
  //   // an integer and not negative.
  //   if (val && !_.isNaN(parseInt(val, 10)) && _.isNumber(parseInt(val, 10))) {
  //     let parsed = Math.abs(parseInt(val, 10));
  //     $input.val(parsed);
  //   } else if (val) {
  //     // Otherwise, show an error message as long as a value was entered.
  //     this._showError(el, ScreenerSinglePage.ErrorMessage.INTEGER);
  //     $input.one('keyup', () => {
  //       this._validateIntegerField(el);
  //     });
  //   } else if ($input.prop('required')) {
  //     this._validateRequiredField(el);
  //   }

  //   return this;
  // }

  /**
   * For a given input, if it has the "toggles" data attribute, show or hide
   * another element selected by the toggles values based on the value of the
   * input. If the input has a "shows" or "hides" data attribute, show or hide
   * relevant element accordingly.
   * @private
   * @param {HTMLElement} el - Input/select element.
   * @return {this} Screener
   */
  _handleToggler(event) {
    const $el = $(event.currentTarget);
    if ($el.data('toggles')) {
      const $target = $($el.data('toggles'));
      if (
          ($el.prop('checked') && Boolean(parseInt($el.val(), 10))) ||
          ($el.is('select') && $el.val())
      ) {
        $target.removeClass(ScreenerSinglePage.CssClass.HIDDEN);
      } else {
        $target.addClass(ScreenerSinglePage.CssClass.HIDDEN);
      }
    }
    if ($el.data('shows')) {
      $($el.data('shows')).removeClass(ScreenerSinglePage.CssClass.HIDDEN);
    }
    if ($el.data('hides')) {
      $($el.data('hides')).addClass(ScreenerSinglePage.CssClass.HIDDEN);
    }
    return this;
  }



  /**
   * Returns the JSON object for Drools submission.
   * @private
   * @return {object} drools JSON
   */
  _getDroolsJSON() {
    const droolsJSON = {
      lookup: 'KieStatelessSession',
      commands: []
    };
    // Insert Household data.
    droolsJSON.commands.push({
      insert: {
        object: {
          'accessnyc.request.Household': this._household.toObject()
        }
      }
    });
    // Insert Person data.
    _.each(this._people.slice(0, this._household.get('members')),
        (person) => {
      if (person) {
        droolsJSON.commands.push({
          insert: {
            object: {
              'accessnyc.request.Person': person.toObject()
            }
          }
        });
      }
    });
    // Additional Drools commands.
    droolsJSON.commands.push({
      'fire-all-rules': {
        'out-identifier': 'rulesFiredCountOut'
      }
    });
    droolsJSON.commands.push({
      query: {
        'name': 'findEligibility',
        'arguments': [],
        'out-identifier': 'eligibility'
      }
    });

    // This Drools command outputs a large number of debugging variables that
    // are not necessary for production.
    if (Utility.getUrlParameter('debug') === '1') {
      droolsJSON.commands.push({
        'get-objects': {
          'out-identifier': 'getObjects'
        }
      });
    }

    return droolsJSON;
  }

  /**
   * Submits the JSON payload to Drools.
   * @private
   * @param {string} postUrl - AJAX URL destination.
   * @return {jqXHR}
   */
  _submit(postUrl) {
    /* eslint-disable no-console, no-debugger */
    if (Utility.getUrlParameter('debug') === '1') {
      console.log(this._getDroolsJSON());
      console.log(JSON.stringify(this._getDroolsJSON()));
      debugger;
    }
    /* eslint-enable no-console, no-debugger */

    return $.ajax({
      url: postUrl,
      type: 'post',
      data: {
        action: 'drools',
        data: this._getDroolsJSON()
      }
    }).done((data) => {
      /* eslint-disable no-console, no-debugger */
      if (Utility.getUrlParameter('debug') === '1') {
        console.log(data);
        console.log(JSON.stringify(data));
        debugger;
      }
      /* eslint-enable no-console, no-debugger */

      if (data.type !== 'SUCCESS') {
        // TODO(jjandoc): Add error handler.
        /* eslint-disable no-console, no-debugger */
        if (Utility.getUrlParameter('debug') === '1') {
          console.error(data);
          debugger;
        }
        /* eslint-enable no-console, no-debugger */
      }
      const programs = _.chain(Utility.findValues(data, 'code'))
          .filter((item) => _.isString(item)).uniq().value();
      const params = {};
      if (this._categories.length) {
        params.categories = this._categories.join(',');
      }
      if (programs.length) {
        params.programs = programs.join(',');
      }
      if ('GUID' in data) {
        params.guid = data.GUID;
      }
      params.date = Math.floor(Date.now() / 1000);
      // For security, reset the form before redirecting so that results are
      // not visible when someone hits back on their browser.
      this._el.reset();
      window.location = `./results?${$.param(params)}`;
    }).fail(function(error) {
      // TODO(jjandoc): Display error messaging here.
    });
  }
}

/**
 * Checks to see if the input's value is a valid NYC zip code.
 * @param {HTMLELement} el - Input element to validate.
 * @return {this} Screener
 */
ScreenerSinglePage.validateZipField = {
  getMessage: () => 'Must be a valid NYC zip code',
  validate: function(value) {
    if (ScreenerSinglePage.NYC_ZIPS.indexOf(value) > -1) return true;
    return false;
  }
};

/**
 * Push/Pull items in an array
 * @param {object} event listener object, requires data;
 *                       {object} array to push to
 *                       {key} if object is contained in a model,
 *                       add the data-key parameter
 */
ScreenerSinglePage.push = function(event) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let key = el.dataset.key;
  let value = el.value;
  // if there is a key, it's probably one of the custom modules
  // with storage in ._attrs, so we'll get the value for processing
  let current = (typeof key === 'undefined')
    ? this[obj] : this[obj].get(key);

  // get the current index
  let index = current.indexOf(value);
  // if (index > -1) return;

  // if checked, push, if not remove item
  if (el.checked) {
    current.push(value);
  } else if (index > -1) {
    current.splice(index, 1);
  }

  // remove duplicates
  current = _.uniq(current);

  // if there is a key, it's probably one of the custom modules
  // with storage in ._attrs
  if (typeof key != 'undefined') {
    this[obj].set(key, current);
  } else {
    this[obj] = current;
  }
  console.dir(current);
};

/**
 * Checks model to see if it is included in a list or not.
 * @param  {string} list  the name of the model
 * @param  {string} value the name of the value to check
 * @return {boolean}      wether or not the value is in the list or not
 */
ScreenerSinglePage.checked = function(list, value) {
  return (this[list].indexOf(value) > -1);
};

/**
 * Resets a attribute matrix, ex "none of these apply"
 * @param  {object} event the click event
 * @return {null}
 */
ScreenerSinglePage.resetAttr = function(event) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let index = el.dataset.index;
  let keys = el.dataset.key.split(',');
  let value = (el.value === 'true');
  for (var i = keys.length - 1; i >= 0; i--) {
    if (typeof index === 'undefined') {
      this[obj].set(keys[i], value);
      console.dir(this[obj]);
    } else {
      this[obj][index].set(keys[i], value);
      console.dir(this[obj][index]);
    }
    let el = document.querySelector(`[data-key="${keys[i]}"]`);
    if (el) el.checked = false;
  }
};

/**
 * Inforces strict types for certain data
 * @param  {event} event event listener object, requires data;
 *                       object {object} 'people' or 'household'
 *                       index {number} item index in object (optional)
 *                       key {string} attribute to set
 *                       type {string} type of attribute
 * @return {null}
 */
ScreenerSinglePage.setAttr = function(event) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let index = el.dataset.index;
  let key = el.dataset.key;
  let reset = el.dataset.reset;
  // get the typed value;
  let value = ScreenerSinglePage.getTypedVal(el);
  console.dir([key, value]);
  // set the attribute;
  if (typeof index === 'undefined') {
    this[obj].set(key, value);
    console.dir(this[obj]);
  } else {
    this[obj][index].set(key, value);
    console.dir(this[obj][index]);
  }
  // reset an element based on this value;
  if (typeof reset != 'undefined') {
    document.querySelector(reset).checked = false;
  }
};

/**
 * Populate the family, start at one because
 * the first person exists by default
 * @param  {event} event to pass to setAttr()
 */
ScreenerSinglePage.populate = function(event) {
  let number = event.currentTarget.value;
  let dif = number - this.people.length;
  // set the data for the model
  this.setAttr(event);
  if (dif > 0) { // add members if positive
    for (let i = 0; i <= dif - 1; i++) {
      let person = new ScreenerPerson();
      // person._attrs.guid = Utility.guid();
      this.people.push(person);
    }
  } else if (dif < 0) { // remove members if negative
    this.people = this.people.slice(0, this.people.length + dif);
  }
};

/**
 * Collects DOM income data and updates the model, if there is no income
 * data based on the DOM, it will create a new income object
 * @param  {object} data - person {index}
 *                         val {model attribute key}
 *                         key {income key}
 *                         value {model attribute value}
 */
ScreenerSinglePage.pushPayment = function(event) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let index = parseInt(el.dataset.index);
  let value = el.value;
  let key = el.dataset.key;
  // if the payment exists
  if (value === '' || el.checked === false) {
    // remove payment
    let current = _.findIndex(
      this[obj][index]._attrs[key], {'type': value}
    );
    this[obj][index]._attrs[key].splice(current, 1);
  } else {
    // create a new payment
    this[obj][index]._attrs[key].push({
      amount: '',
      type: value,
      frequency: 'monthly'//,
      /*guid: Utility.guid()*/
    });
  }
  console.dir(this[obj][index]);
};

/**
 * Find a payment by type in a collection
 * @param  {string} obj    the vue opject to search
 * @param  {integer} index the index of the model within the vue object
 * @param  {string} key    the key of the model's attr
 * @param  {[type]} type   the type value to search by
 * @return {object}        the payment, false if not found
 */
ScreenerSinglePage.getPayment = function(obj, index, key, type) {
  let payment = _.findWhere(
    this[obj][index]._attrs[key], {'type': type}
  );
  return (payment) ? payment : false;
};

/**
 * Check for single occupant of household
 * @return {boolean} if household is 1 occupant
 */
ScreenerSinglePage.singleOccupant = function() {
  return (this.household._attrs.members === 1);
};

/**
 * Assembles data for the recap view and renders the recap template.
 * @private
 * @return {this} Screener
 */
ScreenerSinglePage.renderRecap = function(vue) {
  const templateData = {
    categories: [],
    household: {
      assets: `$${vue.household.get('cashOnHand')}`,
      owners: [],
      rentalType: '',
      renters: [],
      types: [],
      zip: vue.household.get('zip')
    },
    members: []
  };

  // Add programs.
  _.each(vue.categories, (category) => {
    const obj = {
      slug: category,
      label: Utility.localize(category)
    };
    templateData.categories.push(obj);
  });

  const housingTypes = [
    'Renting',
    'Owner',
    'StayingWithFriend',
    'Hotel',
    'Shelter',
    'PreferNotToSay'
  ];

  // Add housing type.
  _.each(housingTypes, (type) => {
    if (vue.household.get(`living${type}`)) {
      const obj = {
        slug: type,
        label: Utility.localize(`living${type}`)
      };

      templateData.household.types.push(obj);
    }

    if (type === 'Renting') {
      templateData.household.rentalType =
          Utility.localize(vue.household.get('livingRentalType'));
    }
  });

  // Add household member data.
  _.each(vue.people.slice(0, vue.household.get('members')),
      (person, i) => {
    const member = {
      age: person.get('age'),
      benefits: [],
      conditions: [],
      expenses: [],
      incomes: [],
      isHoh: person.get('headOfHousehold'),
      relation: Utility.localize(person.get('headOfHouseholdRelation'))
    };

    if (person.get('headOfHousehold')) {
      if (i === 0) {
        member.relation = Utility.localize('Self');
      } else {
        member.relation = Utility.localize('HeadOfHousehold');
      }
    }

    _.each(person.getBenefits(), (value, key) => {
      if (value) {
        member.benefits.push(Utility.localize(key));
      }
    });

    _.each(person.getConditions(), (value, key) => {
      if (value) {
        member.conditions.push(Utility.localize(key));
      }
    });

    _.each(['incomes', 'expenses'], (type) => {
      _.each(person.get(type), (item) => {
        const obj = {
          amount: `$${item.amount}`,
          type: Utility.localize(item.type),
          frequency: Utility.localize(item.frequency)
        };
        member[type].push(obj);
      });
    });

    _.each(['livingOwnerOnDeed', 'livingRentalOnLease'], (type) => {
      if (person.get(type)) {
        const obj = {};
        if (person.get('headOfHousehold')) {
          obj.slug = i === 0 ? 'Self' : 'HeadOfHousehold';
        } else {
          obj.slug = person.get('headOfHouseholdRelation');
        }
        obj.label = Utility.localize(obj.slug);
        if (type === 'livingOwnerOnDeed') {
          templateData.household.owners.push(obj);
        } else {
          templateData.household.renters.push(obj);
        }
      }
    });

    templateData.members.push(member);
  });

  const template = $('#screener-recap-template').html();
  const renderedTemplate = _.template(template)(templateData);
  $('#recap-body').html(renderedTemplate);
  return vue;
}

/**
 * Returns the value of a supplied input in the type defined by a data-type
 * attribute on that input.
 * @param {HTMLElement} input
 * @return {boolean|Number|string} typed value
 */
ScreenerSinglePage.getTypedVal = function(input) {
  const $input = $(input);
  const val = $input.val();
  let finalVal = $input.val();
  switch ($input.data('type')) {
    case ScreenerSinglePage.InputType.BOOLEAN: {
      if (input.type == 'checkbox') {
        finalVal = input.checked;
      } else { // assume it's a radio button
        // if the radio button is using true/false;
        // if the radio button is using 1 or 0;
        finalVal = (val === 'true') ? true : Boolean(parseInt(val, 10));
      }
      break;
    }
    case ScreenerSinglePage.InputType.FLOAT: {
      finalVal = (_.isNumber(parseFloat(val)) && !_.isNaN(parseFloat(val))) ?
          parseFloat(val) : 0;
      break;
    }
    case ScreenerSinglePage.InputType.INTEGER: {
      finalVal = (_.isNumber(parseInt(val, 10)) &&
          !_.isNaN(parseInt(val, 10))) ?
          parseInt($input.val(), 10) : 0;
      break;
    }
  }
  console.log([val, finalVal]);
  return finalVal;
};

/**
 * Component for the person label
 * @type {Object} Vue Component
 */
ScreenerSinglePage.personLabel = {
  props: ['index', 'person'],
  template: '<span class="c-black">' +
    '<span :class="personIndex(index)"></span> ' +
    '<span v-if="index == 0">You,</span>' +
    '<span v-if="person.headOfHousehold"> Head of Household,</span>' +
    '<span v-if="index != 0"> {{ person.headOfHouseholdRelation }},</span>' +
    '<span> {{ person.age }}</span>' +
  '</span>',
  methods: {
    personIndex: function(index) {
      let name = 'i-' + index;
      let classes = {
        'screener-members__member-icon': true
      };
      classes[name] = true
      return classes;
    }
  }
};

/**
 * CSS classes used by this component.
 * @enum {string}
 */
ScreenerSinglePage.CssClass = {
  ACTIVE: 'active',
  ADD_SECTION: 'js-add-section',
  CHECKBOX_GROUP: 'js-screener-checkbox-group',
  CLEAR_GROUP: 'js-clear-group',
  EDIT_PERSON: 'js-edit-person',
  ERROR: 'error',
  ERROR_MSG: 'error-message',
  FORM: 'js-screener-single-page-form',
  HIDDEN: 'hidden',
  MATRIX: 'js-screener-matrix',
  MATRIX_ITEM: 'js-matrix-item',
  MATRIX_SELECT: 'js-matrix-select',
  PAGE: 'js-screener-page',
  PAGE_RECAP: 'page-recap',
  RADIO_GROUP: 'js-screener-radio-group',
  REMOVE_PERSON: 'js-remove-person',
  RENDER_RECAP: 'js-render-recap',
  QUESTION_CONTAINER: 'screener-question-container',
  TOGGLE: 'js-screener-toggle',
  TOGGLE_QUESTION: 'js-toggle-question',
  STEP: 'js-screener-step',
  SUBMIT: 'js-screener-submit',
  TRANSACTION_LABEL: 'screener-transaction-type'
};

/**
 * Localization labels of error messages.
 * @enum {string}
 */
ScreenerSinglePage.ErrorMessage = {
  FLOAT: 'ERROR_FLOAT',
  HOUSEHOLD: 'ERROR_HOUSEHOLD',
  INTEGER: 'ERROR_INTEGER',
  REQUIRED: 'ERROR_REQUIRED',
  ZIP: 'ERROR_ZIP'
};

/**
 * data-type attributes used by this component.
 * @enum {string}
 */
ScreenerSinglePage.InputType = {
  BOOLEAN: 'boolean',
  FLOAT: 'float',
  INTEGER: 'integer'
};

/**
 * Valid zip codes in New York City. Source:
 * https://data.cityofnewyork.us/City-Government/Zip-code-breakdowns/6bic-qvek
 * @type {array<String>}
 */
ScreenerSinglePage.NYC_ZIPS = Shared.NYC_ZIPS;

export default ScreenerSinglePage;
