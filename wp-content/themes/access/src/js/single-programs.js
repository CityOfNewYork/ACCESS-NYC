/* eslint-env browser */

import StepByStep from 'modules/step-by-step';
// import 'modules/feedback';
import 'modules/share-form';

(() => {
  'use strict';

  /**
   * Instantiate the Program Guide
   */
//   (element => {
//     if (element) new StepByStep(element);
//   })(document.querySelector(StepByStep.selector));

    document.addEventListener("DOMContentLoaded", () => {
        const sections = document.querySelectorAll("section");
        const links = document.querySelectorAll(".nav-link");
    
        const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if(entry.target.id) {
                    console.log("a");
                    const current_link = document.querySelector(`#nav-link-${entry.target.id}`);
                    if (entry.isIntersecting) {
                        console.log("b");
                        console.log(current_link);
                        links.forEach((link) => link.classList.remove("active"));
                        console.log(current_link.classList);
                        current_link.classList.add("active");
                    }
                }
            });
        },
        {
            root: null, // Use the viewport as the container
            threshold: 0.5, // Trigger when 50% of the section is visible
        }
        );
    
        sections.forEach((section) => observer.observe(section));
    });
})();
