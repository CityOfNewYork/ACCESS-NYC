/* eslint-env browser */

import StepByStep from 'modules/step-by-step';
import 'modules/feedback';
import 'modules/share-form';

(() => {
  'use strict';

  /**
   * Instantiate the Program Guide
   */
  (element => {
    if (element) new StepByStep(element);
  })(document.querySelector(StepByStep.selector));
})();

