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
        const header = document.querySelector("#program-guide-header");
        
        // Function to update the active link
        const updateActiveLink = () => {
            let topSection = null;
            const headerBottom = header.getBoundingClientRect().bottom;
        
            // Find the topmost section that is visible
            sections.forEach((section) => {
                const rect = section.getBoundingClientRect();

                // section is visible: 
                //      it is not above the viewport && 
                //      it is not above the bottom of the header (which matters if the header is sticky) && 
                //      it is not below the viewport
                if ( rect.bottom >= (0 + WINDOW_DELTA) && 
                     rect.bottom >= (headerBottom + WINDOW_DELTA) && 
                     rect.top < (window.innerHeight - WINDOW_DELTA) ) {
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

                // Add the hash to the URL unless the user is at the top of the page
                if (window.scrollY > headerBottom) {
                    history.pushState(null, "", `#${topSection.id}`);
                }
            }
        };
        
        // Attach event listeners for scroll and resize
        window.addEventListener("scroll", updateActiveLink);
        window.addEventListener("resize", updateActiveLink);
        
        // Initial check for active link
        updateActiveLink();

        // Adjust scroll for direct hash navigation on page load
        if (window.location.hash) {
            const headerBottom = header.getBoundingClientRect().bottom;
            const headerSize = header.getBoundingClientRect().bottom - header.getBoundingClientRect().top;
            if (headerBottom > 0) {
                const targetId = window.location.hash.substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - headerSize;
                    window.scrollTo({
                        top: targetPosition
                    });
                }
            }
        }

        // Adjust scroll when clicking jump links
        document.querySelectorAll(".top-nav-link").forEach((link) => {
            link.addEventListener("click", (e) => {
                const headerBottom = header.getBoundingClientRect().bottom;
                const headerSize = header.getBoundingClientRect().bottom - header.getBoundingClientRect().top;
                if (headerBottom > 0) {
                    e.preventDefault(); // Prevent default jump behavior
                    const targetId = link.getAttribute("href").substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - headerSize;
                        window.scrollTo({
                            top: targetPosition
                        });
                    }

                    history.pushState(null, "", `#${targetId}`);
                }
            });
        });
    });
      
      
})();
