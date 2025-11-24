'use strict';

// Copied from ACCESS patterns, but modified for accordion
/**
 * The Simple Toggle class. This will toggle the class 'active' and 'hidden'
 * on target elements, determined by a click event on a selected link or
 * element. This will also toggle the aria-hidden attribute for targeted
 * elements to support screen readers. Target settings and other functionality
 * can be controlled through data attributes.
 *
 * This uses the .matches() method which will require a polyfill for IE
 * https://polyfill.io/v2/docs/features/#Element_prototype_matches
 *
 * @class
 */
class Toggle {
  /**
   * @constructor
   *
   * @param  {Object}  s  Settings for this Toggle instance
   *
   * @return {Object}     The class
   */
  constructor(s) {
    // Create an object to store existing toggle listeners (if it doesn't exist)
    if (!window.hasOwnProperty(Toggle.callback))
      window[Toggle.callback] = [];

    s = (!s) ? {} : s;

    this.settings = {
      selector: (s.selector) ? s.selector : Toggle.selector,
      namespace: (s.namespace) ? s.namespace : Toggle.namespace,
      inactiveClass: (s.inactiveClass) ? s.inactiveClass : Toggle.inactiveClass,
      activeClass: (s.activeClass) ? s.activeClass : Toggle.activeClass,
      before: (s.before) ? s.before : false,
      after: (s.after) ? s.after : false,
      valid: (s.valid) ? s.valid : false,
      focusable: (s.hasOwnProperty('focusable')) ? s.focusable : true,
      jump: (s.hasOwnProperty('jump')) ? s.jump : true
    };

    // Store the element for potential use in callbacks
    this.element = (s.element) ? s.element : false;

    if (this.element) {
      this.element.addEventListener('click', (event) => {
        this.toggle(event);
      });
    } else {
      // If there isn't an existing instantiated toggle, add the event listener.
      if (!window[Toggle.callback].hasOwnProperty(this.settings.selector)) {
        let body = document.querySelector('body');

        for (let i = 0; i < Toggle.events.length; i++) {
          let tggleEvent = Toggle.events[i];

          body.addEventListener(tggleEvent, event => {
            if (!event.target.matches(this.settings.selector))
              return;

            this.event = event;

            let type = event.type.toUpperCase();

            if (
              this[event.type] &&
              Toggle.elements[type] &&
              Toggle.elements[type].includes(event.target.tagName)
            ) this[event.type](event);
          });
        }
      }
    }

    // Record that a toggle using this selector has been instantiated.
    // This prevents double toggling.
    window[Toggle.callback][this.settings.selector] = true;

    return this;
  }

  /**
   * Click event handler
   *
   * @param  {Event}  event  The original click event
   */
  click(event) {
    this.toggle(event);
  }

  /**
   * Input/select/textarea change event handler. Checks to see if the
   * event.target is valid then toggles accordingly.
   *
   * @param  {Event}  event  The original input change event
   */
  change(event) {
    let valid = event.target.checkValidity();

    if (valid && !this.isActive(event.target)) {
      this.toggle(event); // show
    } else if (!valid && this.isActive(event.target)) {
      this.toggle(event); // hide
    }
  }

  /**
   * Check to see if the toggle is active
   *
   * @param  {Object}  element  The toggle element (trigger)
   */
  isActive(element) {
    let active = false;

    if (this.settings.activeClass) {
      active = element.classList.contains(this.settings.activeClass)
    }

    // if () {
      // Toggle.elementAriaRoles
      // TODO: Add catch to see if element aria roles are toggled
    // }

    // if () {
      // Toggle.targetAriaRoles
      // TODO: Add catch to see if target aria roles are toggled
    // }

    return active;
  }

  /**
   * Get the target of the toggle element (trigger)
   *
   * @param  {Object}  el  The toggle element (trigger)
   */
  getTarget(element) {
    let target = false;

    /** Anchor Links */
    target = (element.hasAttribute('href')) ?
      document.querySelector(element.getAttribute('href')) : target;

    /** Toggle Controls */
    target = (element.hasAttribute('aria-controls')) ?
      document.querySelector(`#${element.getAttribute('aria-controls')}`) : target;

    return target;
  }

  /**
   * The toggle event proxy for getting and setting the element/s and target
   *
   * @param  {Object}  event  The main click event
   *
   * @return {Object}         The Toggle instance
   */
  toggle(event) {
    let element = event.target;
    let target = false;
    let focusable = [];

    event.preventDefault();

    target = this.getTarget(element);

    /** Focusable Children */
    focusable = (target) ?
      target.querySelectorAll(Toggle.elFocusable.join(', ')) : focusable;

    /** Main Functionality */
    if (!target) return this;
    this.elementToggle(element, target, focusable);

    /** Undo */
    if (element.dataset[`${this.settings.namespace}Undo`]) {
      const undo = document.querySelector(
        element.dataset[`${this.settings.namespace}Undo`]
      );

      undo.addEventListener('click', (event) => {
        event.preventDefault();
        this.elementToggle(element, target);
        undo.removeEventListener('click');
      });
    }

    return this;
  }

  /**
   * Get other toggles that might control the same element
   *
   * @param   {Object}    element  The toggling element
   *
   * @return  {NodeList}           List of other toggling elements
   *                               that control the target
   */
  getOthers(element) {
    let selector = false;

    if (element.hasAttribute('href')) {
      selector = `[href="${element.getAttribute('href')}"]`;
    } else if (element.hasAttribute('aria-controls')) {
      selector = `[aria-controls="${element.getAttribute('aria-controls')}"]`;
    }

    return (selector) ? document.querySelectorAll(selector) : [];
  }

  /**
   * Hide the Toggle Target's focusable children from focus.
   * If an element has the data-attribute `data-toggle-tabindex`
   * it will use that as the default tab index of the element.
   *
   * @param   {NodeList}  elements  List of focusable elements
   *
   * @return  {Object}              The Toggle Instance
   */
  toggleFocusable(elements) {
    elements.forEach(element => {
      let tabindex = element.getAttribute('tabindex');

      if (tabindex === '-1') {
        let dataDefault = element
          .getAttribute(`data-${Toggle.namespace}-tabindex`);

        if (dataDefault) {
          element.setAttribute('tabindex', dataDefault);
        } else {
          element.removeAttribute('tabindex');
        }
      } else {
        element.setAttribute('tabindex', '-1');
      }
    });

    return this;
  }

  /**
   * Jumps to Element visibly and shifts focus
   * to the element by setting the tabindex
   *
   * @param   {Object}  element  The Toggling Element
   * @param   {Object}  target   The Target Element
   *
   * @return  {Object}           The Toggle instance
   */
  jumpTo(element, target) {
    // Reset the history state. This will clear out
    // the hash when the target is toggled closed
    history.pushState('', '',
      window.location.pathname + window.location.search);

    // Focus if active
    if (target.classList.contains(this.settings.activeClass)) {
      window.location.hash = element.getAttribute('href');

      target.setAttribute('tabindex', '0');
      target.focus({preventScroll: true});
    } else {
      target.removeAttribute('tabindex');
    }

    return this;
  }

  /**
   * The main toggling method for attributes
   *
   * @param  {Object}    element    The Toggle element
   * @param  {Object}    target     The Target element to toggle active/hidden
   * @param  {NodeList}  focusable  Any focusable children in the target
   *
   * @return {Object}               The Toggle instance
   */
  elementToggle(element, target, focusable = []) {
    let i = 0;
    let attr = '';
    let value = '';

    /**
     * Store elements for potential use in callbacks
     */

    this.element = element;
    this.target = target;
    this.others = this.getOthers(element);
    this.focusable = focusable;

    /**
     * Validity method property that will cancel the toggle if it returns false
     */

    if (this.settings.valid && !this.settings.valid(this))
      return this;

    /**
     * Toggling before hook
     */

    if (this.settings.before)
      this.settings.before(this);

    /**
     * Toggle Element and Target classes
     */

    if (this.settings.activeClass) {
      this.element.classList.toggle(this.settings.activeClass);
      this.target.classList.toggle(this.settings.activeClass);

      // If there are other toggles that control the same element
      this.others.forEach(other => {
        if (other !== this.element)
          other.classList.toggle(this.settings.activeClass);
      });
    }

    if (this.settings.inactiveClass)
      target.classList.toggle(this.settings.inactiveClass);

    /**
     * Target Element Aria Attributes
     */

    for (i = 0; i < Toggle.targetAriaRoles.length; i++) {
      attr = Toggle.targetAriaRoles[i];
      value = this.target.getAttribute(attr);

      if (value != '' && value)
        this.target.setAttribute(attr, (value === 'true') ? 'false' : 'true');
    }

    /**
     * Toggle the target's focusable children tabindex
     */

    if (this.settings.focusable)
      this.toggleFocusable(this.focusable);

    /**
     * Jump to Target Element if Toggle Element is an anchor link
     */

    if (this.settings.jump && this.element.hasAttribute('href'))
      this.jumpTo(this.element, this.target);

    /**
     * Toggle Element (including multi toggles) Aria Attributes
     */

    for (i = 0; i < Toggle.elAriaRoles.length; i++) {
      attr = Toggle.elAriaRoles[i];
      value = this.element.getAttribute(attr);

      if (value != '' && value)
        this.element.setAttribute(attr, (value === 'true') ? 'false' : 'true');

      // If there are other toggles that control the same element
      this.others.forEach((other) => {
        if (other !== this.element && other.getAttribute(attr))
          other.setAttribute(attr, (value === 'true') ? 'false' : 'true');
      });
    }

    /**
     * Toggling complete hook
     */

    if (this.settings.after)
      this.settings.after(this);

    return this;
  }
}

/** @type  {String}  The main selector to add the toggling function to */
Toggle.selector = '[data-js*="toggle"]';

/** @type  {String}  The namespace for our data attribute settings */
Toggle.namespace = 'toggle';

/** @type  {String}  The hide class */
Toggle.inactiveClass = 'hidden';

/** @type  {String}  The active class */
Toggle.activeClass = 'active';

/** @type  {Array}  Aria roles to toggle true/false on the toggling element */
Toggle.elAriaRoles = ['aria-pressed', 'aria-expanded'];

/** @type  {Array}  Aria roles to toggle true/false on the target element */
Toggle.targetAriaRoles = ['aria-hidden'];

/** @type  {Array}  Focusable elements to hide within the hidden target element */
Toggle.elFocusable = [
  'a', 'button', 'input', 'select', 'textarea', 'object', 'embed', 'form',
  'fieldset', 'legend', 'label', 'area', 'audio', 'video', 'iframe', 'svg',
  'details', 'table', '[tabindex]', '[contenteditable]', '[usemap]'
];

/** @type  {Array}  Key attribute for storing toggles in the window */
Toggle.callback = ['TogglesCallback'];

/** @type  {Array}  Default events to to watch for toggling. Each must have a handler in the class and elements to look for in Toggle.elements */
Toggle.events = ['click', 'change'];

/** @type  {Array}  Elements to delegate to each event handler */
Toggle.elements = {
  CLICK: ['A', 'BUTTON'],
  CHANGE: ['SELECT', 'INPUT', 'TEXTAREA']
};

export default Toggle;