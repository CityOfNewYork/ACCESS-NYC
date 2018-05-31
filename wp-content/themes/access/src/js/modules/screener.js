/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Cookies from 'js-cookie';
import ScreenerHousehold from 'modules/screener-household';
import ScreenerPerson from 'modules/screener-person';
import Utility from 'modules/utility';
import _ from 'underscore';

/**
 * This component is the controller for the program screener. There's a lot
 * here but essentially how it works is that a hashchange listener is used to
 * progress the user through the screener steps. As they submit each step,
 * a number of validations occur and if everything checks out for that step,
 * the ScreenerPerson and ScreenerHousehold objects are update or created.
 * When the screener is submitted, these objects are compiled into the proper
 * formatting for the Drools rules engine and sent off to the Drools Proxy.
 * Assuming a successful response is received, we then redirect the user to
 * the screener results page, building a redirect URL based on the program
 * codes in the Droosl results, the categories they selected in step 1, the
 * current time, and a `guid` parameter provided by the Drools proxy. The
 * screener relies on Underscore templates to render any dynamic views, and
 * relies on the Utility.localize function to translate any strings within
 * those views to the current language.
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

    window.addEventListener('hashchange', (e) => {
      const hash = window.location.hash;
      const $section = $(hash);
      if ($section.length && $section.hasClass(Screener.CssClass.STEP)) {
        this._goToStep($section[0]);
        $(window).scrollTop(0);
      }
    });

    $(this._el).on('change', 'input[type="checkbox"]', (e) => {
      this._toggleCheckbox(e.currentTarget);
    }).on('change', `.${Screener.CssClass.TOGGLE}`, (e) => {
      this._handleToggler(e.currentTarget);
    }).on('change', `.${Screener.CssClass.ADD_SECTION}`, (e) => {
      this._addMatrixSection(e.currentTarget);
    }).on('change', `.${Screener.CssClass.MATRIX_SELECT}`, (e) => {
      this._toggleMatrix(e.currentTarget);
    }).on('click', `.${Screener.CssClass.VALIDATE_STEP}`, (e) => {
      const $step = $(e.currentTarget).closest(`.${Screener.CssClass.STEP}`);
      return this._validateStep($step);
    }).on('click', `.${Screener.CssClass.SUBMIT}`, (e) => {
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
    }).on('blur', '[data-type="integer"]', (e) => {
      this._validateIntegerField(e.currentTarget);
    }).on('blur', '[data-type="float"]', (e) => {
      this._validateFloatField(e.currentTarget);
    }).on('blur', '[data-type="zip"]', (e) => {
      this._validateZipField(e.currentTarget);
    }).on('keyup', '[data-type="float"]', (e) => {
      this._limitFloatFieldLength(e.currentTarget);
    }).on('keydown', 'input[type="number"]', (e) => {
      // Number inputs still allow certain characters outside of 0-9.
      if (e.keyCode === 69 || // 'e' key, used for scientific notation
          e.keyCode === 187 || // '=' key (for the '+' sign)
          e.keyCode === 188 || // ',' key
          e.keyCode === 189) { // '-' key
        e.preventDefault();
      }
    }).on('click', `.${Screener.CssClass.REMOVE_PERSON}`, (e) => {
      this._removePerson(parseInt($(e.currentTarget).data('person'), 10))
          ._renderRecap();
    }).on('click', `.${Screener.CssClass.EDIT_PERSON}`, (e) => {
      this._editPerson(parseInt($(e.currentTarget).data('person'), 10));
    }).on('keyup', 'input[maxlength]', (e) => {
      this._enforceMaxLength(e.currentTarget);
    }).on('submit', (e) => {
      e.preventDefault();
      this._$steps.filter(`.${Screener.CssClass.ACTIVE}`)
        .find(`.${Screener.CssClass.VALIDATE_STEP},` +
        `.${Screener.CssClass.SUBMIT}`).trigger('click');
    });

    // Determine whether or not to initialize ReCAPTCHA. This should be
    // initialized only on every 10th view which is determined via an
    // incrementing cookie.
    let viewCount = Cookies.get('screenerViews') ?
        parseInt(Cookies.get('screenerViews'), 10) : 1;
    if (viewCount >= 10) {
      this._initRecaptcha();
      viewCount = 0;
    }
    // `2/1440` sets the cookie to expire after two minutes.
    Cookies.set('screenerViews', ++viewCount, {expires: (2/1440)});

    if (Utility.getUrlParameter('debug') === '1') {
      if (window.location.hash) {
        this._goToStep($(window.location.hash)[0]);
      }
    } else {
      window.location.hash = this._$steps.eq(0).attr('id');
      this._goToStep(this._$steps[0]);
    }

    this._scenarioAnalysis();

    return this;
  }

  /**
   * View Tracking for Webtrends and Google Analytics
   */
  _scenarioAnalysis() {
    let key = '';
    let data = [];

    $(window).on('hashchange', function() {
      let hash = window.location.hash;
      let step = $(hash);
      key = step.data('trackKey');
      data = step.data('trackData');
      Utility.trackView('Eligibility', key, data);
      if (hash === '#step-8') data = [];
    });

    $('#step-8').on('change', 'label', (event) => {
      data = $(event.currentTarget).data('trackData');
    });

    $('[href="#step-9"]').on('click', function() {
      if (typeof data === 'undefined') {
        data = $('#step-8-hoh').data('trackData');
      }
      Utility.trackView('Eligibility', key, data);
    });
  }

  /**
   * Asynchronously loads the Google recaptcha script and sets callbacks for
   * load, success, and expiration.
   * @private
   * @return {this} Screener
   */
  _initRecaptcha() {
    const $script = $(document.createElement('script'));
    $script.attr('src',
        'https://www.google.com/recaptcha/api.js' +
        '?onload=screenerCallback&render=explicit').prop({
      async: true,
      defer: true
    });

    window.screenerCallback = () => {
      window.grecaptcha.render(document.getElementById('screener-recaptcha'), {
        'sitekey': Utility.CONFIG.GRECAPTCHA_SITE_KEY,
        'callback': 'screenerRecaptcha',
        'expired-callback': 'screenerRecaptchaReset'
      });
      $('#screener-recaptcha-container').removeClass(Screener.CssClass.HIDDEN);
      this._recaptchaRequired = true;
    };

    window.screenerRecaptcha = () => {
      this._recaptchaVerified = true;
      this._removeError(document.getElementById('screener-recaptcha'));
    };

    window.screenerRecaptchaReset = () => {
      this._recaptchaVerified = false;
    };

    this._recaptchaRequired = true;
    $('head').append($script);
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
    const template = $(`#screener-${$el.data('matrix')}-template`).html();
    const renderedTemplate = _.template(template)({
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
      $matrixItem.find(`.${Screener.CssClass.TRANSACTION_LABEL}`)
          .text($el.find('option:selected').text());
    } else if (!$matrixItem.is(':last-of-type')) {
      $matrixItem.remove();
    }
    return this;
  }

  /**
   * Adds the active class to the provided section. Removes it from all other
   * sections.
   * @param {HTMLElement} section - section to activate.
   * @return {this} Screener
   */
  _goToStep(section) {
    this._$steps.removeClass(Screener.CssClass.ACTIVE)
        .attr('aria-hidden', 'true').find(':input, a').attr('tabindex', '-1')
        .end().filter(section).addClass(Screener.CssClass.ACTIVE)
        .removeAttr('aria-hidden').find(':input, a').removeAttr('tabindex');

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
      const summaryTemplate = $('#screener-member-summary-template').html();
      const renderedSummaryTemplate = _.template(summaryTemplate)({
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
      const formTemplate = $('#screener-member-template').html();
      const templateData = {
        personIndex: personIndex,
        person: new ScreenerPerson().toObject(),
        localize: Utility.localize
      };

      if (this._people[personIndex]) {
        templateData.person = this._people[personIndex].toObject();
      }

      const renderedFormTemplate = _.template(formTemplate)(templateData);
      $('#screener-household-member').html(renderedFormTemplate);
    }

    if ($(section).attr('id') === 'step-10') {
      // add in family members here
      const template = $('#screener-member-option-template').html();
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

      const ownerTemplate = _.template(template)({
        attribute: 'livingOwnerOnDeed',
        people: people
      });
      $('#screener-possible-owners').html(ownerTemplate);

      const leaseeTemplate = _.template(template)({
        attribute: 'livingRentalOnLease',
        people: people
      });
      $('#screener-possible-leasees').html(leaseeTemplate);
    }

    if ($(section).attr('id') === 'recap') {
      this._renderRecap();
    }

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
          .closest(`.${Screener.CssClass.QUESTION_CONTAINER}`);

      $firstError.find(':input').first().focus();
      $(window).scrollTop(0);

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
        this._categories = categories;
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
          window.location.hash = '#step-10';
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
                  .find('select[name="Person[0].headOfHouseholdRelation"]')
                  .val()
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
        _.each(['incomes', 'expenses'], (key) => {
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
            window.location.hash = '#step-10';
            return false;
          }
        } else {
          // If we need to add more non-HoH household members, repeat this step.
          if (this._people.length < this._household.get('members')) {
            $step.data('personIndex', personIndex + 1);
            this._goToStep($step[0]);
            $(window).scrollTop(0);
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
        _.each(['livingOwnerOnDeed', 'livingRentalOnLease'], (type) => {
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
            _.each(this._people, (person) => {
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
    $(el).closest(`.${Screener.CssClass.QUESTION_CONTAINER}`)
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
    $error.addClass(Screener.CssClass.ERROR_MSG).text(Utility.localize(msg));
    $(el).closest(`.${Screener.CssClass.QUESTION_CONTAINER}`)
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
      this._showError(el, Screener.ErrorMessage.REQUIRED);
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
      if (Screener.NYC_ZIPS.indexOf(formattedVal) >= 0) {
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
    _.each(this._categories, (category) => {
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
    return this;
  }

  /**
   * Removes a user at index `i` from this._people.
   * @private
   * @param {Number} i - index of user.
   * @return {this} Screener
   */
  _removePerson(i) {
    this._people.splice(i, 1);
    this._household.set('members', this._people.length);
    $(this._el).find('input[name="Household.members"]')
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
    if (i === 0) {
      window.location.hash = '#step-3';
    } else if (i === 1 && this._people[i].get('headOfHousehold')) {
      window.location.hash = '#step-8';
    } else {
      $('#step-9').data('personIndex', i);
      window.location.hash = '#step-9';
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
  QUESTION_CONTAINER: 'screener-question-container',
  TOGGLE: 'js-screener-toggle',
  STEP: 'js-screener-step',
  SUBMIT: 'js-screener-submit',
  TRANSACTION_LABEL: 'screener-transaction-type',
  VALIDATE_STEP: 'js-screener-validate-step'
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
  ZIP: 'ERROR_ZIP'
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
 * Valid zip codes in New York City. Source:
 * https://data.cityofnewyork.us/City-Government/Zip-code-breakdowns/6bic-qvek
 * @type {array<String>}
 */
Screener.NYC_ZIPS = ['10451', '10452', '10453', '10454', '10455', '10456',
    '10457', '10458', '10459', '10460', '10461', '10462', '10463',
    '10464', '10465', '10466', '10467', '10468', '10469', '10470',
    '10471', '10472', '10473', '10474', '10475', '10499', '11201',
    '11202', '11203', '11204', '11205', '11206', '11207', '11208',
    '11209', '11210', '11211', '11212', '11213', '11214', '11215',
    '11216', '11217', '11218', '11219', '11220', '11221', '11222',
    '11223', '11224', '11225', '11226', '11228', '11229', '11230',
    '11231', '11232', '11233', '11234', '11235', '11236', '11237',
    '11238', '11239', '11240', '11241', '11242', '11243', '11244',
    '11245', '11247', '11248', '11249', '11251', '11252', '11254',
    '11255', '11256', '10001', '10002', '10003', '10004', '10005',
    '10006', '10007', '10008', '10009', '10010', '10011', '10012',
    '10013', '10014', '10015', '10016', '10017', '10018', '10019',
    '10020', '10021', '10022', '10023', '10024', '10025', '10026',
    '10027', '10028', '10029', '10030', '10031', '10032', '10033',
    '10034', '10035', '10036', '10037', '10038', '10039', '10040',
    '10041', '10043', '10044', '10045', '10046', '10047', '10048',
    '10055', '10060', '10065', '10069', '10072', '10075', '10079',
    '10080', '10081', '10082', '10087', '10090', '10094', '10095',
    '10096', '10098', '10099', '10101', '10102', '10103', '10104',
    '10105', '10106', '10107', '10108', '10109', '10110', '10111',
    '10112', '10113', '10114', '10115', '10116', '10117', '10118',
    '10119', '10120', '10121', '10122', '10123', '10124', '10125',
    '10126', '10128', '10129', '10130', '10131', '10132', '10133',
    '10138', '10149', '10150', '10151', '10152', '10153', '10154',
    '10155', '10156', '10157', '10158', '10159', '10160', '10161',
    '10162', '10163', '10164', '10165', '10166', '10167', '10168',
    '10169', '10170', '10171', '10172', '10173', '10174', '10175',
    '10176', '10177', '10178', '10179', '10184', '10185', '10196',
    '10197', '10199', '10203', '10211', '10212', '10213', '10242',
    '10249', '10256', '10257', '10258', '10259', '10260', '10261',
    '10265', '10268', '10269', '10270', '10271', '10272', '10273',
    '10274', '10275', '10276', '10277', '10278', '10279', '10280',
    '10281', '10282', '10285', '10286', '11001', '11004', '11005',
    '11040', '11096', '11101', '11102', '11103', '11104', '11105',
    '11106', '11109', '11120', '11351', '11352', '11354', '11355',
    '11356', '11357', '11358', '11359', '11360', '11361', '11362',
    '11363', '11364', '11365', '11366', '11367', '11368', '11369',
    '11370', '11371', '11372', '11373', '11374', '11375', '11377',
    '11378', '11379', '11380', '11381', '11385', '11386', '11390',
    '11405', '11411', '11412', '11413', '11414', '11415', '11416',
    '11417', '11418', '11419', '11420', '11421', '11422', '11423',
    '11424', '11425', '11426', '11427', '11428', '11429', '11430',
    '11431', '11432', '11433', '11434', '11435', '11436', '11439',
    '11451', '11499', '11690', '11691', '11692', '11693', '11694',
    '11695', '11697', '10292', '10301', '10302', '10303', '10304',
    '10305', '10306', '10307', '10308', '10309', '10310', '10311',
    '10312', '10313', '10314', '10097', '10514', '10543', '10553',
    '10573', '10701', '10705', '10911', '10965', '10977', '11021',
    '11050', '11111', '11112', '11471', '11510', '11548', '11566',
    '11577', '11580', '11598', '11629', '11731', '11798', '11968',
    '12423', '12428', '12435', '12458', '12466', '12473', '12528',
    '12701', '12733', '12734', '12737', '12750', '12751', '12754',
    '12758', '12759', '12763', '12764', '12768', '12779', '12783',
    '12786', '12788', '12789', '13731', '16091', '20459'];

export default Screener;
