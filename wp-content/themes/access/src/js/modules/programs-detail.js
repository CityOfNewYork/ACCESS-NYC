/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Utility from 'modules/utility';

/**
 * The main unique thing about program details is that they use a ?step=x query
 * parameter in the URL to determine the visible section. It is still all
 * the same page. A hash would seem more appropriate, but there were
 * some supposed issues with WPML where the hash was being stripped when
 * switching between langauges. Because it is a single page, we don't need
 * to actually reload the browser, which is why history.pushState is used.
 */
class ProgramsDetail {
  /**
   * Constructor
   *
   * @return  {Object}  Instantiated ProgramsDetail Class
   */
  constructor() {
    const isMobileView = () => $('[data-js="site-desktop-nav"]')
      .is(':hidden');

    $('[data-js*="program-nav-step-link"]').on('click', e => {
      if (!history.pushState) {
        return true;
      }

      e.preventDefault();

      const step = Utility.getUrlParameter('step', $(e.target).attr('href'));
      let linkType = '';

      window.history.pushState(null, null, '?step=' + step);

      if ($(e.target).hasClass('[data-js*="jump-to-anchor"]')) {
        linkType = 'buttonLink';
      } else {
        linkType = 'navLink';
      }
      $(window).trigger('popstate', linkType);
    });

    $(window).on('popstate', (e, linkType) => {
      const possibleSections = [
        'how-it-works',
        'how-to-apply',
        'determine-your-eligibility',
        'what-you-need-to-include'
      ];

      let sectionId = Utility.getUrlParameter('step');

      if (!sectionId || !$.inArray(sectionId, possibleSections)) {
        sectionId = 'how-it-works';
      }

      // If the page is in a mobile view, and the user has clicked a button
      // (as opposed to one of the table of content links) we want to scroll
      // the browser to the content body as opposed to the top of the page.
      if (isMobileView() && linkType === 'buttonLink') {
        $(document).scrollTop(
          $(ProgramsDetail.selector).offset().top
        );
      } else {
        $(document).scrollTop(0);
      }

      this.showSection(sectionId);
    }).trigger('popstate');

    return this;
  }

  /**
   * Advances Program Page Steps
   * @param  {string}  step  The kebab case identifier for the section
   */
  showSection(step) {
    $('[data-js="program-detail-step"]')
      .removeClass('active').filter(`#${step}`).addClass('active');

    $('[data-js="program-nav"] a').removeClass('active')
      .filter(`#nav-link-${step}`).addClass('active');
  }
}

ProgramsDetail.selector = '[data-js="program-detail-content"]';

export default ProgramsDetail;
