/* eslint-env browser */
// Core-js polyfills.
// Core-js is made available as a dependency of @babel/preset-env
import 'core-js/features/url-search-params';

// Patterns Framework
// import Toggle from 'utilities/toggle/toggle';
import Toggle from '../../node_modules/@nycopportunity/patterns-framework/src/utilities/toggle/toggle';

/**
 * Programs Detail
 */
(() => {
  'use strict';

  const element = document.querySelector('[data-js="step-by-step"]');

  if (!element) return;

  const steps = element.querySelectorAll('[data-step]');

  let step = 0;

  let StepByStep = new Toggle({
    selector: '[data-step-go-to]',
    jump: false
  });

  let el = document.createElement('a'); // symbolic element for toggle method

  /**
   * Hide all steps using the toggle method
   */

  for (let index = 0; index < steps.length; index++) {
    steps[index].focusable = steps[index]
      .querySelectorAll(Toggle.elFocusable.join(', '));

    StepByStep.elementToggle(el, steps[index], steps[index].focusable);
  }

  /**
   * Find queried step index
   */

  const hash = window.location.hash;

  const search = window.location.search;

  if (hash || search) {
    const query = (search) ?
      new URLSearchParams(search).get('step') : hash.replace('#', '');

    for (let index = 0; index < steps.length; index++) {
      if (steps[index].matches(`[data-step='${query}']`)) {
        step = index;

        break;
      }
    }
  }

  /**
   * Show the first or queried step
   */

  StepByStep.elementToggle(el, steps[step], steps[step].focusable);

  /**
   * Create toggle for other links
   */

  StepByStep.settings.before = (toggle) => {
    // Hide all sections unless it is the target section
    for (let index = 0; index < steps.length; index++) {
      if (toggle.target === steps[index]) continue;

      steps[index].classList.remove(toggle.settings.activeClass);
      steps[index].classList.add(toggle.settings.inactiveClass);
      steps[index].setAttribute('aria-hidden', true);

      StepByStep.toggleFocusable(steps[index].focusable);
    }
  };

  StepByStep.settings.after = (toggle) => {
    console.dir('After');
    console.dir(toggle);
    // TODO: Scroll to the top of section parent (N/A?)
    // TODO: Push state to history api
    // TODO: Update active link in sidebar
  };

  // TODO: window on pop state event listener
  //   toggle steps
})();

