/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Utility from 'modules/utility';

/**
 * Calc Input Class
 */
class CalcInput {

  /**
   * Constructor
   * @param  {object} element - the element triggering the event
   */
  constructor(element) {
    this.selector = '[data-js*="calc-input"]';

    this.events = 'keypress paste drop';

    $(element).on(this.events, this.selector, event => {
      this.bus(event);
    });
  }

  /**
   * The main event processor for drop, past, and key events
   * @param  {object} event - the triggering event
   */
  bus(event) {
    const key = event.keyCode;
    const backspace = (key === 8 || key === 46);
    const arrows = (key >= 37 && key <= 40);
    if (backspace || arrows) return;
    if (event.type === 'drop') {
      const start = event.currentTarget.selectionStart;
      const end = event.currentTarget.selectionEnd;
      if (start === end) {
        this._calc(event.originalEvent.dataTransfer.getData('text'), event);
      } else {
        event.preventDefault();
        /* eslint-disable no-console, no-debugger */
        if (Utility.debug())
          console.warn('CalcInput: Blocked. Dropping not allowed from source.');
        /* eslint-enable no-console, no-debugger */
      }
    } else if (event.type === 'paste') {
      this._calc(event.originalEvent.clipboardData.getData('text'), event);
    } else if (!this._isPaste(key) || !this._isCopy(key)) {
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug())
        console.dir({
          'charCode': event.charCode,
          'fromCharCode': String.fromCharCode(event.charCode),
          'event': event
        });
      /* eslint-enable no-console, no-debugger */
      this._calc(String.fromCharCode(event.charCode), event);
      // this._calc(event.key, event);
    }
    // store previous key for keyboard combination (paste) detection.
    window[CalcInput.PREVIOUS_KEY] = key;
  }

  /**
   * Detection for paste event
   * @param  {number} key the current key code
   * @return {boolean}    if the paste command is being used
   */
  _isPaste(key) {
    let ctrl = (window[CalcInput.PREVIOUS_KEY] === 91);
    let cmd = (window[CalcInput.PREVIOUS_KEY] === 17);
    let v = (key === 86);
    return ((ctrl || cmd) || v);
  }

  /**
   * Detection for copy event
   * @param  {number} key the current key code
   * @return {boolean}    if the copy command is being used
   */
  _isCopy(key) {
    let ctrl = (window[CalcInput.PREVIOUS_KEY] === 91);
    let cmd = (window[CalcInput.PREVIOUS_KEY] === 17);
    let v = (key === 67);
    return ((ctrl || cmd) || v);
  }

  /**
   * For a given dollar float input, product requirements dictate we should
   * limit values to 6 digits before the decimal point and 2 after.
   * @param  {string} text - the text to calculate
   * @param  {object} event - the original event object
   */
  _calc(text, event) {
    const el = event.currentTarget;
    const value = (el.value) ? el.value : '';
    const start = el.selectionStart;
    const end = el.selectionEnd;
    const calc = [
      value.substring(0, start), text, value.substring(end, value.length)
    ].join('');
    /* eslint-disable no-console, no-debugger */
    if (Utility.debug()) {
      console.dir(['CalcInput', {
        'selectionStart': start,
        'text': text,
        'selectionEnd': end,
        'calculatedValue': calc
      }]);
    }
    /* eslint-enable no-console, no-debugger */
    this._testCalc(calc, event);
  }

  /**
   * Create the regular expression and test the input
   * @param  {string} calc  The calculated input
   * @param  {object} event The original event
   */
  _testCalc(calc, event) {
    try {
      const r = new RegExp(event.currentTarget.dataset.jsRegex, 'g');
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug()) console.log(`CalcInput: ${r}`);
      const found = calc.match(r);
      if (found.length && Utility.debug()) {
        console.log('CalcInput: Passed!');
      }
      /* eslint-enable no-console, no-debugger */
    } catch (error) {
      event.preventDefault(); // stop input
      /* eslint-disable no-console, no-debugger */
      if (Utility.debug()) {
        console.warn('CalcInput: Blocked. Input will not match valid format');
      }
      /* eslint-enable no-console, no-debugger */
    }
  }

}

CalcInput.PREVIOUS_KEY = '_prevKey';

export default CalcInput;
