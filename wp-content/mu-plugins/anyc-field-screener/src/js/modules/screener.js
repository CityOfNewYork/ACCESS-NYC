/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Utility from 'modules/utility';
import _ from 'underscore';
import Vue from 'vue/dist/vue.common';
import Validator from 'vee-validate';
import Cookies from 'js-cookie';
import ScreenerHousehold from 'modules/screener-household';
import ScreenerPerson from 'modules/screener-person';
import ScreenerClient from 'modules/screener-client';
import ScreenerStaff from 'modules/screener-staff';
import CalcInput from 'modules/calc-input';

/**
 * Requires Documentation
 * @class
 */
class Screener {
  /**
   * @param {HTMLElement} el - The form element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;

    /** @private {boolean} Whether the google reCAPTCHA widget has passed. */
    this._recaptchaVerified = true;

    /** @private {object} The screener routes and event hooks */
    this._routes = {
      admin: function(vue) {},
      screener: function(vue) {},
      recap: function(vue) {}
    };

    /** @private {object} The Vue configuration */
    this._vue = {
      delimiters: ['v{', '}'],
      el: '#vue',
      data: {
        /* Models */
        people: [new ScreenerPerson({
          headOfHousehold: true
        })],
        household: new ScreenerHousehold({
          city: 'NYC',
          livingPreferNotToSay: true
        }),
        client: new ScreenerClient(),
        staff: new ScreenerStaff(),
        /* UI Data */
        /** @type {boolean} Wether the disclaimer is checked */
        disclaimer: false,
        /** @type {Boolean} Throttle for submitting timeout */
        submitting: false,
        /** @type {array} */
        expenses: [],
        /** @type {Number} */
        income: 0,
        /** @type {array} grouped attrs for selection by getAttrs(recap page) */
        conditionAttrs: ScreenerPerson.CONDITION_ATTRS,
        /** @type {Array} grouped attrs for selection by getAttrs(recap page) */
        benefitAttrs: ScreenerPerson.BENEFIT_ATTRS,
        /** @type {Array} grouped attrs for selection by getAttrs(recap page) */
        livingAttrs: ScreenerHousehold.LIVING_ATTRS,
        /** @type {Array} grouped attrs for selection by getAttrs(recap page) */
        addressAttrs: ScreenerClient.ADDRESS_ATTRS
      },
      methods: {
        resetAttr: Screener.resetAttr,
        setAttr: Screener.setAttr,
        setAllAttr: Screener.setAllAttr,
        setHousing: Screener.setHousing,
        getAttrs: Screener.getAttrs,
        populate: Screener.populate,
        pushPayment: Screener.pushPayment,
        getPayment: Screener.getPayment,
        removePayment: Screener.removePayment,
        removeAllPayments: Screener.removeAllPayments,
        push: Screener.push,
        checked: Screener.checked,
        singleOccupant: Screener.singleOccupant,
        validate: Screener.validate,
        localString: Screener.localString,
        getTypedVal: Screener.getTypedVal,
        commit: Screener.commit,
        filterDollars: Screener.filterDollars,
        filterPhone: Screener.filterPhone,
        track: Screener.track
      }
    };
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

    /**
     * Hide pages and questions on load. Hiding them with CSS (using display:
     * none) before causes flickering issues on checkbox interactions on IOS.
     * The CSS could probably afford to be optimized, but this works as well.
     */

    $(Screener.Selectors.PAGE)
      .removeClass(Screener.Classes.HIDDEN_OPACITY)
      .addClass(Screener.Classes.HIDDEN);
    $(Screener.Selectors.TOGGLE_QUESTION)
      .addClass(Screener.Classes.HIDDEN);

    /**
     * Reactive Elements
     */

    // Special validations
    Validator.Validator.extend('zip', this._validateZipField());
    Validator.Validator.extend('hoh', this._validateHeadOfHousehold());
    Vue.use(Validator, {events: 'blur', zip: 'zip', hoh: 'hoh'});

    // Components
    Vue.component('personlabel', Screener.personLabel);

    // Initialize Vue
    this._vue = new Vue(this._vue);

    /**
     * DOM Event Listeners
     */

    const $el = $(this._el);

    // Submit
    $el.on('click', Screener.Selectors.SUBMIT, event => {
      if (this._recaptchaVerified) this._submit(event);
    });

    // Basic toggles
    $el.on('change', Screener.Selectors.TOGGLE, this._toggler);

    // Validate calculated input against regular expressions
    new CalcInput(this._el);

    /**
     * Determine whether or not to initialize ReCAPTCHA. This should be
     * initialized only on every 10th view which is determined via an
     * incrementing cookie.
     */

    let viewCount = Cookies.get(Screener.Cookies.VIEWS) ?
      parseInt(Cookies.get(Screener.Cookies.VIEWS), 10) : 1;

    if (viewCount >= 10) {
      this._initRecaptcha();
      viewCount = 0;
    }

    // `2/1440` sets the cookie to expire after two minutes.
    Cookies.set(Screener.Cookies.VIEWS, ++viewCount, {
      expires: (2/1440),
      path: Screener.Cookies.PATH
    });

    /**
     * Routing
     */

    window.addEventListener('hashchange', event => this._router(event));

    // Close Questions
    $el.on('click', Screener.Selectors.QUESTION, event => {
      this._routerQuestion(event);
    });

    // Set the initial view
    this._routerPage('#page-admin');

    // Set the timeout for the application
    Utility.sessionTimeout(
      Screener.IdleSessionConfig.IDLE_SESSION_TIMEOUT,
      this._idleSession
    );

    return this;
  }

  /**
   * Actions for when the session is idle
   * @param  {object} timer The timer object
   */
  _idleSession(timer) {
    if (timer.int > 1) {
      // prevents the page refresh until initial interaction
      // location.reload(); // The data will be cleared.
      let message = Screener.IdleSessionConfig.IDLE_SESSION_MESSAGE;
      alert(message);
      location.reload();
    }
  }

  /**
   * Asynchronously loads the Google recaptcha script and sets callbacks for
   * load, success, and expiration.
   */
  _initRecaptcha() {
    const script = $(document.createElement('script'));

    /* eslint-disable no-console, no-debugger */
    if (Utility.debug()) console.warn(`Recaptcah Initialized`);
    /* eslint-enable no-console, no-debugger */

    script.attr('src',
      'https://www.google.com/recaptcha/api.js' +
      '?onload=screenerCallback&render=explicit').prop({
        async: true,
        defer: true
      });

    window.screenerCallback = () => {
      window.grecaptcha.render(
        document.querySelector(Screener.Selectors.RECAPTCHA), {
          'sitekey': Utility.CONFIG.GRECAPTCHA_SITE_KEY,
          'callback': 'screenerRecaptcha',
          'expired-callback': 'screenerRecaptchaReset'
        });
    };

    window.screenerRecaptcha = () => {
      this._recaptchaVerified = true;
    };

    window.screenerRecaptchaReset = () => {
      this._recaptchaVerified = false;
    };

    this._recaptchaVerified = false;

    $('head').append(script);
  }

  /**
   * Checks to see if the input's value is a valid NYC zip code.
   * @return {object} The validation object for vee validate
   */
  _validateZipField() {
    return {
      getMessage: () => 'Must be a valid NYC zip code',
      validate: function(value) {
        if (Screener.NYC_ZIPS.indexOf(value) > -1) return true;
        return false;
      }
    };
  }

  /**
   * Makes sure there is at least one head of household.
   * @return {object} The validation object for vee validate
   */
  _validateHeadOfHousehold() {
    return {
      getMessage: () => 'At lease one head of household is required',
      validate: () => {
        let hoh = false;
        for (let i = this._vue.people.length - 1; i >= 0; i--) {
          let valid = this._vue.people[i]._attrs.headOfHousehold;
          hoh = (valid) ? valid : hoh;
        }
        return hoh;
      }
    };
  }

  /**
   * The page to go to.
   * @param  {string} page the page hash
   */
  _routerPage(page) {
    let view = document.querySelector(Screener.Selectors.VIEW);

    /* eslint-disable no-console, no-debugger */
    if (Utility.debug()) console.log(`routerPage: ${page}`);
    /* eslint-enable no-console, no-debugger */

    window.location.hash = page;
    view.scrollTop = 0;

    $(Screener.Selectors.PAGE)
      .removeClass(Screener.Classes.ACTIVE)
      .addClass(Screener.Classes.HIDDEN)
      .removeClass('fadeIn')
      .attr('aria-hidden', 'true')
      .find(':input, a')
      .attr('tabindex', '-1');

    $(page)
      .removeClass(Screener.Classes.HIDDEN)
      .addClass(Screener.Classes.ACTIVE)
      .addClass('fadeIn')
      .removeAttr('aria-hidden')
      .find(':input, a')
      .removeAttr('tabindex');
  }

  /**
   * Jumps to screener question.
   * @param {object} event - The click event if available.
   * @param {string} hash  - The question's hash id.
   */
  _routerQuestion(event, hash) {
    hash = (typeof hash != 'undefined') ? hash : event.target.hash;
    let page = $(hash).closest(Screener.Selectors.PAGE);
    let target = $(hash).find(Screener.Selectors.TOGGLE_QUESTION);
    let show = !target.hasClass(Screener.Classes.ACTIVE);

    if (!page.hasClass(Screener.Classes.ACTIVE)) {
      this._routerPage(`#${page.attr('id')}`);
      show = true;
    }

    // Show
    if (show) {
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug()) console.log(`routerQuestion: Show ${hash}`);
      /* eslint-enable no-console, no-debugger */
      $(Screener.Selectors.TOGGLE_QUESTION)
        .addClass(Screener.Classes.HIDDEN)
        .removeClass(Screener.Classes.ACTIVE)
        .prop('aria-hidden', true);

      target.addClass(Screener.Classes.ACTIVE)
        .removeClass(Screener.Classes.HIDDEN)
        .prop('aria-hidden', false);

      // Scrolling Behavior
      event.preventDefault();
      setTimeout(() => {
        document.querySelector(hash)
          .scrollIntoView(true);
        document.querySelector(Screener.Selectors.VIEW)
          .scrollBy({top: -60, left: 0, behavior: 'auto'});
      }, 1);

      return;
    }

    // Hide
    /* eslint-disable no-console, no-debugger */
    if (Utility.debug()) console.log(`routerQuestion: Hide ${hash}`);
    /* eslint-enable no-console, no-debugger */
    target.addClass(Screener.Classes.HIDDEN)
      .removeClass(Screener.Classes.ACTIVE)
      .prop('aria-hidden', true);
    // Scrolling Behavior
    event.preventDefault();
  }

  /**
   * The router, listens for hash changes and initializes appropriate hooks
   * @param  {object} event the window hash change event
   * @return {null}
   */
  _router(event) {
    let hash = window.location.hash;
    let type = hash.split('-')[0];
    let route = hash.split('-')[1];

    if (type === '#page') {
      this._routes[route](this._vue);
      this._routerPage(hash);
    }

    if (type === '#question') {
      this._routerQuestion(event, hash);
    }

    return this;
  }

  /**
   * For a given input, if it has the "toggles" data attribute, show or hide
   * another element selected by the toggles values based on the value of the
   * input. If the input has a "shows" or "hides" data attribute, show or hide
   * relevant element accordingly.
   * @private
   * @param {object} event - toggle event.
   */
  _toggler(event) {
    const $el = $(event.currentTarget);
    if ($el.data('toggles')) {
      const $target = $($el.data('toggles'));
      if (
          ($el.prop('checked') && Boolean(parseInt($el.val(), 10))) ||
          ($el.is('select') && $el.val())
      ) {
        $target.removeClass(Screener.Classes.HIDDEN);
      } else {
        $target.addClass(Screener.Classes.HIDDEN);
      }
    }
    if ($el.data('shows')) {
      $($el.data('shows')).removeClass(Screener.Classes.HIDDEN);
    }
    if ($el.data('hides')) {
      $($el.data('hides')).addClass(Screener.Classes.HIDDEN);
    }
  }

  /**
   * Returns the JSON object for Drools submission.
   * @private
   * @param  {object} vue the data store
   * @return {object}     drools JSON
   */
  _getDroolsJSON(vue) {
    const json = {
      lookup: 'KieStatelessSession',
      commands: []
    };

    // Insert Household data.
    json.commands.push({
      insert: {
        object: {
          'accessnyc.request.Household': vue.household.toObject()
        }
      }
    });

    // Insert Person data.
    _.each(vue.people.slice(0, vue.household.get('members')), person => {
      if (person) {
        json.commands.push({
          insert: {
            object: {
              'accessnyc.request.Person': person.toObject()
            }
          }
        });
      }
    });

    // Additional Drools commands.
    json.commands.push({
      'fire-all-rules': {
        'out-identifier': 'rulesFiredCountOut'
      }
    });

    json.commands.push({
      query: {
        'name': 'findEligibility',
        'arguments': [],
        'out-identifier': 'eligibility'
      }
    });

    // This Drools command outputs a large number of debugging variables that
    // are not necessary for production.
    if (Utility.debug()) {
      json.commands.push({
        'get-objects': {
          'out-identifier': 'getObjects'
        }
      });
    }

    return json;
  }

  /**
   * Submits the JSON payload to Drools.
   * @private
   * @param  {object} event - the form submit event
   * @return {jqXHR}
   */
  _submit(event) {
    let url = event.target.dataset.action;
    let json = this._getDroolsJSON(this._vue);
    let programsFilter = this._vue.client.get('programsEnrolled');

    // Set the language of the household
    this._vue.household.set('lang', $('html').attr('lang'));

    this._vue.submitting = true;

    /* eslint-disable no-console, no-debugger */
    if (Utility.debug()) {
      console.warn(json);
      console.log(JSON.stringify(json));
      debugger;
    }

    return $.ajax({
      url: url,
      type: 'post',
      data: {
        action: 'drools',
        data: json,
        staff: this._vue.staff.toObject(),
        client: this._vue.client.toObject(),
        nonce: document.querySelector('[data-js="nonce"]').value
      }
    }).done(data => {
      let result = {
        data: data,
        url: url,
        json: json
      };

      if (data.type !== 'SUCCESS') {
        if (Utility.debug()) {
          console.warn(result);
          debugger;
        }
        alert('There was an error getting results. Please try again later.');
        this._vue.submitting = false;
        return;
      }

      if (Utility.debug()) {
        console.warn(result);
        debugger;
      }

      const programs = _.chain(
          Utility.findValues(data, 'code')
        ).filter(
          item => _.isString(item)
        // filter out the programs they are already receiving
        ).filter(
          item => (programsFilter.indexOf(item) === -1)
        ).uniq().value();

      if (Utility.debug()) {
        console.warn(programs);
        debugger;
      }

      const params = {};

      if (this._vue.client._attrs.programCategories.length) {
        params.categories = this._vue.client._attrs.programCategories.join(',');
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
    });
    /* eslint-enable no-console, no-debugger */
  }
}

/**
 * Wrapper for the tracking functionality in utiltiy.
 * @param  {string} key  [description]
 * @param  {object} data [description]
 */
Screener.track = function(key, data) {
  Utility.track(`${Screener.ANALYTICS_PREFIX} ${key}`, data);
};

/**
 * Validation functionality, if a scope is attatched, it will only validate
 * against the scope stored in validScopes
 * @param  {event}  event - the click event
 * @param  {string} scope - the scope to validate, if undefined validates all
 */
Screener.validate = function(event, scope) {
  event.preventDefault();
  scope = (typeof scope !== 'undefined')
    ? scope : event.currentTarget.dataset.vvScope;
  if (typeof scope !== 'undefined') {
    this.$validator.validateAll(scope).then(valid => {
      Screener.valid(event.target.hash, valid);
    });
  } else {
    this.$validator.validate().then(valid => {
      Screener.valid(event.target.hash, valid);
    });
  }
};

/**
 * Validate
 * @param {string}  hash  - The hash to move to
 * @param {boolean} valid - Wether the validator passes validation
 */
Screener.valid = function(hash, valid) {
  if (!valid) {
    /* eslint-disable no-console, no-debugger */
    if (Utility.debug())
      console.warn('Some required fields are not filled out.');
    /* eslint-enable no-console, no-debugger */
  } else {
    window.location.hash = hash;
  }
  // debug bypasses validation
  if (Utility.debug())
    window.location.hash = hash;
};

/**
 * Push/Pull items in an array
 * @param {object} event listener object, requires data;
 *                       {object} array to push to
 *                       {key} if object is contained in a model,
 *                       add the data-key parameter
 */
Screener.push = function(event) {
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
};

/**
 * Checks model to see if it is included in a list or not.
 * @param  {string} list  the name of the model
 * @param  {string} value the name of the value to check
 * @return {boolean}      wether or not the value is in the list or not
 */
Screener.checked = function(list, value) {
  return (this[list].indexOf(value) > -1);
};

/**
 * Resets a attribute matrix, ex "none of these apply"
 * @param  {object} event the click event
 */
Screener.resetAttr = function(event) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let index = el.dataset.index;
  let keys = el.dataset.key.split(',');
  let value = (el.value === 'true');
  for (let i = keys.length - 1; i >= 0; i--) {
    /* eslint-disable no-console, no-debugger */
    if (typeof index === 'undefined') {
      this[obj].set(keys[i], value);
      if (Utility.debug())
        console.log(`resetAttr: ${obj}, ${keys[i]}, ${value}`);
    } else {
      this[obj][index].set(keys[i], value);
      if (Utility.debug())
        console.log(`resetAttr: ${obj}, ${index}, ${keys[i]}, ${value}`);
    }
    /* eslint-enable no-console, no-debugger */
    let el = document.querySelector(`[data-key="${keys[i]}"]`);
    if (el) el.checked = false;
  }
};

/**
 * Inforces strict types for certain data
 * @param  {event} event - event listener object, requires data;
 *                       - object {object} 'people' or 'household'
 *                       - index {number} item index in object (optional)
 *                       - key {string} attribute to set
 *                       - type {string} type of attribute
 * @param  {string/boolean/number/array} value - if passed, will use this value
 *                                               instead of the data attribute
 * @param  {string} attr - if passed, will set this attribute instead of the
 *                         data attribute
 */
Screener.setAttr = function(event, value, attr) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let index = el.dataset.index;
  let key = (typeof attr != 'undefined') ? attr : el.dataset.key;
  let reset = el.dataset.reset;
  // get the typed value;
  value = (typeof value != 'undefined') ? value : Screener.getTypedVal(el);
  // set the attribute;
  /* eslint-disable no-console, no-debugger */
  if (typeof index === 'undefined') {
    this[obj].set(key, value);
    if (Utility.debug())
      console.dir(`setAttr: ${obj}, ${key}, ${value}`);
  } else {
    this[obj][index].set(key, value);
    if (Utility.debug())
      console.dir(`setAttr: ${obj}, ${index}, ${key}, ${value}`);
  }
  /* eslint-enable no-console, no-debugger */
  // reset an element based on this value;
  if (typeof reset != 'undefined')
    document.querySelector(reset).checked = false;
};

/**
 * Format number value and make sure it has '.00'
 * @param  {string} event - the blur event
 * @return {string}       - the formatted string
 */
Screener.filterDollars = function(event) {
  let value = event.currentTarget.value;
  let postfix = '';
  if (`${value}`.indexOf('.') > -1) {
    let split = `${value}`.split('.');
    postfix = (split[1].length == 1) ? '0' : postfix;
    postfix = (split[1].length == 0) ? '00' : postfix;
    value += postfix;
  } else if (value != '') {
    value += '.00';
  }
  return value;
};

/**
 * Filter a phone number and add dashes as the user types
 * @param  {object} event - the key down event object
 * @return {string}       - the formatted string
 */
Screener.filterPhone = function(event) {
  const key = event.keyCode;
  const backspace = (key === 8 || key === 46);
  const arrows = (key >= 37 && key <= 40);

  if (backspace || arrows) return event.target.value;

  let numbers = event.target.value.replace(/[^0-9]/g, '');

  if (numbers.length === 6) {
    return numbers.replace(/(\d{3})(\d{3})/, '$1-$2-');
  } else if (numbers.length === 3) {
    return numbers.replace(/(\d{3})/, '$1-');
  }

  return event.target.value;
};

/**
 * Set attribute for all objects in collection.
 * @param  {event} event - event listener object, requires data;
 *                       - object {object} 'people' or 'household'
 *                       - key {string} attribute to set
 *                       - type {string} type of attribute
 * @param  {string/boolean/number/array} value - if passed, will use this value
 *                                               instead of the data attribute
 * @param  {string} attr - if passed, will set this attribute instead of the
 *                         data attribute
 */
Screener.setAllAttr = function(event, value, attr) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let key = (typeof attr != 'undefined') ? attr : el.dataset.key;
  let keys = el.dataset.key.split(',');
  value = (typeof value != 'undefined') ? value : Screener.getTypedVal(el);
  for (let i = this[obj].length - 1; i >= 0; i--) {
    for (let k = keys.length - 1; k >= 0; k--) {
      this[obj][i].set(keys[k], value);
    }
  }
  /* eslint-disable no-console, no-debugger */
  if (Utility.debug())
    console.dir(`setAllAttr: ${obj}, ${key}, ${value}`);
  /* eslint-enable no-console, no-debugger */
};

/**
 * Get select truthy or non blank string attributes for a model
 * @param  {string} object     - the model to retrieve from
 * @param  {string/array} keys - list of attributes to retrieve
 * @param  {Number} index      - index of model if in a collection
 * @return {object} key value pair of truthy values
 */
Screener.getAttrs = function(object, keys, index = -1) {
  let obj = (index > -1) ? this[object][index] : this[object];
  keys = (typeof keys === 'string') ? keys.split(',') : keys;
  return _.pick(obj._attrs, (value, key) => {
    return (keys.indexOf(key) > -1 && (value != '' || value === true));
  });
};

/**
 * Special processor for the household model housing attributes.
 * @param {event} event - the change event for housing
 */
Screener.setHousing = function(event) {
  let el = event.target;
  let key = el.dataset.key;
  let keys = [];
  let value = Screener.getTypedVal(el);
  let reset = false;

  this.household.set(key, value);

  if (key === 'livingPreferNotToSay' && value === true) {
    keys = el.dataset.keys.split(',');
    for (let k = keys.length - 1; k >= 0; k--) {
      this.household.set(keys[k], false);
    }
    reset = true;
  } else {
    this.household.set('livingPreferNotToSay', false);
  }

  // rental reset
  if ((key === 'livingRenting' && value === false) || reset) {
    this.household.set('livingRentalType', '');
    for (let r = this.people.length - 1; r >= 0; r--) {
      this.people[r].set('livingRentalOnLease', false);
    }
  }

  // owner reset
  if ((key === 'livingOwner' && value === false) || reset) {
    for (let o = this.people.length - 1; o >= 0; o--) {
      this.people[o].set('livingOwnerOnDeed', false);
    }
  }
};

/**
 * Populate the family, start at one because
 * the first person exists by default
 * @param  {event} event to pass to setAttr()
 */
Screener.populate = function(event) {
  let value = event.currentTarget.value;
  if (value === '' || parseInt(value, 10) === 0) return;
  let dif = value - this.people.length;
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
 * @param  {object} event - change event, requires data attributes;
 *                          person {index}
 *                          val {model attribute key}
 *                          key {income key}
 *                          value {model attribute value}
 */
Screener.pushPayment = function(event) {
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let objIndex = parseInt(el.dataset.index);
  let type = el.value;
  let key = el.dataset.key;
  let current = _.findIndex(
    this[obj][objIndex]._attrs[key], {'type': type}
  );

  // if the payment exists
  /* eslint-disable no-console, no-debugger */
  if (type === '' || el.checked === false) {
    // remove payment
    if (Utility.debug())
      console.log(`pushPayment: Remove; ${key}, ${type}`);
    this[obj][objIndex]._attrs[key].splice(current, 1);
  } else {
    // add payment
    if (Utility.debug())
      console.log(`pushPayment: Add; ${key}, ${type}`);
    this[obj][objIndex].addPayment(key, type);
  }
  /* eslint-enable no-console, no-debugger */
};

/**
 * Remove a payment if the element target value is blank
 * @param  {event} event - change event object
 */
Screener.removePayment = function(event) {
  if (event.currentTarget.value !== '') return;
  let el = event.currentTarget;
  let obj = el.dataset.object;
  let objIndex = parseInt(el.dataset.index);
  let key = el.dataset.key;
  let keyIndex = el.dataset.keyIndex;
  /* eslint-disable no-console, no-debugger */
  if (Utility.debug())
    console.log(`removePayment: ${key}`);
  /* eslint-enable no-console, no-debugger */
  this[obj][objIndex]._attrs[key].splice(keyIndex, 1);
};

/**
 * Removes all payments for all persons in household
 * @param  {object} event - the radio button toggle event
 * @param  {string} key   - optional way to set the key of the model
 * @param  {string} type  - an optional type to remove
 */
Screener.removeAllPayments = function(event, key, type) {
  if (event.currentTarget.value === 1) return;
  key = (typeof key != 'undefined') ? key : event.currentTarget.dataset.key;
  for (let i = this.people.length - 1; i >= 0; i--) {
    if (typeof type != 'undefined') {
      let index = _.findIndex(this.people[i]._attrs[key], {'type': type});
      this.people[i]._attrs[key].splice(index, 1);
    } else {
      this.people[i]._attrs[key] = [];
    }
  }
};

/**
 * Find a payment by type in a collection
 * @param  {string} obj       - the vue opject to search
 * @param  {integer} objIndex - the index of the model within the vue object
 * @param  {string} key       - the key of the model's attr
 * @param  {[type]} type      - the type value to search by
 * @return {object}           - the payment, false if not found
 */
Screener.getPayment = function(obj, objIndex, key, type) {
  let payment = _.findWhere(
    this[obj][objIndex]._attrs[key], {'type': type}
  );
  return (payment) ? payment : false;
};

/**
 * Check for single occupant of household
 * @return {boolean} if household is 1 occupant
 */
Screener.singleOccupant = function() {
  return (this.household._attrs.members === 1);
};

/**
 * Returns the value of a supplied input in the type defined by a data-type
 * attribute on that input.
 * @param {HTMLElement} input
 * @return {boolean|Number|string} typed value
 */
Screener.getTypedVal = function(input) {
  const val = input.value;
  let finalVal = input.value;
  switch (input.dataset.type) {
    case Screener.InputType.BOOLEAN: {
      if (input.type === 'checkbox') {
        finalVal = input.checked;
      } else { // assume it's a radio button
        // if the radio button is using true/false;
        // if the radio button is using 1 or 0;
        finalVal = (val === 'true') ? true : Boolean(parseInt(val, 10));
      }
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug())
        console.log(
          `getTypedVal: BOOLEAN, ${finalVal}:${typeof val}>${typeof finalVal}`);
      /* eslint-enable no-console, no-debugger */
      break;
    }
    case Screener.InputType.FLOAT: {
      finalVal = (
          _.isNumber(parseFloat(val)) &&
          !_.isNaN(parseFloat(val))
        ) ? parseFloat(val) : 0;
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug())
        console.log(
          `getTypedVal: FLOAT, ${finalVal}:${typeof val}>${typeof finalVal}`);
      /* eslint-enable no-console, no-debugger */
      break;
    }
    case Screener.InputType.INTEGER: {
      finalVal = (
          _.isNumber(parseInt(val, 10)) &&
          !_.isNaN(parseInt(val, 10))
        ) ? parseInt(input.value, 10) : 0;
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug())
        console.log(
          `getTypedVal: INTEGER, ${finalVal}:${typeof val}>${typeof finalVal}`);
      /* eslint-enable no-console, no-debugger */
      break;
    }
  }
  return finalVal;
};

/**
 * Use the model's commit method to save itself to local storage.
 * @param  {object} event - the change event of the input
 */
Screener.commit = function(event) {
  let obj = event.target.dataset.object;
  this[obj].commit();
};

/**
 * Return the local string label for values
 * @param  {string} slug - the slug value of the string
 * @return {string}      - the local string label
 */
Screener.localString = function(slug) {
  try {
    return _.findWhere(
      window.LOCALIZED_STRINGS,
      {slug: slug}
    ).label;
  } catch (error) {
    return slug;
  }
};

/**
 * Component for the person label
 * @type {Object} Vue Component
 */
Screener.personLabel = {
  props: ['index', 'person'],
  template: '<span class="text-color-black">' +
    '<span v-bind:class="personIndex(index)"></span> ' +
    '<span v-if="index === 0">{{ localString("you") }}, </span>'+
    '<span v-if="person.headOfHousehold"> ' +
      '{{ localString("headOfHousehold") }}, ' +
    '</span><span v-else>' +
      '<span v-if="person.headOfHouseholdRelation != \'\'">' +
        '{{ localString(person.headOfHouseholdRelation) }}, ' +
      '</span><span v-else>' +
        '<em>({{ localString("relationship") }})</em>, ' +
      '</span>' +
    '</span>' +
    '<span v-if="person.age != 0">{{ person.age }}</span>' +
    '<span v-else><em>({{ localString("age") }})</em></span>' +
  '</span>',
  methods: {
    personIndex: function(index) {
      let name = 'i-' + index;
      let classes = {
        'c-member-list__icon': true
      };
      classes[name] = true;
      return classes;
    },
    localString: Screener.localString
  }
};

/**
 * Selectors used by this component.
 * @enum {string}
 */
Screener.Selectors = {
  DOM: '[data-js="screener-field"]',
  PAGE: '[data-js="page"]',
  RECAPTCHA: '[data-js="recaptcha"]',
  TOGGLE: '[data-js="toggle"]',
  TOGGLE_QUESTION: '[data-js="toggle-question"]',
  SUBMIT: '[data-js="submit"]',
  VIEW: '[data-js="view"]',
  QUESTION: '[data-js="question"]'
};

/**
 * Classes used by this component.
 * @enum {string}
 */
Screener.Classes = {
  ACTIVE: 'active',
  HIDDEN: 'hidden',
  HIDDEN_OPACITY: 'opacity-0'
};

/**
 * data-type attributes used by this component.
 * @enum {string}
 */
Screener.InputType = {
  BOOLEAN: 'boolean',
  FLOAT: 'float',
  INTEGER: 'integer'
};

/** Cookie references */
Screener.Cookies = {
  VIEWS: 'access_nyc_field_screener_views',
  PATH: 'peu'
};

/** @type {String} Analytics Prefix */
Screener.ANALYTICS_PREFIX = 'PEU';

/** @type {Object} The configuration for the idle session */
Screener.IdleSessionConfig = {
  /**
   * The session timeout is set for 1 hour. The valid NONCE life is also set
   * for one hour and defined in the FieldScreenerBackend.php.
   */
  IDLE_SESSION_TIMEOUT: 3600000,
  IDLE_SESSION_MESSAGE: [
      'This session has been inactive for an hour,\n', // must match time above
      'any info entered will be cleared to prevent unauthorized access.'
    ].join('')
};

/**
 * Valid zip codes in New York City. Source:
 * https://data.cityofnewyork.us/City-Government/Zip-code-breakdowns/6bic-qvek
 * @type {array<String>}
 */
Screener.NYC_ZIPS = Utility.NYC_ZIPS;

export default Screener;
