/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Cookies from 'js-cookie';
import ScreenerHousehold from 'modules/screener-household';
import ScreenerPerson from 'modules/screener-person';
import Utility from 'modules/utility';
import Track from 'modules/track';
import _ from 'underscore';

/**
 * This component is the controller for the program screener. There's a lot
 * here but essentially how it works is that a click event listener attached
 * to anchor links will push a new state to the history API. As they submit
 * each step, a number of validations occur and if everything checks out for
 * that step, the ScreenerPerson and ScreenerHousehold objects are updated or
 * created.
 *
 * When the screener is submitted, these objects are compiled into the proper
 * formatting for the Drools rules engine and sent off to the Drools Proxy.
 * Assuming a successful response is received, we then redirect the user to
 * the screener results page, building a redirect URL based on the program
 * codes in the Droosl results, the categories they selected in step 1, the
 * current time, and a [guid] parameter provided by the Drools proxy.
 *
 * The screener relies on Underscore templates to render any dynamic views and
 * the Utility.localize function to translate any strings within those views to
 * the current language.
 *
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

    /** @private {jQuery} jQuery element array of screener steps. */
    this._$steps = $(this._el).find(`.${Screener.CssClass.STEP}`);

    /** @private {array<string>} array of selected category IDs */
    this._categories = [];

    /** @private {array<ScreenerPerson>} household members, max 8 */
    this._people = [new ScreenerPerson({
      headOfHousehold: true
    })];

    /** @private {ScreenerHousehold} household */
    this._household = new ScreenerHousehold();

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;

    /** @private {boolean} Whether the google reCAPTCHA widget is required. */
    this._recaptchaRequired = false;

    /** @private {boolean} Whether the google reCAPTCHA widget has passed. */
    this._recaptchaVerified = false;

    /** @private {string} the base string for the screener title. */
    this._baseTitle = $('title').text();

    /** @private {Array} Keeping track of current step in the screener. */
    this._history = [];
  }

  /**
   * If this component has not yet been initialized, attaches event listeners.
   * @method
   * @return {this} OfficeMap
   */
  init() {
    if (this._initialized)
      return this;

    /**
     * Step Changer and Validator. It captures the click event for anchor links
     * an pushes them through to the history API. It will also validate steps
     * that have the validation class attached to them.
     */
    $(this._el).on('click', 'a[href*="#"]', event => {
      event.preventDefault();

      let $step = $(event.currentTarget)
        .closest('.' + Screener.CssClass.STEP);

      let target = event.currentTarget;
      let hash = event.currentTarget.hash;
      let valid = true;

      if (target.classList.contains(Screener.CssClass.VALIDATE_STEP)) {
        valid = this._validateStep($step);
      }

      if (valid) {
        this._goToStep(hash)
          ._newState(hash, 'pushState')
          ._reFocus();
      }
    });

    /**
     * Step 8 or Head of Household Question. This is a special handler that
     * will set Person 0's Head of Household attribute when the radio button
     * is clicked then push a sate object to the history API.
     */
    $(this._el).on('change', '[name="Person[0].headOfHousehold"]', event => {
      let step = $(event.currentTarget).closest(`.${Screener.CssClass.STEP}`);
      let input = step.find('input[name="Person[0].headOfHousehold"]:checked');

      // Reset HOH to be element at index 0 and remove current HOH from array.
      // this._people.forEach((member, i) => {
      //  if (member._attrs.headOfHousehold === true && i !== 0) {
      //    this._people.splice(i, 1);

      //    $(this._el).find(`[id*="screener-person-${i}-income-"]`).remove();
      //    $(this._el).find(`[id*="screener-person-${i}-expenses-"]`).remove();
      //  }
      // });

      this._people[0].set({
        headOfHousehold: Screener.getTypedVal(input)
      });

      this._newState(window.location.hash, 'replaceState');
    });

    /**
     * Back/Forward button navigation. Listens for the hashchange event then
     * replaces the current state with the new state. This will not ultimately
     * replace the original hashchange history event.
     */
    window.addEventListener('hashchange', event => {
      event.preventDefault();

      let hash = window.location.hash;

      this._goToStep(hash)
        ._newState(hash, 'replaceState')
        ._reFocus();
    });

    /**
     * Large chained event handler for everything else.
     */
    $(this._el).on('change', 'input[type="checkbox"]', e => {
      this._toggleCheckbox(e.currentTarget);
    }).on('change', `.${Screener.CssClass.TOGGLE}`, e => {
      this._handleToggler(e.currentTarget);
    }).on('change', `.${Screener.CssClass.ADD_SECTION}`, e => {
      this._addMatrixSection(e.currentTarget);
    }).on('change', `.${Screener.CssClass.MATRIX_SELECT}`, e => {
      this._toggleMatrix(e.currentTarget);
    }).on('click', `.${Screener.CssClass.SUBMIT}`, e => {
      if (!this._recaptchaRequired) {
        this._submit($(e.currentTarget).data('action'));
      } else {
        $(e.currentTarget).closest(`.${Screener.CssClass.STEP}`)
          .find(`.${Screener.CssClass.ERROR_MSG}`).remove();

        if (this._recaptchaVerified) {
          this._submit($(e.currentTarget).data('action'));
        } else {
          this._showError($('#screener-recaptcha')[0],
              Screener.ErrorMessage.REQUIRED);
        }
      }
    }).on('blur', '[data-type="integer"]', e => {
      this._validateIntegerField(e.currentTarget);
    }).on('blur', '[data-type="float"]', e => {
      this._validateFloatField(e.currentTarget);
    }).on('blur', '[data-type="zip"]', e => {
      this._validateZipField(e.currentTarget);
    }).on('blur', '[data-type="age"]', e => {
      this._validateIntegerField(e.currentTarget);
    }).on('keyup', '[data-type="float"]', e => {
      this._limitFloatFieldLength(e.currentTarget);
    }).on('keydown', 'input[type="number"]', e => {
      // Number inputs still allow certain characters outside of 0-9.
      if (e.keyCode === 69 || // 'e' key, used for scientific notation
          e.keyCode === 187 || // '=' key (for the '+' sign)
          e.keyCode === 188 || // ',' key
          e.keyCode === 189) { // '-' key
        e.preventDefault();
      }
    }).on('click', `.${Screener.CssClass.REMOVE_PERSON}`, e => {
      this._removePerson(parseInt($(e.currentTarget).data('person'), 10))
          ._renderRecap();
    }).on('click', `.${Screener.CssClass.EDIT_PERSON}`, e => {
      this._editPerson(parseInt($(e.currentTarget).data('person'), 10));
    }).on('keyup', 'input[maxlength]', e => {
      this._enforceMaxLength(e.currentTarget);
    }).on('submit', e => {
      e.preventDefault();

      this._$steps.filter(`.${Screener.CssClass.ACTIVE}`)
        .find(`.${Screener.CssClass.VALIDATE_STEP},` +
        `.${Screener.CssClass.SUBMIT}`).trigger('click');
    });

    /**
     * Determine whether or not to initialize ReCAPTCHA. This should be
     * initialized only on every 10th view which is determined via an
     * incrementing cookie.
     */
    let viewCount = Cookies.get('screenerViews') ?
        parseInt(Cookies.get('screenerViews'), 10) : 1;

    if (viewCount >= 10) {
      this._initRecaptcha();
      viewCount = 0;
    } else {
      window.reCaptchaCallback = () => {};
    }

    // `2/1440` sets the cookie to expire after two minutes.
    Cookies.set('screenerViews', ++viewCount, {
      expires: (2/1440),
      path: Screener.CookiePath
    });

    /**
     * Initial state handler. If in debug mode go to the step requested, else,
     * go to step 1.
     */
    $(document).ready(() => {
      if (Utility.getUrlParameter('debug') === '1') {
        if (window.location.hash)
          this._goToStep(window.location.hash)
            ._reFocus();
      } else {
        this._goToStep('#step-1')
          ._newState('#step-1', 'replaceState')
          ._reFocus();
      }
    });

    /**
     * Initialize Webtrends Scenario analysis
     */
    this._scenarioAnalysis();

    return this;
  }

  /**
   * View Tracking for Webtrends and Google Analytics
   */
  _scenarioAnalysis() {
    let key = '';
    let data = [];

    $(window).on('hashchange load', function() {
      let hash = window.location.hash;
      let step = $(hash);
      key = step.data('trackKey');
      data = step.data('trackData');
      Track.view('Eligibility', key, data);
      if (hash === '#step-8') data = [];
    });

    $('#step-8').on('change', 'label', event => {
      data = $(event.currentTarget).data('trackData');
    });

    $('[href="#step-9"]').on('click', function() {
      if (typeof data === 'undefined') {
        data = $('#step-8-hoh').data('trackData');
      }
      Track.view('Eligibility', key, data);
    });
  }

  /**
   * Collects data about the current state and writes it to the history api
   * either using the 'pushState' or 'replaceState' method provided in the args
   * @param   {string}  hash    Hash of the step to go to including the '#'
   * @param   {string}  method  'pushState' or 'replaceState' history method
   * @return  {object}          The screener class
   */
  _newState(hash, method) {
    let section = $(hash);
    let question = section.find('[data-js="question"]');
    let personIndex = (section.data('personIndex'))
      ? section.data('personIndex') : 0;

    let person = (this._people[personIndex])
      ? this._people[personIndex] : false;
    let applicant = this._people[0];

    let stateObj = {
      step: hash.replace('#', ''),
      persons: this._household._attrs.members,
      applicantIsHeadOfHousehold: applicant.get('headOfHousehold'),
      question: question[0].innerText,
      person: {
        index: personIndex,
        headOfHousehold: (person) ? person.get('headOfHousehold') : false
      }
    };

    window.history[method](stateObj, $('title').html(), [
        window.location.pathname, window.location.search, hash
      ].join(''));

    return this;
  }

  /**
   * Asynchronously loads the Google recaptcha script and sets callbacks for
   * load, success, and expiration.
   * @private
   * @return {this} Screener
   */
  _initRecaptcha() {
    window.reCaptchaCallback = () => {
      window.grecaptcha.render(document.getElementById('screener-recaptcha'), {
        'sitekey': Utility.CONFIG.GRECAPTCHA_SITE_KEY,
        'callback': () => {
          this._recaptchaVerified = true;
          this._removeError(document.getElementById('screener-recaptcha'));
        },
        'expired-callback': () => {
          this._recaptchaVerified = false;
        }
      });

      $('#screener-recaptcha-container').removeClass(Screener.CssClass.HIDDEN);

      this._recaptchaRequired = true;
    };

    // window.screenerRecaptcha = () => {
    //   this._recaptchaVerified = true;
    //   this._removeError(document.getElementById('screener-recaptcha'));
    // };

    // window.screenerRecaptchaReset = () => {
    //   this._recaptchaVerified = false;
    // };

    this._recaptchaRequired = true;

    return this;
  }

  /**
   * Adds and removes active classes to a checkbox. Also appropriate toggles
   * any "none of these" type checkboxes.
   * @param {HTMLElement} el - checkbox input
   * @return {this} Screener
   */
  _toggleCheckbox(el) {
    const $checkbox = $(el);
    const $group = $checkbox.closest(`.${Screener.CssClass.CHECKBOX_GROUP}`);
    if ($checkbox.prop('checked')) {
      if ($checkbox.hasClass(Screener.CssClass.CLEAR_GROUP)) {
        $group.find('input[type="checkbox"]').not(el).prop('checked', false)
            .trigger('change');
      } else {
        $group.find(`.${Screener.CssClass.CLEAR_GROUP}`).prop('checked', false)
            .trigger('change');
      }
    } else {
      if ($group.find('input[type="checkbox"]:checked').length === 0) {
        $group.find(`.${Screener.CssClass.CLEAR_GROUP}`)
            .prop('checked', true).trigger('change');
      }
    }
    return this;
  }

  /**
   * For a given input, if it has the "toggles" data attribute, show or hide
   * another element selected by the toggles values based on the value of the
   * input. If the input has a "shows" or "hides" data attribute, show or hide
   * relevant element accordingly.
   * @private
   * @param {HTMLElement} el - Input/select element.
   * @return {this} Screener
   */
  _handleToggler(el) {
    const $el = $(el);
    if ($el.data('toggles')) {
      const $target = $($el.data('toggles'));
      if (
          ($el.prop('checked') && Boolean(parseInt($el.val(), 10))) ||
          ($el.is('select') && $el.val())
      ) {
        $target.removeClass(Screener.CssClass.HIDDEN);
      } else {
        $target.addClass(Screener.CssClass.HIDDEN);
      }
    }
    if ($el.data('shows')) {
      $($el.data('shows')).removeClass(Screener.CssClass.HIDDEN);
    }
    if ($el.data('hides')) {
      $($el.data('hides')).addClass(Screener.CssClass.HIDDEN);
    }
    return this;
  }

  /**
   * For a given element with a maxlength attribute, enforce the maxlength rule.
   * This is necessary because input[type="number"] elements ignrore the
   * attribute natively.
   * element.
   * @private
   * @param {HTMLElement} el
   * @return {this} Screener
   */
  _enforceMaxLength(el) {
    const $input = $(el);
    const maxlength = parseInt($input.attr('maxlength'), 10);
    const val = $input.val();
    if (val.length > maxlength) {
      $input.val(val.slice(0, maxlength));
    }
    return this;
  }

  /**
   * For a repeatable matrix, like incomes or expenses, this adds
   * the next section based on data attributes from a triggering element.
   * @private
   * @param {HTMLElement} el
   * @return {this} Screener
   */
  _addMatrixSection(el) {
    const $el = $(el);
    const $target = $($el.data('renders'));
    const template = window.JST[`screener/template-${$el.data('matrix')}`];
    const renderedTemplate = template({
      personIndex: parseInt($el.data('personIndex'), 10) || 0,
      matrixIndex: parseInt($el.data('matrixIndex'), 10) || 0
    });

    const $renderTarget = $el.data('renderTarget') ?
        $($el.data('renderTarget')) :
        $el.closest(`.${Screener.CssClass.MATRIX}`);

    if ($target.length) {
      $target.removeClass(Screener.CssClass.HIDDEN);
    } else if (!$el.data('renderTarget') ||
        !$renderTarget.find(`.${Screener.CssClass.MATRIX_ITEM}`).length) {
      $renderTarget.append(renderedTemplate);
    }

    return this;
  }

  /**
   * For a select element in a repeating matrix, if a value exists for the
   * selected option, update the labels within the matrix item based on the
   * select element's value. Otherwise, remove it.
   * @private
   * @param {HTMLElement} el
   * @return {this} Screener
   */
  _toggleMatrix(el) {
    const $el = $(el);
    const $matrixItem = $el.closest(`.${Screener.CssClass.MATRIX_ITEM}`);
    if ($el.val()) {
      $matrixItem.find(Screener.Selectors.TRANSACTION_LABEL)
          .text($el.find('option:selected').text());
    } else if (!$matrixItem.is(':last-of-type')) {
      $matrixItem.remove();
    }
    return this;
  }

  /**
   * Refocus the window on the questionaire
   * @return {this} Screener
   */
  _reFocus() {
    $(window).scrollTop($(Screener.Selectors.VIEW).offset().top);

    return this;
  }

  /**
   * Adds the active class to the provided section. Removes it from all other
   * sections.
   * @param  {string}  hash  '#' and id of the step to go to
   * @return {this}          The screener class
   */
  _goToStep(hash) {
    let section = $(hash);

    if (!section.length || !section.hasClass(Screener.CssClass.STEP))
     return this;

    this._$steps
      .removeClass(Screener.CssClass.SCREENER_STEP_ACTIVE)
      .addClass(Screener.CssClass.SCREENER_STEP_HIDDEN)
      .attr('aria-hidden', 'true')
      .find(':input, a')
      .attr('tabindex', '-1');

    $(section)
      .removeClass(Screener.CssClass.SCREENER_STEP_HIDDEN)
      .addClass(Screener.CssClass.SCREENER_STEP_ACTIVE)
      .removeAttr('aria-hidden')
      .find(':input, a')
      .removeAttr('tabindex');

    if ($(section).attr('id') === 'step-9') {
      // add in family members here
      const members = [];

      _.each(this._people.slice(0,
          this._household.get('members')), (person, i) => {
        const member = {
          age: person.get('age'),
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

        members.push(member);
      });

      const summary = window.JST['screener/template-member-summary'];
      const renderedSummaryTemplate = summary({
        members: members
      });

      $('#screener-household-summary').html(renderedSummaryTemplate);

      // Render member form.
      let personIndex = null;

      if ($(section).data('personIndex')) {
        personIndex = parseInt($(section).data('personIndex'), 10);
      } else {
        personIndex = this._people.length;
        $(section).data('personIndex', personIndex);
      }

      const templateData = {
        personIndex: personIndex,
        person: new ScreenerPerson().toObject(),
        localize: Utility.localize
      };

      if (this._people[personIndex]) {
        templateData.person = this._people[personIndex].toObject();
      }

      const member = window.JST['screener/template-member'];
      const renderedFormTemplate = member(templateData);

      $('#screener-household-member').html(renderedFormTemplate);

      return this;
    }

    if ($(section).attr('id') === 'step-10') {
      // add in family members here
      const people = [];
      _.each(this._people, (person, i) => {
        const obj = {
          age: person.get('age'),
          owner: person.get('livingOwnerOnDeed'),
          leasee: person.get('livingRentalOnLease')
        };
        if (i === 0) {
          obj.relation = Utility.localize('Self');
        } else if (person.get('headOfHousehold')) {
          obj.relation = Utility.localize('HeadOfHousehold');
        } else {
          obj.relation =
              Utility.localize(person.get('headOfHouseholdRelation'));
        }
        people.push(obj);
      });

      const option = window.JST['screener/template-member-option'];
      const ownerTemplate = option({
        attribute: 'livingOwnerOnDeed',
        people: people
      });
      $('#screener-possible-owners').html(ownerTemplate);

      const leaseeTemplate = option({
        attribute: 'livingRentalOnLease',
        people: people
      });
      $('#screener-possible-leasees').html(leaseeTemplate);
    }

    if ($(section).attr('id') === 'recap') {
      this._renderRecap();
    }

    let stepTitle = $(section).find('[data-js="step-title"]').text();
    $('title').text(`${stepTitle} - ${this._baseTitle}`);

    return this;
  }

  /**
   * Validate and process data for each section.
   * @param {jQuery} $step - step to validate
   * @return {boolean} whether the step is valid
   */
  _validateStep($step) {
    const stepId = $step.attr('id');

    $step.find(`.${Screener.CssClass.ERROR}`)
        .removeClass(Screener.CssClass.ERROR).end()
        .find(`.${Screener.CssClass.ERROR_MSG}`).remove();

    $step.find(':input:visible').filter('[required]').each((i, el) => {
      this._validateRequiredField(el);
    }).end().filter('[data-type="integer"]').each((i, el) => {
      this._validateIntegerField(el);
    }).end().filter('[data-type="float"]').each((i, el) => {
      this._validateFloatField(el);
    }).end().filter('[name="Household.zip"]').each((i, el) => {
      this._validateZipField(el);
    });

    const $errors = $step.find(`.${Screener.CssClass.ERROR}:visible`);

    if ($errors.length) {
      const $firstError = $errors.first()
        .closest(Screener.Selectors.QUESTION);

      $firstError.find(':input').first().focus();
      $(window).scrollTop($firstError.offset().top);

      return false;
    }

    let stepValid = true;

    switch (stepId) {
      case 'step-1': {
        // Add program categories.
        const categories = [];
        $step.find('input[name="category"]:checked').each((i, el) => {
          categories.push($(el).val());
        });

        this._household.set('programCategories', categories);
        break;
      }
      case 'step-2': {
        // Nothing to process here.
        break;
      }
      case 'step-3': {
        // Set submitter age and household.
        this._people[0].set('age',
            parseInt($step.find('input[name="Person[0].age"]').val(), 10));
        this._household.set('city', 'NYC')
            .set('zip', $step.find('input[name="Household.zip"]').val());
        break;
      }
      case 'step-4': {
        // Set all checked attributes. Unset any that are not checked.
        $step.find(`.${Screener.CssClass.CHECKBOX_GROUP}`).find(':input')
            .each((i, el) => {
              if ($(el).val() && $(el).attr('name')) {
                const key = $(el).attr('name').split('.')[1];
                if ($(el).is(':visible') && $(el).is(':checked')) {
                  this._people[0].set(key, Screener.getTypedVal(el));
                } else {
                  this._people[0].set(key, false);
                }
              }
            });
        // Set the attribute according to the radio button value.
        $step.find(`.${Screener.CssClass.RADIO_GROUP}`)
            .find(':input:checked').each((i, el) => {
              if ($(el).val() && $(el).attr('name')) {
                const key = $(el).attr('name').split('.')[1];
                if ($(el).is(':visible')) {
                  if ($(el).is(':checked')) {
                    this._people[0].set(key, Screener.getTypedVal(el));
                  }
                } else {
                  this._people[0].set(key, false);
                }
              }
            });
        break;
      }
      case 'step-5':
      case 'step-6': {
        // For step 5, add incomes. For step 6, add expenses.
        const key = stepId === 'step-5' ? 'incomes' : 'expenses';
        const person = this._people[0];
        person.set(key, []);
        $step.find('[name$="amount"]').filter(':visible').each((i, el) => {
          const itemIndex = $(el).attr('name').split('[').pop().split(']')[0];
          const amount = Screener.getTypedVal(el);
          const type = Screener.getTypedVal(
              $step.find(`[name="Person[0].${key}[${itemIndex}].type"]`)[0]);
          const frequency = Screener.getTypedVal($step
              .find(`[name="Person[0].${key}[${itemIndex}].frequency"]`)[0]);
          if (amount && type && frequency) {
            if (key === 'incomes') {
              person.addIncome(amount, type, frequency);
            } else {
              person.addExpense(amount, type, frequency);
            }
          }
        });
        break;
      }
      case 'step-7': {
        const $memberInput =
            $step.find('input[name="Household.members"]');
        const memberCount = Screener.getTypedVal($memberInput[0]);

        // Verify that the inputted value is at least one and not greater than
        // the maximum household size.
        if (memberCount < 1 ||
            memberCount > Utility.CONFIG.SCREENER_MAX_HOUSEHOLD) {
          this._showError($memberInput[0], Screener.ErrorMessage.HOUSEHOLD);
          $(window).scrollTop(0);
          stepValid = false;
        } else {
          this._household.set('members', memberCount);
        }

        // If there is only one member, ensure that they are the head of the
        // household and proceed to the final step, returning `false` to
        // prevent the default hash change.
        if (memberCount === 1) {
          this._people[0].set({
            headOfHousehold: true,
            headOfHouseholdRelation: ''
          });

          this._goToStep('#step-10')
            ._newState('#step-10', 'pushState')
            ._reFocus();

          return false;
        }
        break;
      }
      case 'step-8':
      case 'step-9': {
        const personIndex = stepId === 'step-9' ? $step.data('personIndex') : 1;
        const member = this._people[personIndex] || new ScreenerPerson();
        // If this is step 8 set up the Head of the Household Relationship
        // for the submitter.
        if (stepId === 'step-8') {
          const $hohInput =
              $step.find('input[name="Person[0].headOfHousehold"]:checked');
          // If the current user is the HoH, update their status and break.
          if (Screener.getTypedVal($hohInput[0])) {
            this._people[0].set({
              headOfHousehold: true,
              headOfHouseholdRelation: ''
            });
            break;
          } else {
            member.set('headOfHousehold', true);
            this._people[0].set({
              headOfHousehold: false,
              headOfHouseholdRelation: $step
                .find('select[name="Person[0].headOfHouseholdRelation"]').val()
            });
          }
        } else {
          // Set member's relations to HOH.
          member.set({
            headOfHousehold: false,
            headOfHouseholdRelation: $step.find(
              `select[name="Person[${personIndex}].headOfHouseholdRelation"]`)
              .val()
            });
        }

        member.set('age', Screener.getTypedVal($step
            .find(`input[name="Person[${personIndex}].age"]`)[0]));

        // Set member attributes and benefits (checkbox groups)
        $step.find(`.${Screener.CssClass.CHECKBOX_GROUP}`)
            .find('input') // get all checkboxes...
            .filter(`[name^="Person[${personIndex}]"]`)
            .each((i, el) => {
              const key = $(el).attr('name').split('.')[1];
               // ...and set them to their prop value
              member.set(key, $(el).prop('checked'));
            });

        // Set member attrs for radio button groups
        $step.find(`.${Screener.CssClass.RADIO_GROUP}`)
            .find('input:checked') // only get checked radios...
            .filter(`[name^="Person[${personIndex}]"]`)
            .each((i, el) => {
              const key = $(el).attr('name').split('.')[1];
              // ...and set them to their typed value
              member.set(key, Screener.getTypedVal(el));
            });

        // Add income and expenses.
        member.set({
          incomes: [],
          expenses: []
        });

        _.each(['incomes', 'expenses'], key => {
          $step.find('[name$="amount"]').filter(':visible')
              .filter(`[name*="${key}"]`).each((i, el) => {
            const itemIndex = $(el).attr('name').split('[').pop().split(']')[0];
            const amount = Screener.getTypedVal(el);
            const type = Screener.getTypedVal($step.find(
                `[name="Person[${personIndex}].${key}[${itemIndex}]` +
                `.type"]`)[0]);
            const frequency = Screener.getTypedVal($step.find(
                `[name="Person[${personIndex}].${key}[${itemIndex}]` +
                `.frequency"]`)[0]);
            if (amount && type && frequency) {
              if (key === 'incomes') {
                member.addIncome(amount, type, frequency);
              } else {
                member.addExpense(amount, type, frequency);
              }
            }
          });
        });

        this._people[personIndex] = member;

        if (stepId === 'step-8') {
          // If adding a HoH meets the household size, skip ahead to step 10.
          if (this._people.length >= this._household.get('members')) {
            this._goToStep('#step-10')
              ._newState('#step-10', 'pushState')
              ._reFocus();

            return false;
          }
        } else {
          // If we need to add more non-HoH household members, repeat this step.
          if (this._people.length < this._household.get('members')) {
            $step.data('personIndex', personIndex + 1);

            this._goToStep(`#${$step[0].id}`)
              ._newState(`#${$step[0].id}`, 'pushState')
              ._reFocus();

            return false;
          }
        }

        break;
      }
      case 'step-10': {
        // Big hack fix here. For some reason, the previous break statement
        // just two lines up doesn't actually break out of the switch
        // (tested in Chrome) and will fire this case. So we are doing an
        // additional check to make sure we are only handling the apprpirate
        // case in  question.
        if (stepId !== 'step-10') {
          break;
        }
        // End big hack fix.

        // Set the type of the household.
        $step.find('input[name^="Household"]').each((i, el) => {
          if ($(el).val()) {
            const key = $(el).attr('name').split('.')[1];
            if ($(el).prop('checked')) {
              this._household.set(key, Screener.getTypedVal(el));
            } else {
              this._household.set(key, false);
            }
          }
        });

        // Set or unset the household members who are on the lease or deed.
        _.each(['livingOwnerOnDeed', 'livingRentalOnLease'], type => {
          const $inputs = $step.find(`input[name$="${type}"]:visible`);
          if ($inputs.length) {
            if ($inputs.filter(':checked').length) {
              $inputs.each((i, el) => {
                const personIndex =
                    parseInt($(el).attr('name').split(']')[0].split('[')[1],
                    10);
                const person = this._people[personIndex];
                if (person) {
                  person.set(type, $(el).prop('checked'));
                }
              });
            } else {
              this._showError($inputs[0], Screener.ErrorMessage.REQUIRED);
              // If the screener step is not yet invalid, scroll to the first
              // error.
              if (stepValid) {
                $(window).scrollTop(0);
              }
              stepValid = false;
            }
          } else {
            _.each(this._people, person => {
              person.set(type, false);
            });
          }
        });

        // Set the rental type.
        const $rentalType =
            $step.find('select[name="Household.livingRentalType"]');
        if ($rentalType.is(':visible')) {
          this._household.set('livingRentalType', $rentalType.val());
        } else {
          this._household.set('livingRentalType', '');
        }

        this._household.set('cashOnHand', Screener.getTypedVal($step
            .find('input[name="Household.cashOnHand"]')));

        break;
      }

      default: {
        stepValid = false;
        break;
      }
    }

    return stepValid;
  }

  /**
   * Removes error messages on a given input.
   * @param {HTMLELement} el - Input element.
   * @return {this} Screener
   */
  _removeError(el) {
    $(el).closest(Screener.Selectors.QUESTION_CONTAINER)
        .removeClass(Screener.CssClass.ERROR)
        .find(`.${Screener.CssClass.ERROR_MSG}`).remove();
    return this;
  }

  /**
   * Displays an error message by taking an input element, finding its
   * container, adding an error class to it, and then prepending an error
   * message to the container.
   * @param {HTMLELement} el - Input element in error.
   * @param {string} msg - Error message to display.
   * @return {this} Screener
   */
  _showError(el, msg) {
    const $error = $(document.createElement('div'));
    $error.attr('aria-live', 'polite');
    $error.addClass(Screener.CssClass.ERROR_MSG).text(Utility.localize(msg));
    $(el).closest(Screener.Selectors.QUESTION_CONTAINER)
        .addClass(Screener.CssClass.ERROR).prepend($error);
    return this;
  }

  /**
   * For a given input, checks for whether it is checked or has a value. If it
   * does not, displays an error message and binds an event listener that
   * that reruns validation on change.
   * @param {HTMLELement} el - Input element to validate.
   * @return {this} Screener
   */
  _validateRequiredField(el) {
    const $input = $(el);
    this._removeError(el);
    if ((($input.attr('type') === 'checkbox' ||
        $input.attr('type') === 'radio') && !$input.prop('checked')) ||
        (($input.attr('type') !== 'checkbox' ||
        $input.attr('type') !== 'radio') && !$input.val())) {
      // if ($input.attr('data-type')) {
      //   this._showError(el,
      //     Screener.ErrorMessage[$input.attr('data-type').toUpperCase()]
      //   );
      // } else {
      this._showError(el, Screener.ErrorMessage.REQUIRED);
      // }
      $input.one('change keyup', () => {
        this._validateRequiredField(el);
      });
    }
    return this;
  }

  /**
   * For a given input, if the input has a value and can be coerced to a
   * positive integer then enforce that. Otherwise, show an error message.
   * @param {HTMLELement} el - Input element to validate.
   * @return {this} Screener
   */
  _validateIntegerField(el) {
    const $input = $(el);
    const val = $input.val();
    this._removeError(el);

    // If there is a value for the element, make sure that it is rounded to
    // an integer and not negative.
    if (val && !_.isNaN(parseInt(val, 10)) && _.isNumber(parseInt(val, 10))) {
      let parsed = Math.abs(parseInt(val, 10));
      $input.val(parsed);
    } else if (val) {
      // Otherwise, show an error message as long as a value was entered.
      this._showError(el, Screener.ErrorMessage.INTEGER);
      $input.one('keyup', () => {
        this._validateIntegerField(el);
      });
    } else if ($input.prop('required')) {
      this._validateRequiredField(el);
    }

    return this;
  }

  /**
   * For a given input, if the input has a value and can be coerced to a
   * float with two decimal points then enforce that. Otherwise, show an error
   * message.
   * @param {HTMLELement} el - Input element to validate.
   * @return {this} Screener
   */
  _validateFloatField(el) {
    const $input = $(el);
    const val = $input.val();
    this._removeError(el);

    // If there is a value for the element, make sure that it is rounded to
    // an integer and not negative.
    if (val && !_.isNaN(parseFloat(val)) && _.isNumber(parseFloat(val))) {
      let sanitizedVal = val.replace(/[eE\+\-]+/g, '');
      $input.val(Utility.toDollarAmount(sanitizedVal));
    } else if (val) {
      // Otherwise, show an error message as long as a value was entered.
      this._showError(el, Screener.ErrorMessage.FLOAT);
      $input.one('change', () => {
        this._validateFloatField(el);
      });
    } else if ($input.prop('required')) {
      this._validateRequiredField(el);
    }

    return this;
  }

  /**
   * For a given dollar float input, product requirements dictate we should
   * limit values to 6 digits before the decimal point and 2 after.
   * @param {HTMLELement} el - Input element to validate.
   * @return {this} Screener
   */
  _limitFloatFieldLength(el) {
    const $input = $(el);
    const val = $input.val();
    const components = val.split('.');
    let valAltered = false;
    _.each(components, (component, i) => {
      const maxlength = i === 0 ? 6 : 2;
      if (component.length > maxlength) {
        components[i] = component.slice(0, maxlength);
        valAltered = true;
      }
    });
    if (valAltered) {
      $input.val(components.join('.'));
    }
    return this;
  }

  /**
   * Checks to see if the input's value is a valid NYC zip code.
   * @param {HTMLELement} el - Input element to validate.
   * @return {this} Screener
   */
  _validateZipField(el) {
    const $input = $(el);
    const val = $input.val();
    this._removeError(el);

    if (val) {
      const formattedVal = val.substring(0, 5);
      if (Utility.NYC_ZIPS.indexOf(formattedVal) >= 0) {
        $input.val(formattedVal);
      } else {
        this._showError(el, Screener.ErrorMessage.ZIP);
        $input.one('keyup', () => {
          this._validateZipField(el);
        });
      }
    } else if ($input.prop('required')) {
      this._validateRequiredField(el);
    }

    return this;
  }

  /**
   * Assembles data for the recap view and renders the recap template.
   * @private
   * @return {this} Screener
   */
  _renderRecap() {
    const templateData = {
      categories: [],
      household: {
        assets: `$${this._household.get('cashOnHand')}`,
        owners: [],
        rentalType: '',
        renters: [],
        types: [],
        zip: this._household.get('zip')
      },
      members: []
    };

    // Add programs.
    _.each(this._household.get('programCategories'), category => {
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
    _.each(housingTypes, type => {
      if (this._household.get(`living${type}`)) {
        const obj = {
          slug: type,
          label: Utility.localize(`living${type}`)
        };

        templateData.household.types.push(obj);
      }

      if (type === 'Renting') {
        templateData.household.rentalType =
            Utility.localize(this._household.get('livingRentalType'));
      }
    });

    // Add household member data.
    _.each(this._people.slice(0, this._household.get('members')),
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

      _.each(['incomes', 'expenses'], type => {
        _.each(person.get(type), item => {
          const obj = {
            amount: `$${item.amount}`,
            type: Utility.localize(item.type),
            frequency: Utility.localize(item.frequency)
          };
          member[type].push(obj);
        });
      });

      _.each(['livingOwnerOnDeed', 'livingRentalOnLease'], type => {
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

    const template = window.JST['screener/template-recap'];
    const renderedTemplate = template(templateData);
    $('#recap-body').html(renderedTemplate);

    // Reset personIndex to zero on edit the head of household.
    // let editPersonButton = document.getElementById('recap-edit-person');
    // editPersonButton.addEventListener('click', function resetPersonIndex() {
    //   this._$steps.data('personIndex', 0);
    // }.bind(this));

    return this;
  }

  /**
   * Removes a user at index `i` from this._people.
   * @private
   * @param {Number} i - index of user.
   * @return {this} Screener
   */
  _removePerson(i) {
    let el = $(this._el);

    this._people.splice(i, 1);
    this._household.set('members', this._people.length);

    el.find('input[name="Household.members"]')
        .val(this._people.length);

    return this;
  }

  /**
   * Navigates the user to the edit screen for the person at index `i`
   * in this._people. If `i` is 0, then the user goes to step 3. If it is 1
   * and that person is Head of the Household, go to step 8. Otherwise, set
   * the proper data attribute of step 9 and navigate there.
   * @private
   * @param {Number} i - index of user.
   * @return {this} Screener
   */
  _editPerson(i) {
    let hash = '#';

    if (i === 0) {
      hash = '#step-3';
    } else if (i === 1 && this._people[i].get('headOfHousehold')) {
      hash = '#step-8';
    } else {
      $('#step-9').data('personIndex', i);
      hash = '#step-9';
    }

    this._goToStep(hash)
      ._newState(hash, 'pushState')
      ._reFocus();

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
    _.each(this._people.slice(0, this._household.get('members')), person => {
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
    // Set the language of the household
    this._household.set('lang', $('html').attr('lang'));

    $(`.${Screener.CssClass.SUBMIT}`).hide();
    $(Screener.Selectors.SPINNER).show();

    /* eslint-disable no-console, no-debugger */
    if (Utility.getUrlParameter('debug') === '1') {
      console.dir(this);
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
    }).done(data => {
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

        $(`.${Screener.CssClass.SUBMIT}`).show();
        $(Screener.Selectors.SPINNER).hide();
        /* eslint-enable no-console, no-debugger */
      }
      const programs = _.chain(Utility.findValues(data, 'code'))
          .filter(item => _.isString(item)).uniq().value();
      const params = {};
      if (this._household.get('programCategories').length) {
        params.categories = this._household.get('programCategories').join(',');
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
      window.location = `./results`;
    });
  }
}

/**
 * Returns the value of a supplied input in the type defined by a data-type
 * attribute on that input.
 * @param {HTMLElement} input
 * @return {boolean|Number|string} typed value
 */
Screener.getTypedVal = function(input) {
  const $input = $(input);
  const val = $input.val();
  let finalVal = $input.val();
  switch ($input.data('type')) {
    case Screener.InputType.BOOLEAN: {
      finalVal = Boolean(parseInt(val, 10));
      break;
    }
    case Screener.InputType.FLOAT: {
      finalVal = (_.isNumber(parseFloat(val)) && !_.isNaN(parseFloat(val))) ?
          parseFloat(val) : 0;
      break;
    }
    case Screener.InputType.INTEGER: {
      finalVal = (_.isNumber(parseInt(val, 10)) &&
          !_.isNaN(parseInt(val, 10))) ?
          parseInt($input.val(), 10) : 0;
      break;
    }
  }
  return finalVal;
};

/**
 * CSS classes used by this component.
 * @enum {string}
 */
Screener.CssClass = {
  ACTIVE: 'active',
  ADD_SECTION: 'js-add-section',
  CHECKBOX_GROUP: 'js-screener-checkbox-group',
  CLEAR_GROUP: 'js-clear-group',
  EDIT_PERSON: 'js-edit-person',
  ERROR: 'error',
  ERROR_MSG: 'error-message',
  FORM: 'js-screener-form',
  HIDDEN: 'hidden',
  MATRIX: 'js-screener-matrix',
  MATRIX_ITEM: 'js-matrix-item',
  MATRIX_SELECT: 'js-matrix-select',
  RADIO_GROUP: 'js-screener-radio-group',
  REMOVE_PERSON: 'js-remove-person',
  TOGGLE: 'js-screener-toggle',
  SCREENER_STEP_ACTIVE: 'animated active fadeIn',
  SCREENER_STEP_HIDDEN: 'animated hidden',
  STEP: 'js-screener-step',
  SUBMIT: 'js-screener-submit',
  VALIDATE_STEP: 'js-screener-validate-step'
};

/**
 * Selectors for elements
 * @type {Object}
 */
Screener.Selectors = {
  QUESTION: '.c-question',
  QUESTION_CONTAINER: '.c-question__container',
  TRANSACTION_LABEL: '[data-js="transaction-label"]',
  SPINNER: '[data-js="spinner"]',
  VIEW: '[data-js="view"]',
  FORM: '[data-js="screener"]'
};

/**
 * Localization labels of error messages.
 * @enum {string}
 */
Screener.ErrorMessage = {
  FLOAT: 'ERROR_FLOAT',
  HOUSEHOLD: 'ERROR_HOUSEHOLD',
  INTEGER: 'ERROR_INTEGER',
  REQUIRED: 'ERROR_REQUIRED',
  ZIP: 'ERROR_ZIP',
  AGE: 'ERROR_AGE'
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

/**
 * The cookie path for the screener cookies
 */
Screener.CookiePath = 'eligibility';

export default Screener;
