/* eslint-env browser */
'use strict';

import $ from 'jquery';
import _ from 'underscore';

/**
 * This component takes an element that serves as a filter controller for a
 * parent OfficeMap. This handles UI toggle interactions, emits a 'change'
 * event when its state changes, and can return an array of its active checkbox
 * values.
 * @class
 */
class OfficeFilter {
  /**
   * @param {HTMLElement} el - The HTML element for this component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The main component element. */
    this._el = el;

    /** @private {Array<Number>} An array of active program IDs. */
    this._programs = [];

    /** @private {jQuery} The program inputs in this controller. */
    this._$programCheckboxes =
        $(el).find(`input.${OfficeFilter.CssClass.PROGRAM_CHECKBOX}`);

    /** @private {boolean} Whether this control has been initialized. */
    this._initialized = false;
  }

  /**
   * Attach event handlers if this has not been initialized yet.
   * @return {this} OfficeFilter
   */
  init() {
    if (this._initialized) {
      return this;
    }

    $(this._el).on('change', `.${OfficeFilter.CssClass.PROGRAM_CHECKBOX}`,
        e => {
          this.setCategoryParent(e.currentTarget).updateResults();
        }).on('change', `.${OfficeFilter.CssClass.PARENT_CHECKBOX}`, e => {
          const $checkbox = $(e.currentTarget);
          this.toggleCheckGroup($checkbox.data('toggles'),
              $checkbox.prop('checked')).updateResults();
        }).on('click', `.${OfficeFilter.CssClass.TOGGLE}`, e => {
          const targetSelector = $(e.currentTarget).data('target') ||
              $(e.currentTarget).attr('href');
          e.preventDefault();
          this.togglePanel($(targetSelector)[0], e.currentTarget);
        });
    this._initialized = true;

    return this;
  }

  /**
   * Updates this._programs with the value of checked controls.
   * @method
   * @return {this} OfficeFilter
   */
  updateResults() {
    this._programs = [];
    this._$programCheckboxes.each((i, el) => {
      const $checkbox = $(el);
      if ($checkbox.prop('checked')) {
        this._programs.push(parseInt($checkbox.val(), 10));
      }
    });
    $(this._el).trigger(OfficeFilter.Event.UPDATE);
    return this;
  }

  /**
   * Returns the value of this._programs.
   * @method
   * @return {Array<Number>} array of matched program IDs.
   */
  getPrograms() {
    return this._programs;
  }

  /**
   * Checks any inputs that match the passed array of program IDs and updates
   * this._programs.
   * @method
   * @param {Array<Number>} programs - array of program IDs
   * @return {this} OfficeFilter
   */
  setPrograms(programs) {
    if(!_.isArray(programs)) {
      return this;
    }
    this._$programCheckboxes.each((i, el) => {
      const $checkbox = $(el);
      const checked = programs.indexOf(parseInt($checkbox.val(), 10)) >= 0;
      $checkbox.prop('checked', checked);
      if (checked) {
        this.setCategoryParent(el);
      }
    });
    this._programs = programs;
    return this;
  }

  /**
   * Toggles all inputs in the targeted element.
   * @method
   * @param {string} selector - Parent element to target
   * @param {boolean} toggle - whether to toggle these elements on or off.
   * @return {this} OfficeFilter
   */
  toggleCheckGroup(selector, toggle) {
    $(this._el).find(selector)
        .find(`.${OfficeFilter.CssClass.PROGRAM_CHECKBOX}`)
        .prop('checked', toggle);
    return this;
  }

  /**
   * For a program checkbox, checks its related category checkbox if the
   * program checkbox is checked, otheriwse if all sibling program checkboxes
   * are unchecked, unchecks the related category checkbox.
   * @method
   * @param {HTMLElement} checkbox - Program checkbox.
   * @return {this} OfficeFilter
   */
  setCategoryParent(checkbox) {
    const $categoryGroup = $(checkbox)
        .closest(`.${OfficeFilter.CssClass.PROGRAM_GROUP}`);
    const $categoryCheckbox = $(this._el)
        .find(`input[data-toggles="#${$categoryGroup.attr('id')}"]`);
    if ($(checkbox).prop('checked')) {
      $categoryCheckbox.prop('checked', true);
    } else if (!$categoryGroup
        .find(`input.${OfficeFilter.CssClass.PROGRAM_CHECKBOX}`)
        .filter(':checked').length) {
      $categoryCheckbox.prop('checked', false);
    }
    return this;
  }

  /**
   * Toggles the active class on a target and optionally the triggering element.
   * @method
   * @param {HTMLElement} target - the targetted panel element
   * @param {?HTMLElement} trigger - the element that triggered the toggle
   * @return {this} OfficeFilter
   */
  togglePanel(target, trigger) {
    const els = [];
    if (target) {
      els.push(target);
    }
    if (trigger) {
      els.push(trigger);
    }
    $(els).toggleClass(OfficeFilter.CssClass.ACTIVE);

    const targetActive = $(target).hasClass(OfficeFilter.CssClass.ACTIVE);
    $(target).attr('aria-hidden',
        !targetActive);
    if (targetActive) {
      $(target).find(`.${OfficeFilter.CssClass.PARENT_CHECKBOX},
          .${OfficeFilter.CssClass.PROGRAM_CHECKBOX},
          .${OfficeFilter.CssClass.TOGGLE}`).removeAttr('tabindex');
    } else {
      $(target).find(`.${OfficeFilter.CssClass.PARENT_CHECKBOX},
          .${OfficeFilter.CssClass.PROGRAM_CHECKBOX},
          .${OfficeFilter.CssClass.TOGGLE}`).attr('tabindex', '-1');
    }
    return this;
  }
}

/**
 * CSS classes used by this component.
 * @enum {string}
 */
OfficeFilter.CssClass = {
  ACTIVE: 'active',
  MAIN_TOGGLE: 'js-main-filter-toggle',
  PARENT_CHECKBOX: 'js-map-filter-parent-input',
  PROGRAM_CHECKBOX: 'js-map-filter-program-input',
  PROGRAM_GROUP: 'js-map-filter-program-group',
  TOGGLE: 'js-toggle-filter'
};

/**
 * Events used by this component.
 * @enum {string}
 */
OfficeFilter.Event = {
  UPDATE: 'update'
};


export default OfficeFilter;
