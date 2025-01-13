/* eslint-env browser */

import StepByStep from 'modules/step-by-step';
// import 'modules/feedback';
import 'modules/share-form';

(() => {
  'use strict';

  /**
   * Instantiate the Program Guide
   */
    document.addEventListener("DOMContentLoaded", () => {
        const WINDOW_DELTA = 50;

        const sections = document.querySelectorAll("section");
        const sideLinks = document.querySelectorAll(".side-nav-link");
        const topLinks = document.querySelectorAll(".top-nav-link");
        
        // Function to update the active link
        const updateActiveLink = () => {
            let topSection = null;
        
            // Find the topmost section that is visible
            sections.forEach((section) => {
                const rect = section.getBoundingClientRect();
                if (rect.bottom >= (0 + WINDOW_DELTA) && rect.top < (window.innerHeight - WINDOW_DELTA) ) {
                    if (!topSection || rect.top < topSection.getBoundingClientRect().top) {
                        topSection = section;
                    }
                }
            });
        
            // Update the active state of links
            sideLinks.forEach((link) => link.classList.remove("active"));
            topLinks.forEach((link) => link.classList.remove("active"));

            if (topSection) {
                const activeSideLink = document.querySelector(`#side-nav-link-${topSection.id}`);
                if (activeSideLink) {
                    activeSideLink.classList.add("active");
                }

                const activeTopLink = document.querySelector(`#top-nav-link-${topSection.id}`);
                if (activeTopLink) {
                    activeTopLink.classList.add("active");
                }
            }
        };
        
        // Attach event listeners for scroll and resize
        window.addEventListener("scroll", updateActiveLink);
        window.addEventListener("resize", updateActiveLink);
        
        // Initial check
        updateActiveLink();
    });
      
      
})();
