/* eslint-env browser */

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
        const topNav = document.querySelector(".c-top-nav");

        // for top nav horizontal scrolling
        function centerActiveLinkIfScrollable() {
            const activeLink = document.querySelector(".top-nav-link.active");
          
            if (activeLink && topNav && topNav.scrollWidth > topNav.clientWidth) {
              // Proceed only if the container is scrollable
              const linkRect = activeLink.getBoundingClientRect();
              const containerRect = topNav.getBoundingClientRect();
          
              // Calculate the horizontal scroll offset
              const offset = (containerRect.width / 2) - (linkRect.width / 2);
              const scrollPosition = activeLink.offsetLeft - offset;
          
              // Smoothly scroll the container to center the active link
              topNav.scrollTo({
                left: scrollPosition
              });
            }
        }
        
        // Function to update the active link
        const updateActiveLink = () => {
            let topSection = null;
            let navBottom = 0;

            if (window.getComputedStyle(topNav).display !== "none") {
                navBottom = topNav.getBoundingClientRect().bottom;
            }
        
            // Find the topmost section that is visible
            sections.forEach((section) => {
                const rect = section.getBoundingClientRect();

                // section is visible: 
                //      it is not above the viewport && 
                //      it is not above the bottom of the top nav (which matters if the top nav is sticky) && 
                //      it is not below the viewport
                if ( rect.bottom >= (0 + WINDOW_DELTA) && 
                     rect.bottom >= (navBottom + WINDOW_DELTA) && 
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

                    centerActiveLinkIfScrollable();
                }

                // Add the hash to the URL unless the user is at the top of the page
                if (window.scrollY > navBottom) {
                    history.pushState(null, "", `#${topSection.id}`);
                }
            }
        };
        
        // Attach event listeners for scroll and resize
        window.addEventListener("scroll", updateActiveLink);
        window.addEventListener("resize", updateActiveLink);
        
        // Initial check for active link
        updateActiveLink();
        centerActiveLinkIfScrollable();

        // Adjust scroll for direct hash navigation on page load
        if (window.location.hash) {
            requestAnimationFrame(() => {
                let navBottom = topNav.getBoundingClientRect().bottom;
                let navSize = navBottom - topNav.getBoundingClientRect().top;
    
                if (window.getComputedStyle(topNav).display !== "none") {
                    navBottom = topNav.getBoundingClientRect().bottom;
                    navSize = navBottom - topNav.getBoundingClientRect().top;
                }
    
                if (navBottom > 0) {
                    const targetId = window.location.hash.substring(1);
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - navSize;
                        window.scrollTo({
                            top: targetPosition
                        });
                    }
                }
            })
        }

        // Adjust scroll when clicking jump links
        document.querySelectorAll(".top-nav-link").forEach((link) => {
            link.addEventListener("click", (e) => {
                let navBottom = 0;
                let navSize = 0;

                if (window.getComputedStyle(topNav).display !== "none") {
                    navBottom = topNav.getBoundingClientRect().bottom;
                    navSize = navBottom - topNav.getBoundingClientRect().top;
                }

                if (navBottom > 0) {
                    e.preventDefault(); // Prevent default jump behavior
                    const targetId = link.getAttribute("href").substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - navSize;
                        window.scrollTo({
                            top: targetPosition
                        });

                        centerActiveLinkIfScrollable();
                    }

                    history.pushState(null, "", `#${targetId}`);
                }
            });
        });

    });
      
      
})();
